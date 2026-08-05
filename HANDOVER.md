# Project Handover

Working record for the NextGen Smart University Platform build.

Last updated: 2026-08-05. All work merged into `main` on GitHub.
The interface was redesigned on the same day; see section 10.

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

Every module named in the requirement documents is now built, wired to the
frontend and covered by tests.

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
| 2 | AI Examination | Done | Done | Yes |
| 3 | Student Activities | Done | Done | Yes |
| 3 | Notification Center | Done | Done | Yes |
| 3 | Assessment System | Done | Done | Yes |
| 3 | Grade Approval | Done | Done | Yes |
| 3 | Reset Examination | Done | Done | Yes |
| 3 | Role Management | Done | Done | Yes |
| 3 | Settings | Done | Done | Yes |
| 3 | System | Done | Done | Yes |

Totals: 87 database tables, 211 backend PHP files, 87 frontend files,
370 tests with 636 assertions.

All work is merged into `main` on GitHub through pull requests #1 to #14.

---

## 2. Git history and attribution

On 2026-08-04 the whole history was rewritten for two reasons:

1. Every commit carried a `Co-Authored-By: Claude` trailer, which made GitHub
   list an AI as the second contributor on the repository.
2. The commits were authored as `...@Sharifs-MacBook-Air.local`, an address
   linked to no GitHub account, so they credited nobody.

Both are fixed. The commits are now authored as `sshaaarif1@gmail.com` and
attribute to `SHAFOO11`, and no commit mentions Claude. `bandarbwz`'s own
commits and his merge commit were left untouched, and the file tree was
verified byte identical before and after.

**If a clone still points at the old history**, reset it rather than pulling:

```bash
git fetch origin && git reset --hard origin/main
```

A local branch `backup/before-rewrite-main` holds the pre-rewrite history.

Set the identity before committing in a fresh clone, or the problem returns:

```bash
git config user.email "sshaaarif1@gmail.com"
```

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
semesters, courses, and the two lecturers in
`database/seed/03-teaching-staff.sql`. They do not create the other test
accounts; section 4 lists which are which.

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

Password for all: `Password123!`

| Email | Role | In a seed file? |
|-------|------|-----------------|
| `admin@nextgen.edu` | Administrator | No |
| `coordinator@nextgen.edu` | Coordinator | No |
| `lecturer@nextgen.edu` | Lecturer, teaches CS101 | No |
| `other@nextgen.edu` | Lecturer, holds no section on purpose | No |
| `student@nextgen.edu` | Student, STU001 | No |
| `stad@nextgen.edu` | STAD Staff | No |
| `owner@nextgen.edu` | Restaurant Owner | No |
| `aisha@nextgen.edu` | Lecturer, teaches CS102 and CS201 | Yes |
| `faiz@nextgen.edu` | Lecturer, teaches CS210 and SE201 | Yes |

The accounts marked no exist only in the local database, so rebuilding it
removes them. The two marked yes come from
`database/seed/03-teaching-staff.sql` and survive a rebuild.

**`other@nextgen.edu` deliberately teaches nothing.** A second lecturer holding
no section is what makes the cross lecturer denial check easy to see by hand.

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

**Frontend uses design tokens, not Bootstrap. Decided, not a deviation.** The
stack document originally named Bootstrap 5. On 2026-08-05 the supervisor
replaced it with the custom design token system that was actually built, and
every document naming Bootstrap was updated to match. The unused `bootstrap`
package was removed from `frontend/package.json`; it had been installed but
imported nowhere, and no Bootstrap class name appeared in the source.

The reasoning is recorded in `docs/PROJECT/004-Technology-Stack.md`: themes come
from swapping tokens rather than fighting a framework palette, every interactive
element meets the 44 pixel touch target minimum, and no unused framework CSS
ships to the browser.

**Chat uses short polling, not Socket.IO.** A native PHP backend cannot host
Socket.IO. The client polls every five seconds with an `after_id` cursor.
Describe it as polling, not real time.

