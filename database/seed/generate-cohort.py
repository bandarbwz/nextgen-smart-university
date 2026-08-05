#!/usr/bin/env python3
"""Generates a realistic 500 student cohort as an idempotent SQL seed file."""

import os
import random
from collections import defaultdict

random.seed(20260806)          # fixed, so the file regenerates identically

OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "05-cohort.sql")
PW = "$2y$12$SM035BgYaiapS0y3OfpbQO5FsUKPx.t/.VCpIvsyZGgjgsRmHZE1i"

# ---------------------------------------------------------------- name pools --
MALAY_M = ["Muhammad", "Ahmad", "Amir", "Danial", "Haziq", "Irfan", "Aiman", "Zulkifli",
           "Faiz", "Hakim", "Nabil", "Syafiq", "Rizal", "Azlan", "Khairul", "Iskandar"]
MALAY_F = ["Nur", "Siti", "Aisyah", "Nurul", "Farah", "Hana", "Amirah", "Alia",
           "Syafiqah", "Izzati", "Sofea", "Balqis", "Adriana", "Zahra", "Liyana"]
MALAY_FATHER = ["Abdullah", "Ismail", "Hassan", "Rahman", "Yusof", "Omar",
                "Karim", "Salleh", "Rashid", "Zainal", "Mokhtar", "Othman"]
CHINESE_S = ["Tan", "Lim", "Wong", "Lee", "Chan", "Ng", "Chong", "Goh", "Teo", "Yap", "Low", "Ooi"]
CHINESE_G = ["Wei Ling", "Jia Hao", "Mei Yee", "Zhi Wei", "Xin Yi", "Kai Ming", "Hui Shan",
             "Jun Kit", "Li Ping", "Yong Sheng", "Shu Fen", "Wen Jie"]
INDIAN_G = ["Arjun", "Priya", "Rajesh", "Anitha", "Vikram", "Deepa", "Suresh", "Kavitha",
            "Ramesh", "Meera", "Karthik", "Divya"]
INDIAN_S = ["Subramaniam", "Kumar", "Raj", "Nair", "Pillai", "Menon", "Krishnan", "Rao"]
ARAB_M = ["Omar", "Yusuf", "Bilal", "Tariq", "Kareem", "Idris", "Salim", "Hamza"]
ARAB_F = ["Layla", "Mariam", "Salma", "Rania", "Yasmin", "Huda", "Noor"]
ARAB_S = ["Al-Harbi", "Al-Rashid", "Abdulkadir", "Bawazir", "Al-Amin", "Hassan", "Osman"]


def person():
    kind = random.random()

    if kind < 0.40:
        # pick the gender first, so the given names and the patronymic agree
        if random.random() < 0.5:
            first, second = random.sample(MALAY_M, 2)
            link = "bin"
        else:
            first, second = random.sample(MALAY_F, 2)
            link = "binti"

        given = f"{first} {second}"

        return f"{given} {link} {random.choice(MALAY_FATHER)}"
    if kind < 0.68:
        return f"{random.choice(CHINESE_S)} {random.choice(CHINESE_G)}"
    if kind < 0.85:
        return f"{random.choice(INDIAN_G)} {random.choice(INDIAN_S)}"

    return f"{random.choice(ARAB_M + ARAB_F)} {random.choice(ARAB_S)}"


def email_of(name, number):
    parts = [p for p in name.lower().replace("-", "").split() if p not in ("bin", "binti")]
    stem = ".".join(parts[:2])
    return f"{stem}.{number[-3:]}@student.city.edu.my"


# ------------------------------------------------------------------ catalogue --
# programme -> department -> level -> [(code, name, credits, type)]
PROGRAMMES = {
    "Bachelor of Computer Science": ("Computer Science", "CS", 175),
    "Bachelor of Software Engineering": ("Software Engineering", "SE", 130),
    "Bachelor of Artificial Intelligence": ("Artificial Intelligence", "AI", 95),
    "Bachelor of Electrical Engineering": ("Electrical Engineering", "EE", 60),
    "Bachelor of Accounting": ("Accounting", "AC", 40),
}

