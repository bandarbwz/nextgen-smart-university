# Project Handover

Work log for the NextGen Smart University Platform build.

Last updated: 2026-08-03. Nothing has been pushed to GitHub yet.

---

## 1. Current state

Phase 1 of the roadmap is complete. All six Phase 1 modules have a working
backend and frontend, verified against a running MySQL database.

| Module | Backend | Frontend | Branch |
|--------|---------|----------|--------|
| Authentication | Done | Done | `feature/authentication`, `feature/frontend-auth-academic` |
| Academic | Done | Done | `feature/academic`, `feature/frontend-auth-academic` |
| Attendance | Done | Done | `feature/attendance` |
| LMS | Done | Done | `feature/lms` |
| Calendar | Done | Done | `feature/calendar` |
| Chat | Done | Done | `feature/chat` |

Totals: 41 database tables, 110 PHP files, ~200 REST endpoints, 11 commits.

**All 11 commits sit in a single chain on `feature/chat`.** Each branch was
created from the previous one, so `feature/chat` contains every change. You do
not need to push seven branches.

---

## 2. Pushing to GitHub (when you are ready)

Nothing is pushed. `main` on GitHub is still at the documentation commit.

To push the whole chain as one branch and open a pull request:

```bash
cd ~/Desktop/nextgen-smart-university
git push -u origin feature/chat
```

Then open a pull request from `feature/chat` into `main` on GitHub.

If you would rather push each module as its own branch so your friend can
review them separately:

```bash
git push -u origin feature/authentication
git push -u origin feature/academic
git push -u origin feature/frontend-auth-academic
git push -u origin feature/attendance
git push -u origin feature/lms
git push -u origin feature/calendar
git push -u origin feature/chat
```

Note: the GitHub CLI on this machine is authenticated as `SHAFOO11`, while the
repository belongs to `bandarbwz`. Confirm you have push access before running
the commands above.

---

## 3. Running the project from scratch

### Requirements installed on this machine

PHP 8.5, Composer and MySQL 8 were installed via Homebrew during this work.

```bash
brew services start mysql
```

### Backend

```bash
cd backend
composer install
cp .env.example .env
```

`backend/.env` is deliberately not committed. After copying it you must
generate a JWT secret and paste it into `JWT_SECRET`:

```bash
openssl rand -hex 32
```

Without a JWT secret the API returns 500 on every request by design, rather
than falling back to an insecure default.

### Database

Run the schema files in order, then the seeds, then the migration:

```bash
cd ~/Desktop/nextgen-smart-university
mysql -u root < database/schema/01-authentication.sql
mysql -u root < database/schema/02-academic.sql
mysql -u root < database/schema/03-attendance.sql
mysql -u root < database/schema/04-lms.sql
mysql -u root < database/schema/05-calendar.sql
mysql -u root < database/schema/06-chat.sql

mysql -u root < database/seed/01-authentication.sql
mysql -u root < database/seed/02-academic.sql

mysql -u root < database/migrations/01-backfill-course-chat-rooms.sql
```

The seeds create the six roles, 25 permissions, three faculties, five
departments, five programmes, two semesters and six courses. They do **not**
create user accounts.

### Running

```bash
php -S 127.0.0.1:8000 -t backend/public backend/public/index.php
```

```bash
cd frontend && npm install && npm run dev
```

API on `http://127.0.0.1:8000`, frontend on `http://localhost:5173`.

---

## 4. Test accounts

These exist in the local database only. They were created by hand for testing
and are **not** in the seed files. Password for all of them:

```
Password123!
```

| Email | Role |
|-------|------|
| `admin@nextgen.edu` | Administrator |
| `lecturer@nextgen.edu` | Lecturer (Dr. Sami Lecturer) |
| `other@nextgen.edu` | Lecturer (used to test cross-lecturer denial) |
| `student@nextgen.edu` | Student (Lina Student, STU001) |

If you rebuild the database these disappear. Recreate one with:

```bash
HASH=$(php -r 'echo password_hash("Password123!", PASSWORD_BCRYPT);')
mysql -u root nextgen_university -e "
INSERT INTO User (role_id, full_name, university_id, email, password, status, email_verified)
SELECT id,'Test Admin','ADM001','admin@nextgen.edu','$HASH','active',TRUE
FROM Role WHERE name='Administrator';"
```