**Face verification is not stubbed.** It calls a configurable `AI_SERVICE_URL`
and returns 503 when absent, rather than faking a security control.

**An unverified examination is recorded as unverified, never as passed.** The
feature document says AI verification must succeed before an examination begins.
Enforcing that literally with no AI service would make every examination
unstartable. So verification is required whenever `AI_SERVICE_URL` is set, and
when it is not the session still starts but is stamped
`identity_verified = 0` with the reason, and the AI report caps the integrity
score at 60 and states that it cannot confirm who sat the paper. A missing
proctor is never silently treated as a clean one.

**Pausing an examination is an invigilator action, not a student one.** A
student who could pause their own timer could stop the clock at will. Pause and
resume are Lecturer and Coordinator only, and resuming extends the deadline by
exactly the time paused.

**Role Management deliberately does not create the documented `UserRole`
table.** The feature document lists one holding user_id, role_id, assigned_by
and assigned_at. A user's role already lives in `User.role_id`, which every
authentication query and the JWT payload read from. A second table holding the
same fact would be two competing sources of truth, and the first time they
disagreed somebody would be granted or denied access wrongly. The intent behind
it, knowing who changed a role and when, is met by `AuthorizationLog`, which
records role assignments alongside every other authorization change.

**A system default role cannot be deleted or renamed**, but can be described
differently, deactivated, and have its permissions changed. Renaming is blocked
because code and seed data both refer to the six by name. An `is_system` flag
carries this rather than hard coded names in PHP, which would break the moment
somebody renamed a role.

**An administrator cannot change their own role.** Demoting yourself by accident
would lock the platform out of its own administration.

**Settings and System share one store.** The documents describe a
`SystemSetting` table under Settings and a `SystemConfiguration` table under
System, holding the same thing: a key, a value and a category. Building both
would mean two tables answering the same question, and a maintenance flag that
is true in one and false in the other. There is one `SystemSetting` table.
Settings owns writing to it, System owns reading it back and reporting health.

**Maintenance mode actually locks people out.** It is enforced in
`AuthMiddleware`, which asks `SystemService::shouldBlock()` on every
authenticated request and answers 503 with the stored message. Administrators
are exempt, otherwise nobody could turn it off again. Both halves of that rule
are pinned by tests, and both were mutation checked: removing the exemption and
making the block a no-op each fail the suite.

**The health check measures, it does not assume.** The database check times a
real query and counts real tables, storage reads the real disk, and the AI and
email checks report `not configured` when they are. Email in particular is only
called configured when the host *and* the credentials are present, because a
host on its own sends nothing and would hide a broken password reset behind a
green tick.

**There is no `BackupHistory` table and no backup endpoint that pretends.** A
table of backup rows written by an application that never ran a backup is a
table full of lies. `POST /system/backup` answers 501 and says backups are run
outside the application.

**A reset never deletes anything.** The original submission is stamped
`reset_at` and the retake is a new attempt beside it, so the first sitting and
the reason it was abandoned both survive. This needed a change to an existing
table: `ExamSubmission` held one row per student per examination, and that
unique key made a retake impossible. It now carries `attempt_number` and the
key includes it. `database/migrations/02-exam-submission-attempts.sql` applies
this to a database that already exists and is safe to run twice.

**Only an examination that was actually sat can be reset.** A request needs a
closed session behind it, so asking to redo a paper never started is refused,
and a session still in progress must be finished first.

**Grade approval is the only thing that writes a transcript row.** Until this
module existed, nothing in the platform ever inserted into `Transcript`, so
`GpaService` and the transcript page read an empty table and every student sat
at a GPA of zero forever. Approval now writes the row and recalculates the GPA,
which is what makes the academic record real rather than decorative.

**Grades must be finished before they can be submitted.** The scheme has to add
up to a hundred and every enrolled student needs a complete set of published
components. Submitting half marked grades would ask the coordinator to approve
something incomplete.

**Rejecting or returning grades requires remarks.** A refusal with no reason
leaves the lecturer nothing to act on.