CATALOGUE = {
    "CS": {
        1: [("CS111", "Programming Fundamentals", 3), ("CS112", "Discrete Mathematics", 3),
            ("CS113", "Computer Organisation", 3), ("CS114", "Web Technologies", 3)],
        2: [("CS221", "Object Oriented Programming", 3), ("CS222", "Operating Systems", 3),
            ("CS223", "Computer Networks", 3), ("CS224", "Software Design", 3)],
        3: [("CS331", "Distributed Systems", 3), ("CS332", "Information Security", 3),
            ("CS333", "Mobile Application Development", 3), ("CS334", "Cloud Computing", 3)],
        4: [("CS441", "Final Year Project I", 4), ("CS442", "Professional Practice", 2),
            ("CS443", "Advanced Databases", 3), ("CS444", "Research Methods", 3)],
    },
    "SE": {
        1: [("SE111", "Introduction to Software Engineering", 3), ("SE112", "Programming Principles", 3),
            ("SE113", "Systems Analysis", 3), ("SE114", "Technical Communication", 2)],
        2: [("SE221", "Software Architecture", 3), ("SE222", "Quality Assurance and Testing", 3),
            ("SE223", "Human Computer Interaction", 3), ("SE224", "Agile Development", 3)],
        3: [("SE331", "Software Project Management", 3), ("SE332", "DevOps and Deployment", 3),
            ("SE333", "Enterprise Systems", 3), ("SE334", "Secure Software Development", 3)],
        4: [("SE441", "Capstone Project I", 4), ("SE442", "Software Maintenance", 3),
            ("SE443", "Industry Placement", 4), ("SE444", "Emerging Technologies", 3)],
    },
    "AI": {
        1: [("AI111", "Foundations of Artificial Intelligence", 3), ("AI112", "Linear Algebra for AI", 3),
            ("AI113", "Programming for Data Science", 3), ("AI114", "Probability and Statistics", 3)],
        2: [("AI221", "Machine Learning", 4), ("AI222", "Data Mining", 3),
            ("AI223", "Knowledge Representation", 3), ("AI224", "Data Visualisation", 3)],
        3: [("AI331", "Deep Learning", 4), ("AI332", "Natural Language Processing", 3),
            ("AI333", "Computer Vision", 3), ("AI334", "Reinforcement Learning", 3)],
        4: [("AI441", "AI Capstone Project", 4), ("AI442", "Ethics in Artificial Intelligence", 2),
            ("AI443", "Big Data Analytics", 3), ("AI444", "AI in Industry", 3)],
    },
    "EE": {
        1: [("EE111", "Circuit Analysis", 3), ("EE112", "Engineering Mathematics", 3),
            ("EE113", "Digital Electronics", 3), ("EE114", "Engineering Drawing", 2)],
        2: [("EE221", "Signals and Systems", 3), ("EE222", "Microcontrollers", 3),
            ("EE223", "Electromagnetic Fields", 3), ("EE224", "Control Systems", 3)],
        3: [("EE331", "Power Systems", 3), ("EE332", "Embedded Systems", 3),
            ("EE333", "Communication Systems", 3), ("EE334", "Instrumentation", 3)],
        4: [("EE441", "Engineering Project", 4), ("EE442", "Renewable Energy Systems", 3),
            ("EE443", "Industrial Automation", 3), ("EE444", "Engineering Management", 2)],
    },
    "AC": {
        1: [("AC111", "Financial Accounting", 3), ("AC112", "Business Mathematics", 3),
            ("AC113", "Principles of Management", 3), ("AC114", "Business Law", 3)],
        2: [("AC221", "Management Accounting", 3), ("AC222", "Corporate Finance", 3),
            ("AC223", "Taxation", 3), ("AC224", "Auditing", 3)],
        3: [("AC331", "Advanced Financial Reporting", 3), ("AC332", "Forensic Accounting", 3),
            ("AC333", "Accounting Information Systems", 3), ("AC334", "Public Sector Accounting", 3)],
        4: [("AC441", "Accounting Research Project", 4), ("AC442", "Strategic Management", 3),
            ("AC443", "International Accounting", 3), ("AC444", "Professional Ethics", 2)],
    },
}

LEVEL_MIX = [(1, 0.30), (2, 0.28), (3, 0.24), (4, 0.18)]

DEPT_OF_PREFIX = {
    "CS": "Computer Science",
    "SE": "Software Engineering",
    "AI": "Artificial Intelligence",
    "EE": "Electrical Engineering",
    "AC": "Accounting",
}

# --------------------------------------------------------------- generation --
lecturers = []          # (staff_id, name, dept, office, specialisation)
LECT_PER_DEPT = {"Computer Science": 7, "Software Engineering": 6,
                 "Artificial Intelligence": 5, "Electrical Engineering": 4,
                 "Accounting": 3}