---

## 5. Architecture

```
backend/
  app/
    Controllers/   thin, validate then delegate
    Models/        PDO data access, extend Model
    Services/      all business logic lives here
    Middleware/    AuthMiddleware, RoleMiddleware
    Validation/    per module validators
    Helpers/       Database, Router, Request, Response, Config, Logger, FileUpload
  config/          config.php, database.php
  routes/api.php   every route
  public/          front controller + .htaccess
  storage/         uploads, logs (gitignored contents)

frontend/src/
  components/  shared UI (Button, FormField, Alert, Badge, Skeleton, ...)
  layouts/     AuthLayout, AppLayout, navigationItems
  pages/       auth/, student/, lecturer/, plus Calendar, Chat, Profile
  services/    one API client module per backend module
  contexts/    AuthContext, ToastContext
  styles/      tokens.css, components.css, shell.css, auth.css

database/
  schema/      table definitions, run in numeric order
  seed/        reference data
  migrations/  backfills for data created before a feature existed
```

Design system output from the `ui-ux-pro-max` skill is committed at
`design-system/nextgen-smart-university/MASTER.md`.

---

## 6. Decisions taken, and why

These are places where a judgement call was made. Each is worth being able to
explain if the project is assessed.

**Backend is native PHP 8.4 MVC, not Laravel or Node.**
`docs/PROJECT/004-Technology-Stack.md`, the README and the folder structure doc
all specify native PHP with PDO. `docs/ARCHITECTURE/03-Backend-Architecture.md`
contradicts them by describing Node.js, Express and Prisma. That file appears
to be a leftover from a different template and should be corrected.

**Frontend does not use Bootstrap 5.** The tech stack doc names Bootstrap, but
the UI is built on design tokens from the ui-ux-pro-max skill instead. Layering
the token system over Bootstrap would have meant fighting Bootstrap's defaults.
If Bootstrap is a hard requirement for marking, this needs reworking.

**Chat uses short polling, not Socket.IO.** The architecture doc specifies
Socket.IO, which a native PHP backend cannot host. The client polls every five
seconds using an `after_id` cursor, pauses while the browser tab is hidden and
catches up when it becomes visible. Describe this as polling, not real time.

**Face verification is not stubbed.** `POST /api/v1/attendance/verify-face`
calls a configurable `AI_SERVICE_URL` and returns 503 when it is not
configured. It was deliberately not faked to return success, because that
would fake a security control. It will work unchanged once the Python service
exists.

**Tokens are stored hashed.** Session and refresh tokens are stored as SHA-256
hashes, so a database leak cannot be used to hijack sessions. QR attendance
tokens are the exception and are stored in plain text, because the lecturer has
to display the code to the room. They expire after ten minutes.

**Uploads are validated by content, not file name.** `FileUpload` sniffs the
real MIME type and rejects mismatches. Files are written outside the web root
under `backend/storage/uploads/` with random names, and are served through
authenticated download endpoints.

### Additions beyond the documented schema

Each of these fills a gap where the documentation described a feature but not
the storage it needs.

| Addition | Reason |
|----------|--------|
| `User.password_reset_token`, `password_reset_expires_at`, `email_verification_token` | The reset and verification endpoints are documented, but no table stored their tokens |
| `QuizOption` table | `QuizQuestion` had `correct_answer` but nowhere to store multiple choice options |
| `QuizAnswer` table | `QuizSubmission` stored only a score, but Essay and Short Answer must be graded by a human |
| `Message.pinned`, `pinned_by`, `pinned_at` | A pin endpoint is documented but no column existed |
| `Message.deleted_at`, `deleted_by` | Soft delete is required by the business rules but no column existed |
| `Resource` table | Present in the feature doc and the API, but missing from the table inventory, which appears stale |

---

## 7. Bugs found and fixed during the build

Recorded because several were only visible by running the code, not by reading
it. They are already fixed and committed.

1. **PDO turned PHP `false` into an empty string**, which MySQL rejected for
   integer columns. This broke creating an assignment with late submission
   disabled, and would have broken semesters, users and sections too. Fixed
   centrally in `Model::normalise()`.
2. **A method signature clash took down the whole API.** `ChatMember::find()`
   was incompatible with the inherited `Model::find()`. PHP raises a class load
   fatal for this, and because `routes/api.php` instantiates every controller at
   boot, every endpoint including login returned a fatal error. `php -l` does
   not detect this.