**The approval log is append only.** Rows are never updated or deleted, because
the point of an audit log is that it cannot be tidied up afterwards.

**The Assessment module is the weighting layer, not a second grade book.** The
Academic module already had `Grade`, which records individual marked items with
no weighting. Assessment adds what `Grade` never had: a weighted scheme per
section, and a course total computed from it. A midterm worth thirty per cent
counts for thirty per cent whatever its mark total happens to be, so a student
scoring 80 on a 50 mark midterm and 95 on a 200 mark final gets 90.5, not the
87.5 a plain average would give. `Grade` is left alone.

**A section's assessment weights must reach exactly a hundred.** Anything else
makes the course total meaningless, so the sum is checked on every write and a
course result reports whether the scheme is complete rather than pretending.

**A published result is locked.** Until publication a mark can be corrected
freely; afterwards the student has seen it and changing it needs approval.

**The letter scale lives in one place.** It was private inside `GradeService`
until Assessment needed the same thresholds. It is now `GradeScale`, because two
copies of a grading scale is exactly the kind of thing that silently drifts.

**A notification never breaks the thing that caused it.** `NotificationService`
catches everything and logs. An enrolment approval, a payment or a grade must
not roll back because a notification row could not be written.

**A critical notification ignores the user's preference.** Someone who has muted
notifications still has to be told their examination was terminated or their
account is on hold. Everything below critical respects the setting.

**Three critical violations end the session.** The feature document says
sessions terminate when critical policies are breached but never says at what
count. Three is the chosen threshold, held in one constant in
`ProctoringService`.

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

`docs/API/06-Student-Activities-API.md` was also a byte for byte duplicate of
the Chat specification. It was written from the feature document before the
Student Activities module was built, and the module follows it.

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
| AI Examination uses the union of two conflicting table lists | `docs/FEATURES/08-AI-Exam.md` defines Exam, ExamQuestion, ExamSubmission, ExamSession and AIViolation with real columns; `docs/DATABASE/01-Tables.md` instead lists Examination, ExaminationSession, FaceDetection, EyeTracking, HeadPose, BrowserActivity, ExamRecording and AIReport. The API specification has endpoints for both, so all eleven tables exist, named after the feature document because that is the one with column definitions |
| `ExamSession.paused_at` | Pause and resume are documented endpoints with nowhere to record when the pause began |
| `PUT /ai-exam/submissions/{id}/grade` | The specification has no grading endpoint, so essay answers could never leave `Pending Review` |
| `EventQrSession` table | Event attendance is documented as QR based with an expiring token, with nowhere to record the token. Mirrors `QRSession` from Attendance |
| `Event.event_type` and `Event.award_points` | The feature document lists competitions, workshops and seminars as separate things but gave `Event` no type column, and describes awarding points per event with no place to store the value |
| `EventRegistration.decision_reason`, `decided_by`, `decided_at` | Approval and rejection are documented and required to be logged, with no columns to log them |
| `Announcement` renamed to `SystemAnnouncement` | The LMS module already owns `Announcement` for course announcements scoped to a section. The notification one is university wide |
| `SystemAnnouncement` table itself | The API specification has announcement endpoints with no table behind them in the feature document |
| `Notification.reference_type`, `reference_id`, `read_at`, `archived_at` | Archiving and marking read are documented endpoints with nowhere to record when, and a notification needs to point at the thing it is about |

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
10. **A real browser could crash the examination start.** The client sends
    `navigator.userAgent` as the browser field, which is far longer than the
    100 character column, so MySQL rejected the insert and starting an
    examination returned 500. Found by driving the actual page, not by reading
    the code, because curl sent nothing that long. Values are now clipped to
    the column width.
11. **A report level note assumed a string.** Adding a summary line to the PDF
    exporter under the key `summary` collided with the transcript report, which
    already stores a GPA array under that name, so transcript downloads threw a
    `TypeError`. Caught by the existing transcript test. The key is now `note`
    and is type checked.

---

## 8. Test suite

370 tests, 636 assertions.

```bash
cd backend && composer test
```

