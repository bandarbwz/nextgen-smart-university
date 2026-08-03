# Project Handover

Working record for the NextGen Smart University Platform build.

Last updated: 2026-08-03. Nothing has been pushed to GitHub yet.

---

## 0. Resuming this work in a new conversation

**Nothing is stored in the assistant's memory of the conversation. Everything
is on disk.** The code, the schema, the tests and this document are all in git.
A new chat can pick up exactly where the last one stopped.

To resume, open a new session in this project folder and say something like:

> Continue the NextGen Smart University project. Read HANDOVER.md first.

That is enough. The assistant also keeps a persistent project memory outside the
repository that survives between chats, so it will already know the decisions,
the bugs found, and where things stopped.

To see the state yourself at any time:

```bash
cd ~/Desktop/nextgen-smart-university && git log --oneline && git status
```

---

## 1. Current state

Phase 1 is complete and tested. Phase 2 is four modules of five.

| Phase | Module | Backend | Frontend | Tests |
|-------|--------|---------|----------|-------|
| 1 | Authentication | Done | Done | Yes |
| 1 | Academic | Done | Done | Yes |
| 1 | Attendance | Done | Done | Yes |
| 1 | LMS | Done | Done | Yes |
| 1 | Calendar | Done | Done | Yes |
| 1 | Chat | Done | Done | Yes |
| 2 | Finance | Done | Done | Yes |
| 2 | Food Court | Done | Done | Yes |
| 2 | Reports | Done | Done | Yes |
| 2 | Download Center | Done | Done | Yes |
| 2 | AI Examination | Not started | Not started | No |

Totals: 56 database tables, 150 backend PHP files, 51 frontend files,
183 tests with 341 assertions, 16 commits.

**All 16 commits are one chain on `feature/reports-download-center`.** Each
branch was cut from the previous one, so that single branch contains
everything. There is no need to push seven branches.

---

## 2. Pushing to GitHub

Nothing is pushed. `main` on GitHub is still at the documentation commit, so
your friend has seen none of this yet.

```bash
cd ~/Desktop/nextgen-smart-university
git push -u origin feature/reports-download-center
```

Then open a pull request into `main` on GitHub.

To push every branch separately so each module can be reviewed on its own:

```bash
cd ~/Desktop/nextgen-smart-university
for branch in feature/authentication feature/academic \
              feature/frontend-auth-academic feature/attendance \
              feature/lms feature/calendar feature/chat \
              feature/tests feature/finance feature/food-court \
              feature/reports-download-center; do
  git push -u origin "$branch"
done
```

Note: the GitHub CLI on this machine is authenticated as `SHAFOO11` while the
repository belongs to `bandarbwz`. Confirm you have push access first.

Pushing is the only thing protecting this work against losing the laptop. The
repository is currently the single copy.

---

## 3. Running the project from scratch

PHP 8.5, Composer and MySQL 8 were installed through Homebrew during this work.

```bash
brew services start mysql
```

### Backend

```bash
cd backend
composer install
cp .env.example .env
```

`backend/.env` is deliberately not committed. Generate a JWT secret and paste
it into `JWT_SECRET`:

```bash
openssl rand -hex 32
```

Without a secret the API returns 500 on every request by design rather than
falling back to an insecure default.

### Database

Run the schema files in order, then the seeds, then the migration:

```bash
cd ~/Desktop/nextgen-smart-university
for f in database/schema/*.sql; do mysql -u root < "$f"; done
for f in database/seed/*.sql; do mysql -u root < "$f"; done
mysql -u root < database/migrations/01-backfill-course-chat-rooms.sql
```

The seeds create roles, permissions, faculties, departments, programmes,
semesters and courses. They do **not** create user accounts.

### Running

```bash
php -S 127.0.0.1:8000 -t backend/public backend/public/index.php
```

```bash
cd frontend && npm install && npm run dev
```

API on `http://127.0.0.1:8000`, frontend on `http://localhost:5173`.

### Tests

```bash
cd backend && composer test
```

The suite builds a separate `nextgen_university_test` database from
`database/schema/` on every run and drops it afterwards. Development data in
`nextgen_university` is never touched.

---

## 4. Test accounts

Local database only. These are **not** in the seed files, so rebuilding the
database removes them. Password for all: `Password123!`

| Email | Role |
|-------|------|
| `admin@nextgen.edu` | Administrator |
| `lecturer@nextgen.edu` | Lecturer |
| `other@nextgen.edu` | Lecturer, used to test cross lecturer denial |
| `student@nextgen.edu` | Student, STU001 |
| `owner@nextgen.edu` | Restaurant Owner |

To recreate one:

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
backend/app/
  Controllers/   thin, validate then delegate
  Models/        PDO data access, extend Model
  Services/      all business logic
  Middleware/    AuthMiddleware, RoleMiddleware
  Validation/    per module validators
  Helpers/       Database, Router, Request, Response, Config, Logger, FileUpload