n = 0
for dept, count in LECT_PER_DEPT.items():
    for _ in range(count):
        n += 1
        lecturers.append((
            f"STF26{n:03d}",
            "Dr. " + person(),
            dept,
            f"{random.choice('ABCD')}-{random.randint(101, 418)}",
        ))

lect_by_dept = defaultdict(list)
for staff_id, name, dept, office in lecturers:
    lect_by_dept[dept].append(staff_id)

# students
students = []
idx = 0
for programme, (dept, prefix, count) in PROGRAMMES.items():
    for _ in range(count):
        idx += 1
        number = f"CU26{idx:04d}"
        name = person()
        level = random.choices([l for l, _ in LEVEL_MIX], [w for _, w in LEVEL_MIX])[0]
        ability = random.gauss(0, 1)
        students.append({
            "number": number, "name": name, "email": email_of(name, number),
            "programme": programme, "dept": dept, "prefix": prefix,
            "level": level, "ability": ability,
        })

# enrolments: every student takes the four courses of their programme and level
course_roster = defaultdict(list)
for st in students:
    for code, title, credits in CATALOGUE[st["prefix"]][st["level"]]:
        course_roster[(st["prefix"], code, title, credits)].append(st["number"])

# split each course into sections of at most 32
SECTION_CAP = 32
sections = []           # (code, section_number, staff_id, room, capacity, [students])
DAYS = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday"]
SLOTS = [("08:00:00", "10:00:00"), ("10:00:00", "12:00:00"),
         ("13:00:00", "15:00:00"), ("15:00:00", "17:00:00")]

for (prefix, code, title, credits), roster in sorted(course_roster.items()):
    dept = DEPT_OF_PREFIX[prefix]
    pool = lect_by_dept[dept]
    chunks = [roster[i:i + SECTION_CAP] for i in range(0, len(roster), SECTION_CAP)]

    for s, chunk in enumerate(chunks, start=1):
        staff = pool[(len(sections) + s) % len(pool)]
        day = DAYS[(len(sections) + s) % len(DAYS)]
        slot = SLOTS[(len(sections) * 2 + s) % len(SLOTS)]
        sections.append({
            "code": code, "title": title, "credits": credits, "prefix": prefix,
            "number": f"{s:02d}", "staff": staff,
            "room": f"{random.choice('ABCD')}{random.randint(1, 4)}{random.randint(10, 30)}",
            "day": day, "start": slot[0], "end": slot[1],
            "roster": chunk,
        })

SESSIONS = ["2026-08-03", "2026-08-05", "2026-08-10", "2026-08-12", "2026-08-17",
            "2026-08-19", "2026-08-24", "2026-08-26", "2026-08-31", "2026-09-02"]

ASSESSMENTS = [("Quiz", "Quiz 1", 20.0), ("Assignment", "Assignment 1", 100.0),
               ("Midterm", "Midterm Examination", 60.0)]

LETTERS = [(90, "A", 4.00), (85, "A-", 3.70), (80, "B+", 3.30), (75, "B", 3.00),
           (70, "B-", 2.70), (65, "C+", 2.30), (60, "C", 2.00), (55, "C-", 1.70),
           (50, "D", 1.00)]


def letter_for(pct):
    for threshold, letter, points in LETTERS:
        if pct >= threshold:
            return letter, points
    return "F", 0.00


by_number = {st["number"]: st for st in students}
grade_rows = []
attendance_rows = []
enrol_rows = []
student_points = defaultdict(list)
letter_tally = defaultdict(int)

for sec in sections:
    for number in sec["roster"]:
        st = by_number[number]

        enrol_rows.append((number, sec["code"], sec["number"]))

        # attendance: reliability driven by the student, with a few chronic absentees
        reliability = min(0.99, max(0.45, 0.90 + st["ability"] * 0.035 + random.gauss(0, 0.05)))

        for date in SESSIONS:
            roll = random.random()
            if roll < reliability:
                status, method = "Present", random.choice(["QR", "QR", "QR", "GPS"])
            elif roll < reliability + 0.06:
                status, method = "Late", "QR"
            elif roll < reliability + 0.09:
                status, method = "Excused", "Manual"
            else:
                status, method = "Absent", "Manual"

            attendance_rows.append((number, sec["code"], sec["number"], date, status, method))

        # marks: ability shifts the mean, noise keeps it human
        pcts = []
        for kind, title, total in ASSESSMENTS:
            pct = 72 + st["ability"] * 9 + random.gauss(0, 7)
            pct = min(99.0, max(18.0, pct))
            pcts.append(pct)
            marks = round(total * pct / 100, 2)
            letter, points = letter_for(pct)
            letter_tally[letter] += 1
            grade_rows.append((number, sec["code"], sec["number"], kind, title,
                               marks, total, letter, points))

        overall = sum(pcts) / len(pcts)
        letter, points = letter_for(overall)
        student_points[number].append((points, sec["credits"]))