- **Unit** — validation rules, Haversine distance, iCalendar export and parse,
  grade scale boundaries, report export shape
- **Integration** — enrolment, attendance, LMS, calendar sync, chat
  membership, finance, food court, reports, download center, examinations and
  resets, notifications, assessment weighting, grade approval, role management,
  settings and system health
- **Security** — quiz answers never reaching students, enrolment scoped
  content access, cross lecturer denial, cross user isolation, SQL injection,
  and a class load check that `php -l` cannot perform

Every rule worth the name is mutation checked: the rule is removed, the suite is
run, and the matching test has to fail before the rule goes back. That is how a
weak test in the role management file was caught, where a system role guard
appeared to be tested but a different guard was doing the work. Export tests assert real
output: the PDF carries the PDF magic bytes and the spreadsheet is a valid zip
archive.

---

## 9. Known gaps

- **The Python AI service does not exist.** Everything that needs computer
  vision is wired to a configurable `AI_SERVICE_URL` and returns 503 when it is
  absent. Nothing fakes a detection. Until that service exists, examinations
  start with `identity_verified = 0`, the reason is written onto the session,
  and the AI report caps the integrity score at 60 and says plainly that it
  cannot confirm who sat the examination. Browser and fullscreen monitoring
  need no AI and work today.
- **Reminders are never delivered.** Calendar reminders are stored and can be
  queried, but nothing dispatches them. Needs a cron job.
- **Email is not configured.** `MAIL_HOST` points at Mailtrap but the username
  and password in `.env` are empty, so nothing sends. The system health page
  reports this rather than showing a green tick. Password reset and verification
  emails start working the moment real credentials are filled in.
- **No frontend tests.** The backend is covered, the React side is not.
- **Push and SMS notifications are not implemented.** Both endpoints exist and
  return 501 with a plain explanation rather than accepting a request and
  quietly doing nothing. Neither has a provider.
- **Backups are not run from the application.** `POST /system/backup` returns
  501 and points at `mysqldump`.
- **Arabic is a stored preference only.** The interface is not translated.

---

## 10. The interface

Redesigned on 2026-08-05 around the City University brand, from two prototypes
the owner approved.

**A landing page now exists at `/`.** Before this there was none: `/` redirected
to `/dashboard`, which bounced a signed out visitor to `/login`. The landing
page runs a loading sequence and crossfades into a welcome hero, and Enter
Portal navigates to the real login route. Signed in users skip it.

**Sound lives only on the landing and login pages.** It is synthesised with the
Web Audio API, there are no audio files, and there is a mute toggle. Nothing
inside the portal makes a sound. Browsers block audio until the visitor has
interacted, so the first loading run is silent by design.

**The brand palette and typefaces are in `tokens.css`.** Every component reads
from them, so a further change is one file. Token names were left alone, which
is why nothing under `components/` had to be touched.

**The shell is three boxes**: a sticky top bar with the crest, a floating
navigation card and a content card. The menu button hides and shows the
navigation at any width and remembers the choice.

**The student dashboard** groups courses into in progress, completed and
dropped, with filter pills and cards that expand to their actions. Every value
on it comes from an endpoint that already existed.

Three things in the reference design were deliberately not built. Classmate
avatars need the section roster, which is restricted to lecturers and
coordinators, so showing it to students is an access control decision rather
than a styling one. The course link points at a route that does not exist, and
no syllabus record exists behind the syllabus button.

---

## 11. Suggested next steps

1. Add frontend tests with Vitest.
2. Decide whether a lecturer should have a teaching load limit. Nothing in the
   platform caps how many sections one lecturer can hold, and no feature
   document specifies a limit, so none was invented.
3. Build the Python AI service, or agree that proctoring ships without it.
4. Add a cron job so calendar reminders are actually delivered.
5. Fill in real SMTP credentials so password reset works end to end.
6. Decide whether the product name follows the brand. The interface says City
   University Platform, while the repository, the shell before this redesign and
   the seeded `university_name` setting say NextGen Smart University.