backend/tests/   Unit, Integration, Security suites
frontend/src/    components, layouts, pages, services, contexts, styles
database/        schema, seed, migrations
```

Design system output from the ui-ux-pro-max skill is committed at
`design-system/nextgen-smart-university/MASTER.md`.

---

## 6. Decisions taken, and why

Places where a judgement call was made. Each is worth being able to explain.

**Backend is native PHP 8.4 MVC, not Laravel or Node.** The technology stack
document, the README and the folder structure document all specify native PHP
with PDO. `docs/ARCHITECTURE/03-Backend-Architecture.md` contradicts them by
describing Node.js, Express and Prisma. That file looks like a leftover from a
different template and should be corrected.

**Frontend does not use Bootstrap 5.** The stack document names Bootstrap, but
the UI is built on design tokens instead. If Bootstrap is a hard requirement
for marking, this needs reworking. **Still undecided.**

**Chat uses short polling, not Socket.IO.** A native PHP backend cannot host
Socket.IO. The client polls every five seconds with an `after_id` cursor.
Describe it as polling, not real time.

**Face verification is not stubbed.** It calls a configurable `AI_SERVICE_URL`
and returns 503 when absent, rather than faking a security control.

**Tokens are stored hashed.** Session and refresh tokens are SHA-256 hashed.
QR attendance tokens are the exception, stored in plain text because the
lecturer must display the code; they expire in ten minutes.

**Order totals and invoice amounts are computed server side.** The client sends
identifiers and quantities only. A tampered price in the payload is ignored.

**Report permissions live in one catalogue.** Running a report and exporting it
both check the same table, so export cannot be used as a bypass.

### Two API specifications were missing and were written during the build

`docs/API/09-Finance-API.md` was a byte for byte duplicate of the AI
Examination specification. `docs/API` had no Reports specification at all.
Both were written from their feature documents.

**Still broken:** `docs/API/06-Student-Activities-API.md` is a byte for byte
duplicate of the Chat specification. The Student Activities endpoint contract
does not exist and must be written before that module is built.

### Additions and renames beyond the documented schema

| Change | Reason |
|--------|--------|
| `User.password_reset_token`, `password_reset_expires_at`, `email_verification_token` | Reset and verification endpoints documented with nowhere to store tokens |
| `QuizOption` table | `QuizQuestion` had `correct_answer` but nowhere to store the choices |
| `QuizAnswer` table | `QuizSubmission` stored only a score, but Essay and Short Answer need human grading |
| `Message.pinned`, `deleted_at`, `deleted_by` | Pin endpoint and soft delete rule documented with no columns |
| `Resource` table | In the feature and API documents, missing from the table inventory |
| `Order` renamed to `FoodOrder` | `ORDER` is a reserved word in SQL |
| `Payment` renamed to `OrderPayment` | `Payment` already existed in the Finance module |

The feature documents also reference a **Finance Staff** role that does not
exist among the six defined roles. Finance endpoints are Administrator only
until that role is added.

---

## 7. Bugs found and fixed

Several were only visible by running the code, not by reading it. All are fixed
and each has a regression test.

1. **PDO turned PHP `false` into an empty string**, which MySQL rejected for
   integer columns. Broke creating an assignment with late submission
   disabled, and would have broken semesters, users and sections. Fixed
   centrally in `Model::normalise()`.
2. **A method signature clash took down the whole API.** `ChatMember::find()`
   was incompatible with the inherited `Model::find()`. PHP raises a class load
   fatal, and because `routes/api.php` builds every controller at boot, every
   endpoint including login failed. `php -l` cannot detect this, so
   `ClassIntegrityTest` now loads every class.
3. **Payments were counted twice.** MySQL evaluates `UPDATE` assignments left
   to right, so the balance expression read the already incremented paid
   amount. Paying an invoice in full produced a negative balance. Now computed
   in PHP against a locked row.
4. **Instant events vanished from the calendar.** Assignment deadlines have the
   same start and end time, and the range query used `end > from`, so a
   deadline on the first instant of a month view disappeared.
5. **LIKE wildcards were not escaped.** Searching for a percent sign returned
   every row. Found by a security test.
6. **Reused named placeholder** in course search failed under native prepared
   statements.
7. **MySQL rejects a CHECK constraint** on a column also used by a foreign key
   with a referential action.
8. **Touch targets below the minimum**, calendar cells at 41px and the skip
   link at 42px against a 44px minimum.
9. **A Cyrillic character** typed into a hex colour value.

---

## 8. Test suite

183 tests, 341 assertions.

```bash
cd backend && composer test
```

- **Unit** — validation rules, Haversine distance, iCalendar export and parse
- **Integration** — enrolment, attendance, LMS, calendar sync, chat
  membership, finance, food court, reports, download center
- **Security** — quiz answers never reaching students, enrolment scoped
  content access, cross lecturer denial, cross user isolation, SQL injection,
  and a class load check that `php -l` cannot perform

The suite was checked by reintroducing two fixed bugs and confirming the
matching tests failed, so a green run means something. Export tests assert real
output: the PDF carries the PDF magic bytes and the spreadsheet is a valid zip
archive.

---

## 9. Known gaps

- **AI Examination module is not built.** It is the last Phase 2 module and
  depends on the Python service.
- **The Python AI service does not exist.** Face verification is wired and will
  work once it does.
- **Reminders are never delivered.** Calendar reminders are stored and can be
  queried, but nothing dispatches them. Needs a cron job.
- **Email is not configured.** Password reset and verification emails will send
  once SMTP credentials are in `.env`. Until then they fail and are logged.
- **No frontend tests.** The backend is covered, the React side is not.
- **`docs/API/06-Student-Activities-API.md` is a duplicate** of the Chat
  specification and must be written before that module is built.
- **`docs/ARCHITECTURE/03-Backend-Architecture.md` still describes Node.js** and
  contradicts the rest of the documentation.
- **Student Activities, Notification Center, Assessment, Grade Approval, Reset
  Examination, Settings, System and Role Management modules** are documented but
  not built.

---

## 10. Suggested next steps

1. **Push.** This laptop is currently the only copy.
2. Build the AI Examination module to finish Phase 2.
3. Correct the stale Node.js architecture document.
4. Decide the Bootstrap question before building more frontend.
5. Add frontend tests with Vitest.
6. Write the missing Student Activities API specification.