# per student GPA from the marks actually generated
for st in students:
    rows = student_points[st["number"]]
    if rows:
        total_credits = sum(c for _, c in rows)
        st["gpa"] = round(sum(p * c for p, c in rows) / total_credits, 2)
    else:
        st["gpa"] = 0.00

    prior = max(0.0, min(4.0, st["gpa"] + random.gauss(0, 0.18)))
    st["cgpa"] = round((st["gpa"] + prior) / 2, 2) if st["level"] > 1 else st["gpa"]
    st["completed"] = max(0, {1: 0, 2: 30, 3: 62, 4: 94}[st["level"]] + random.choice([0, 0, 3, -3]))
    st["admitted"] = {1: "2026-02-01", 2: "2025-09-01",
                      3: "2024-09-01", 4: "2023-09-01"}[st["level"]]
    st["graduates"] = {1: "2030-02-01", 2: "2029-09-01",
                       3: "2028-09-01", 4: "2027-09-01"}[st["level"]]


def q(value):
    return "'" + str(value).replace("\\", "\\\\").replace("'", "''") + "'"


def batched(rows, size=400):
    for i in range(0, len(rows), size):
        yield rows[i:i + size]


out = []
w = out.append

w("-- Demonstration cohort: 500 students")
w("-- NextGen Smart University Platform")
w("--")
w("-- Generated, not hand written. The platform was verified with a handful of")
w("-- records, which proves a rule but shows nothing. This fills the database")
w("-- with a cohort the size of a real intake so the interface, the reports and")
w("-- the exports have something to display.")
w("--")
w("-- Marks are drawn from a normal distribution around seventy two per cent")
w("-- with a per student ability offset, so the grade spread looks like a real")
w("-- cohort rather than everybody scoring an A. Attendance is driven by the")
w("-- same offset, so a weak student is also more often absent.")
w("--")
w("-- Every row here is identified by a generated key: staff numbers begin")
w("-- STF26 and student numbers begin CU26. The file removes anything carrying")
w("-- those markers before inserting, so running it twice is safe and it never")
w("-- touches the hand made accounts from the earlier seeds.")
w("--")
w("-- Password for every account: Password123!")
w("")
w("USE nextgen_university;")
w("")
w("SET @sem := (SELECT id FROM Semester WHERE status = 'active' LIMIT 1);")
w("SET @coordinator := (SELECT id FROM User WHERE email = 'coordinator@nextgen.edu');")
w("SET @registrar := (SELECT id FROM User WHERE email = 'admin@nextgen.edu');")
w("")

w("-- ------------------------------------------------------------- cleanup --")
w("DELETE FROM User WHERE university_id LIKE 'CU26%';")
generated_codes = sorted({sec["code"] for sec in sections})
codes_sql = ", ".join(q(c) for c in generated_codes)
w(f"DELETE FROM ClassSchedule WHERE section_id IN (SELECT id FROM Section WHERE course_id IN (SELECT id FROM Course WHERE course_code IN ({codes_sql})));")
w(f"DELETE FROM Section WHERE course_id IN (SELECT id FROM Course WHERE course_code IN ({codes_sql}));")
w(f"DELETE FROM Course WHERE course_code IN ({codes_sql});")
w("DELETE FROM User WHERE university_id LIKE 'STF26%';")
w("")

w("-- ----------------------------------------------------------- lecturers --")
rows = ",\n".join(
    f"    ((SELECT id FROM Role WHERE name = 'Lecturer'), {q(name)}, {q(sid)}, "
    f"{q(sid.lower() + '@city.edu.my')}, {q(PW)}, 'active', TRUE)"
    for sid, name, dept, office in lecturers
)
w("INSERT INTO User (role_id, full_name, university_id, email, password, status, email_verified) VALUES")
w(rows + ";")
w("")

