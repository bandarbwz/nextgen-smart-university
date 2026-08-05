# NextGen Smart University — how to pick this back up

Updated 2026-08-05. Keep this file on the Desktop.

Everything is safe. The code lives in git on this Mac **and** on GitHub. A chat
ending loses the conversation, never the work.

---

## 1. Copy this into a new chat

Everything between the lines is the prompt. Paste it as your first message.

---

Continue the NextGen Smart University project at
`~/Desktop/nextgen-smart-university`.

Read `HANDOVER.md` in the repository root first. It has the full state, every
decision taken and why, the bugs found and fixed, and the known gaps.

Where things stand: all 19 functional modules are built and tested, and
everything is merged into `main` on GitHub. The coding phase is finished. The
next piece of work is the **UI redesign**, which I will specify myself.

Before writing code, run `cd backend && composer test` and confirm 370 tests
pass, so we know the starting point is green.

My coding preferences: split code into separate files by responsibility, almost
no comments unless something genuinely needs explaining, clean readable spacing,
nothing clever for its own sake.

Never add `Co-Authored-By: Claude` to commits.

---

## 2. Where we stopped

| # | Module | State |
|---|--------|-------|
| 1 | Authentication | Done, tested |
| 2 | Academic | Done, tested |
| 3 | Attendance | Done, tested |
| 4 | LMS | Done, tested |
| 5 | Calendar | Done, tested |
| 6 | Chat | Done, tested |
| 7 | Finance | Done, tested |
| 8 | Food Court | Done, tested |
| 9 | Reports | Done, tested |
| 10 | Download Center | Done, tested |
| 11 | AI Examination | Done, tested |
| 12 | Student Activities | Done, tested |
| 13 | Notification Center | Done, tested |
| 14 | Assessment System | Done, tested |
| 15 | Grade Approval | Done, tested |
| 16 | Reset Examination | Done, tested |
| 17 | Role Management | Done, tested |
| 18 | Settings | Done, tested |
| 19 | System | Done, tested |

Plus the three Administration pages (Students, Lecturers, Sections), which were
broken links until they were built.

**83 database tables. 333 tests, 572 assertions, all passing.**
200 backend PHP files, 82 frontend files, 15 schema files.

---

## 3. First thing to do in the new chat

Everything is merged. There are no open pull requests and nothing half finished.
Just make sure the local copy is current:

```bash
cd ~/Desktop/nextgen-smart-university && git checkout main && git pull
```

---

## 4. Running it

```bash
brew services start mysql
```

```bash
cd ~/Desktop/nextgen-smart-university/backend
composer install
cp .env.example .env
openssl rand -hex 32
```

Paste that output into `JWT_SECRET` in `backend/.env`. Without a secret the API
returns 500 on every request by design, rather than falling back to something
insecure.

```bash
cd ~/Desktop/nextgen-smart-university
for f in database/schema/*.sql; do mysql -u root < "$f"; done
for f in database/seed/*.sql; do mysql -u root < "$f"; done
for f in database/migrations/*.sql; do mysql -u root < "$f"; done
```

```bash
php -S 127.0.0.1:8000 -t backend/public backend/public/index.php
```

```bash
cd frontend && npm install && npm run dev
```

API on `http://127.0.0.1:8000`, app on `http://localhost:5173`.

Tests:

```bash
cd backend && composer test
```

The suite builds a separate `nextgen_university_test` database on every run and
drops it afterwards. Development data is never touched.

---

## 5. Test accounts

Password for all: **`Password123!`**

| Email | Role |
|-------|------|
| `student@nextgen.edu` | Student |
| `lecturer@nextgen.edu` | Lecturer |
| `other@nextgen.edu` | Lecturer, used to test cross lecturer denial |
| `coordinator@nextgen.edu` | Coordinator |
| `stad@nextgen.edu` | STAD Staff |
| `admin@nextgen.edu` | Administrator |
| `owner@nextgen.edu` | Restaurant Owner |

These live **only in the local database** and disappear if it is rebuilt.
`HANDOVER.md` section 4 has the snippet to recreate them.

---

## 6. What is left

### The UI redesign

This is the next piece of work and the only one waiting on you. All 19 modules
are built, so nothing is blocking a redesign now. Say what you want changed and
it gets changed; the design tokens live in `frontend/src/styles/tokens.css` and
every component reads from them, so a global change is one file.

**Bootstrap is settled.** `docs/PROJECT/004-Technology-Stack.md` used to name
Bootstrap 5. You cancelled it as project supervisor on 2026-08-05 and the
document now records that decision and the four reasons behind it. Custom design
tokens are the approved approach. No rework is owed.

### Other gaps

- **No frontend tests.** 370 on the backend, zero on React. Vitest is the
  obvious choice.
- **Calendar reminders are never delivered.** They are stored and queryable, but
  nothing dispatches them. Needs a cron job.
- **Email is not configured.** `MAIL_HOST` points at Mailtrap but the username
  and password in `backend/.env` are empty, so nothing sends. The System page
  reports this honestly rather than showing a green tick.
- **The Python AI service does not exist.** Face, eye and head pose checks return
  an honest 503. Tab and fullscreen monitoring need no AI and work today.
- **Push and SMS notifications** return 501. No provider is configured.

---

## 7. Things worth knowing before touching the code

These cost real time to discover. They are all in `HANDOVER.md` too.

**PDO turns PHP `false` into an empty string**, which MySQL rejects for integer
columns. Handled centrally in `Model::normalise()`. If a new model bypasses the
base `create` or `update`, cast booleans there too.

**`php -l` cannot catch a method signature clash with a parent class.** That is a
class load fatal, and because `routes/api.php` builds every controller at boot,
one bad signature takes down every endpoint including login.
`ClassIntegrityTest` loads every class to catch it.

**MySQL evaluates `UPDATE` assignments left to right**, so a later expression
sees columns already written by an earlier one. This double counted every
payment once. Compute in PHP against a locked row instead.

**Test against the real interface, not only curl.** Two bugs hid from curl and
appeared immediately in a browser: a real user agent overflowed a column and
turned starting an examination into a 500, and a stale notification badge
disagreed with the page beside it.

**Three table names had to change** because of collisions: `Order` to
`FoodOrder`, `Payment` to `OrderPayment`, and `Announcement` to
`SystemAnnouncement`.

---

## 8. Two reminders

**Your commits are yours.** The history was rewritten on 2026-08-04 so every
commit is authored by your GitHub account (SHAFOO11) and no commit mentions
Claude. Keep it that way. If you clone fresh, set the identity first:

```bash
git config user.email "sshaaarif1@gmail.com"
```

**Tell bandarbwz to pull.** `main` has moved a long way. If he has an old clone:

```bash
git fetch origin && git reset --hard origin/main
```