3. **Instant events vanished from the calendar.** Assignment deadlines have the
   same start and end time. The range query used `end > from`, so a deadline
   landing exactly on the first instant of a month view was silently dropped.
   Changed to `end >= from`.
4. **Reused named placeholder.** `LIKE :term OR LIKE :term` fails under native
   prepared statements. Split into two placeholders.
5. **MySQL rejects a CHECK constraint** on a column that a foreign key with a
   referential action also uses. The self prerequisite rule moved into
   `CourseService`.
6. **Touch targets below the minimum.** Calendar day cells were 41px and the
   skip link 42px, against a 44px minimum. Both fixed.
7. **A Cyrillic character** had been typed into a hex colour value, which would
   have silently broken a dark mode border.

---

## 8. Known gaps

Honest list of what is not done.

- **Reminders are never delivered.** Calendar reminders are stored and can be
  queried, but nothing dispatches them. That needs a cron job or scheduler.
- **Transcript PDF download is not implemented.**
  `GET /api/v1/transcript/{id}/download` is documented and needs DomPDF.
- **The Python AI service does not exist.** Face verification is wired and will
  work when it does.
- **Email is not configured.** Password reset and verification emails are
  written and will send once SMTP credentials are in `.env`. Until then they
  fail silently and are logged.
- **Phase 2 modules are not started:** Finance, Food Court, AI Examination,
  Reports, Download Center, Student Activities, Notification Center.
- **`docs/ARCHITECTURE/03-Backend-Architecture.md` still describes Node.js** and
  contradicts the rest of the documentation.

---

## 8b. Test suite

112 tests, 194 assertions, covering the unit, integration and security levels
required by `docs/PROJECT/013-Testing-Strategy.md`.

```bash
cd backend && composer test
```

The suite builds a separate `nextgen_university_test` database from the files in
`database/schema/`, so development data is never touched and schema drift breaks
the tests.

Coverage by level:

- **Unit** (33) — validation rules, Haversine distance, iCalendar export and parse
- **Integration** (67) — enrolment rules, attendance rules, LMS rules, calendar
  synchronisation, chat membership lifecycle
- **Security** (12) — quiz answers never reaching students, enrolment scoped
  content access, cross lecturer denial, cross user isolation, SQL injection,
  and a class load check that `php -l` cannot perform

Each of the bugs listed in section 7 has a named regression test. The suite was
checked by reintroducing two of those bugs and confirming the matching tests
failed, so a green run means something.

---

## 9. Suggested next steps

1. Push and open a pull request so your friend can review.
2. Correct the stale Node.js architecture document.
3. Decide the Bootstrap question before building more frontend.
4. Add frontend tests with Vitest. The backend is covered; the React side is not.
5. Start Phase 2.

---

## 10. Verification performed

For the record, every rule below was confirmed with a real request against a
real database, not assumed.

Authentication: login issues a JWT, wrong password returns 401, account locks
after repeated failures, permissions load per role, sessions revoke.

Academic: prerequisites enforced, duplicate registration rejected, credit limit
enforced, section capacity respected, timetable clash detected between a
09:00-11:00 class and a 10:00-12:00 class on the same day, coordinator approval
and rejection release seats correctly.

Attendance: QR scan records attendance, duplicate scan rejected, expired and
invalid tokens rejected, GPS distance computed correctly at 50m and 2001.5m
against a 150m radius, out of range scan refused, students not enrolled refused,
excuse approval flips the attendance record to Excused, a text file renamed to
look like a document was rejected with nothing written to disk.

LMS: quiz correct answers never reach students, objective questions auto marked
while essays wait for a human, deadlines enforced with an opt in late window,
marks cannot exceed the assignment total, grades hidden until published then
locked, hidden materials invisible to students, students cannot read content
from courses they are not enrolled in.

Calendar: sync run twice produced no duplicates, generated events are read only,
another user's event returns 404 rather than 403, ICS export re-imported
successfully, reminders must precede their event.

Chat: non members refused, students can delete only their own messages, soft
delete preserves the audit row while withholding content, only lecturers can
pin, membership follows enrolment through approve and drop, and a message sent
by another user appeared in an open browser thread without a reload.