rows = ",\n".join(
    f"    ((SELECT id FROM User WHERE university_id = {q(sid)}), "
    f"(SELECT faculty_id FROM Department WHERE name = {q(dept)}), "
    f"(SELECT id FROM Department WHERE name = {q(dept)}), {q(office)}, 'full_time')"
    for sid, name, dept, office in lecturers
)
w("INSERT INTO Lecturer (user_id, faculty_id, department_id, office, employment_status) VALUES")
w(rows + ";")
w("")

w("-- ------------------------------------------------------------- courses --")
course_defs = {}
for sec in sections:
    course_defs[sec["code"]] = (sec["title"], sec["credits"], sec["prefix"],
                                int(sec["code"][2]))
rows = ",\n".join(
    f"    ((SELECT id FROM Department WHERE name = {q(DEPT_OF_PREFIX[prefix])}), "
    f"(SELECT id FROM Program WHERE name = {q([p for p, v in PROGRAMMES.items() if v[1] == prefix][0])}), "
    f"{q(code)}, {q(title)}, {credits}, 'Core', {level}, 'active')"
    for code, (title, credits, prefix, level) in sorted(course_defs.items())
)
w("INSERT INTO Course (department_id, program_id, course_code, course_name, credit_hours, course_type, level, course_status) VALUES")
w(rows + ";")
w("")

w("-- ------------------------------------------------------------ sections --")
rows = ",\n".join(
    f"    ((SELECT id FROM Course WHERE course_code = {q(s['code'])}), "
    f"(SELECT l.id FROM Lecturer l JOIN User u ON u.id = l.user_id WHERE u.university_id = {q(s['staff'])}), "
    f"@sem, {q(s['number'])}, {q(s['room'])}, 'Physical', {SECTION_CAP}, {len(s['roster'])}, 'open')"
    for s in sections
)
w("INSERT INTO Section (course_id, lecturer_id, semester_id, section_number, classroom, delivery_mode, capacity, registered_students, status) VALUES")
w(rows + ";")
w("")

rows = ",\n".join(
    f"    ((SELECT s.id FROM Section s JOIN Course c ON c.id = s.course_id "
    f"WHERE c.course_code = {q(s['code'])} AND s.section_number = {q(s['number'])}), "
    f"{q(s['day'])}, {q(s['start'])}, {q(s['end'])}, {q(s['room'])})"
    for s in sections
)
w("INSERT INTO ClassSchedule (section_id, day_of_week, start_time, end_time, room) VALUES")
w(rows + ";")
w("")

w("-- ------------------------------------------------------------ students --")
for chunk in batched(students):
    rows = ",\n".join(
        f"    ((SELECT id FROM Role WHERE name = 'Student'), {q(st['name'])}, "
        f"{q(st['number'])}, {q(st['email'])}, {q(PW)}, 'active', TRUE)"
        for st in chunk
    )
    w("INSERT INTO User (role_id, full_name, university_id, email, password, status, email_verified) VALUES")
    w(rows + ";")
w("")

for chunk in batched(students):
    rows = ",\n".join(
        f"    ((SELECT id FROM User WHERE university_id = {q(st['number'])}), {q(st['number'])}, "
        f"(SELECT faculty_id FROM Department WHERE name = {q(st['dept'])}), "
        f"(SELECT id FROM Department WHERE name = {q(st['dept'])}), "
        f"(SELECT id FROM Program WHERE name = {q(st['programme'])}), @sem, 'full_time', "
        f"{st['level']}, {q(st['admitted'])}, {q(st['graduates'])}, 'active', "
        f"{st['completed']}, {st['gpa']}, {st['cgpa']})"
        for st in chunk
    )
    w("INSERT INTO Student (user_id, student_number, faculty_id, department_id, program_id, "
      "current_semester_id, study_mode, academic_level, admission_date, "
      "expected_graduation_date, academic_status, completed_credit_hours, "
      "current_gpa, cumulative_gpa) VALUES")
    w(rows + ";")
w("")

w("-- ---------------------------------------------------------- enrolments --")
for chunk in batched(enrol_rows):
    rows = ",\n".join(
        f"    ((SELECT id FROM Student WHERE student_number = {q(num)}), "
        f"(SELECT s.id FROM Section s JOIN Course c ON c.id = s.course_id "
        f"WHERE c.course_code = {q(code)} AND s.section_number = {q(sec)}), "
        f"'2026-07-28 09:00:00', @coordinator, '2026-07-29 10:00:00', 'Approved')"
        for num, code, sec in chunk
    )
    w("INSERT INTO Enrollment (student_id, section_id, registration_date, approved_by, approved_at, enrollment_status) VALUES")
    w(rows + ";")
w("")

w("-- ---------------------------------------------------------- attendance --")
for chunk in batched(attendance_rows, 600):
    rows = ",\n".join(
        f"    ((SELECT id FROM Student WHERE student_number = {q(num)}), "
        f"(SELECT s.id FROM Section s JOIN Course c ON c.id = s.course_id "
        f"WHERE c.course_code = {q(code)} AND s.section_number = {q(sec)}), "
        f"{q(date)}, '09:05:00', {q(status)}, {q(method)})"
        for num, code, sec, date, status, method in chunk
    )
    w("INSERT INTO Attendance (student_id, section_id, attendance_date, attendance_time, attendance_status, attendance_method) VALUES")
    w(rows + ";")
w("")

w("-- -------------------------------------------------------------- grades --")
for chunk in batched(grade_rows, 500):
    rows = ",\n".join(
        f"    ((SELECT id FROM Student WHERE student_number = {q(num)}), "
        f"(SELECT s.id FROM Section s JOIN Course c ON c.id = s.course_id "
        f"WHERE c.course_code = {q(code)} AND s.section_number = {q(sec)}), "
        f"{q(kind)}, {q(title)}, {marks}, {total}, {q(letter)}, {points}, "
        f"'2026-09-04 16:00:00')"
        for num, code, sec, kind, title, marks, total, letter, points in chunk
    )
    w("INSERT INTO Grade (student_id, section_id, assessment_type, title, marks, total_marks, "
      "grade_letter, grade_points, published_at) VALUES")
    w(rows + ";")
w("")

w("-- ------------------------------------------------------------ invoices --")
invoice_rows = []
for st in students:
    credits = sum(c for _, c in student_points[st["number"]]) or 12
    gross = round(399.91 * credits, 2)
    scholarship = round(gross * 0.30, 2) if st["cgpa"] >= 3.70 else (
        round(gross * 0.15, 2) if st["cgpa"] >= 3.30 else 0.00)
    net = round(gross - scholarship, 2)

    roll = random.random()
    if roll < 0.55:
        paid = net
    elif roll < 0.80:
        paid = round(net * random.uniform(0.25, 0.75), 2)
    else:
        paid = 0.00

    status = "Paid" if paid >= net else ("Partially Paid" if paid > 0 else "Pending")
    invoice_rows.append((st["number"], gross, scholarship, net, paid,
                         round(net - paid, 2), status))

for chunk in batched(invoice_rows):
    rows = ",\n".join(
        f"    ((SELECT id FROM Student WHERE student_number = {q(num)}), @sem, "
        f"{q('INV-2026-' + num)}, {gross}, {scholarship}, {net}, {paid}, {balance}, "
        f"'2026-09-15', {q(status)}, @registrar)"
        for num, gross, scholarship, net, paid, balance, status in chunk
    )
    w("INSERT INTO Invoice (student_id, semester_id, invoice_number, gross_amount, "
      "scholarship_amount, total_amount, paid_amount, balance, due_date, status, issued_by) VALUES")
    w(rows + ";")
w("")

w("-- Keep the counter on every section true, including the older ones the new")
w("-- cohort did not join.")
w("UPDATE Section sec SET registered_students = (")
w("    SELECT COUNT(*) FROM Enrollment e")
w("    WHERE e.section_id = sec.id AND e.enrollment_status IN ('Approved', 'Completed')")
w(");")

with open(OUT, "w") as fh:
    fh.write("\n".join(out) + "\n")

total = sum(letter_tally.values())
print(f"file written: {OUT}")
print(f"lecturers {len(lecturers)}  courses {len(course_defs)}  sections {len(sections)}")
print(f"students {len(students)}  enrolments {len(enrol_rows)}  "
      f"attendance {len(attendance_rows)}  grades {len(grade_rows)}")
print("\ngrade distribution across all marks:")
for _, letter, _ in LETTERS + [(0, "F", 0)]:
    count = letter_tally[letter]
    print(f"  {letter:<3} {count:>5}  {count / total * 100:5.1f}%  {'█' * int(count / total * 100)}")
