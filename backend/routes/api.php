<?php

declare(strict_types=1);

use App\Controllers\AssignmentController;
use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\CourseController;
use App\Controllers\ExcuseController;
use App\Controllers\LmsContentController;
use App\Controllers\MaterialController;
use App\Controllers\QuizController;
use App\Controllers\DepartmentController;
use App\Controllers\EnrollmentController;
use App\Controllers\FacultyController;
use App\Controllers\LecturerController;
use App\Controllers\ProgramController;
use App\Controllers\ScheduleController;
use App\Controllers\SectionController;
use App\Controllers\SemesterController;
use App\Controllers\StudentController;
use App\Controllers\TranscriptController;
use App\Helpers\Router;

$router = new Router();

$auth = new AuthController();

$router->post('/api/v1/auth/login', fn () => $auth->login());
$router->post('/api/v1/auth/logout', fn () => $auth->logout());
$router->post('/api/v1/auth/refresh', fn () => $auth->refresh());

$router->post('/api/v1/auth/forgot-password', fn () => $auth->forgotPassword());
$router->post('/api/v1/auth/reset-password', fn () => $auth->resetPassword());
$router->put('/api/v1/auth/change-password', fn () => $auth->changePassword());

$router->post('/api/v1/auth/verify-email', fn () => $auth->verifyEmail());
$router->post('/api/v1/auth/resend-verification', fn () => $auth->resendVerification());

$router->get('/api/v1/auth/profile', fn () => $auth->profile());
$router->put('/api/v1/auth/profile', fn () => $auth->updateProfile());

$router->get('/api/v1/auth/sessions', fn () => $auth->sessions());
$router->delete('/api/v1/auth/sessions/{id}', fn (string $id) => $auth->revokeSession($id));


$faculties = new FacultyController();

$router->get('/api/v1/faculties', fn () => $faculties->index());
$router->get('/api/v1/faculties/{id}', fn (string $id) => $faculties->show($id));
$router->get('/api/v1/faculties/{id}/departments', fn (string $id) => $faculties->departments($id));
$router->post('/api/v1/faculties', fn () => $faculties->store());
$router->put('/api/v1/faculties/{id}', fn (string $id) => $faculties->update($id));
$router->delete('/api/v1/faculties/{id}', fn (string $id) => $faculties->destroy($id));


$departments = new DepartmentController();

$router->get('/api/v1/departments', fn () => $departments->index());
$router->get('/api/v1/departments/{id}', fn (string $id) => $departments->show($id));
$router->post('/api/v1/departments', fn () => $departments->store());
$router->put('/api/v1/departments/{id}', fn (string $id) => $departments->update($id));
$router->delete('/api/v1/departments/{id}', fn (string $id) => $departments->destroy($id));


$programs = new ProgramController();

$router->get('/api/v1/programs', fn () => $programs->index());
$router->get('/api/v1/programs/{id}', fn (string $id) => $programs->show($id));
$router->post('/api/v1/programs', fn () => $programs->store());
$router->put('/api/v1/programs/{id}', fn (string $id) => $programs->update($id));
$router->delete('/api/v1/programs/{id}', fn (string $id) => $programs->destroy($id));


$courses = new CourseController();

$router->get('/api/v1/courses', fn () => $courses->index());
$router->get('/api/v1/courses/{id}', fn (string $id) => $courses->show($id));
$router->get('/api/v1/courses/{id}/prerequisites', fn (string $id) => $courses->prerequisites($id));
$router->post('/api/v1/courses', fn () => $courses->store());
$router->put('/api/v1/courses/{id}', fn (string $id) => $courses->update($id));
$router->delete('/api/v1/courses/{id}', fn (string $id) => $courses->destroy($id));


$semesters = new SemesterController();

$router->get('/api/v1/semesters', fn () => $semesters->index());
$router->get('/api/v1/semesters/current', fn () => $semesters->current());
$router->get('/api/v1/semesters/{id}', fn (string $id) => $semesters->show($id));
$router->post('/api/v1/semesters', fn () => $semesters->store());
$router->put('/api/v1/semesters/{id}', fn (string $id) => $semesters->update($id));
$router->delete('/api/v1/semesters/{id}', fn (string $id) => $semesters->destroy($id));


$sections = new SectionController();

$router->get('/api/v1/sections', fn () => $sections->index());
$router->get('/api/v1/sections/{id}', fn (string $id) => $sections->show($id));
$router->get('/api/v1/sections/{id}/students', fn (string $id) => $sections->students($id));
$router->get('/api/v1/courses/{id}/sections', fn (string $id) => $sections->byCourse($id));
$router->post('/api/v1/sections', fn () => $sections->store());
$router->put('/api/v1/sections/{id}', fn (string $id) => $sections->update($id));
$router->delete('/api/v1/sections/{id}', fn (string $id) => $sections->destroy($id));
$router->post('/api/v1/sections/{id}/open-registration', fn (string $id) => $sections->openRegistration($id));
$router->post('/api/v1/sections/{id}/close-registration', fn (string $id) => $sections->closeRegistration($id));
$router->put('/api/v1/sections/{id}/capacity', fn (string $id) => $sections->updateCapacity($id));
$router->put('/api/v1/sections/{id}/lecturer', fn (string $id) => $sections->assignLecturer($id));
$router->put('/api/v1/sections/{id}/classroom', fn (string $id) => $sections->changeClassroom($id));


$students = new StudentController();

$router->get('/api/v1/students', fn () => $students->index());
$router->get('/api/v1/students/me', fn () => $students->profile());
$router->get('/api/v1/students/{id}', fn (string $id) => $students->show($id));
$router->post('/api/v1/students', fn () => $students->store());
$router->put('/api/v1/students/{id}', fn (string $id) => $students->update($id));
$router->delete('/api/v1/students/{id}', fn (string $id) => $students->destroy($id));


$lecturers = new LecturerController();

$router->get('/api/v1/lecturers', fn () => $lecturers->index());
$router->get('/api/v1/lecturers/{id}', fn (string $id) => $lecturers->show($id));
$router->post('/api/v1/lecturers', fn () => $lecturers->store());
$router->put('/api/v1/lecturers/{id}', fn (string $id) => $lecturers->update($id));
$router->delete('/api/v1/lecturers/{id}', fn (string $id) => $lecturers->destroy($id));


$enrollments = new EnrollmentController();

$router->post('/api/v1/enrollments/register', fn () => $enrollments->register());
$router->post('/api/v1/enrollments/drop', fn () => $enrollments->drop());
$router->get('/api/v1/enrollments/current', fn () => $enrollments->current());
$router->get('/api/v1/enrollments/history', fn () => $enrollments->history());
$router->get('/api/v1/enrollments/pending', fn () => $enrollments->pending());
$router->put('/api/v1/enrollments/{id}/approve', fn (string $id) => $enrollments->approve($id));
$router->put('/api/v1/enrollments/{id}/reject', fn (string $id) => $enrollments->reject($id));


$transcripts = new TranscriptController();

$router->get('/api/v1/transcript', fn () => $transcripts->own());
$router->get('/api/v1/transcript/{id}', fn (string $id) => $transcripts->forStudent($id));
$router->get('/api/v1/gpa', fn () => $transcripts->currentGpa());
$router->get('/api/v1/cgpa', fn () => $transcripts->cumulativeGpa());
$router->post('/api/v1/gpa/{id}/calculate', fn (string $id) => $transcripts->recalculate($id));


$schedules = new ScheduleController();

$router->get('/api/v1/schedule', fn () => $schedules->weekly());
$router->get('/api/v1/schedule/daily', fn () => $schedules->daily());
$router->get('/api/v1/schedule/semester', fn () => $schedules->weekly());


$attendance = new AttendanceController();
$excuses = new ExcuseController();

$router->post('/api/v1/attendance/qr-session', fn () => $attendance->openSession());
$router->get('/api/v1/attendance/qr-session/{id}', fn (string $id) => $attendance->activeSession($id));
$router->put('/api/v1/attendance/qr-session/{id}/close', fn (string $id) => $attendance->closeSession($id));

$router->post('/api/v1/attendance/scan', fn () => $attendance->scan());
$router->post('/api/v1/attendance/verify-location', fn () => $attendance->verifyLocation());
$router->post('/api/v1/attendance/verify-face', fn () => $attendance->verifyFace());
$router->put('/api/v1/attendance/manual', fn () => $attendance->manual());

$router->post('/api/v1/attendance/excuse', fn () => $excuses->store());
$router->get('/api/v1/attendance/excuse', fn () => $excuses->index());
$router->put('/api/v1/attendance/excuse/{id}/approve', fn (string $id) => $excuses->approve($id));
$router->put('/api/v1/attendance/excuse/{id}/reject', fn (string $id) => $excuses->reject($id));

$router->get('/api/v1/attendance/me', fn () => $attendance->myAttendance());
$router->get('/api/v1/attendance/statistics', fn () => $attendance->statistics());
$router->get('/api/v1/attendance/reports/daily', fn () => $attendance->dailyReport());
$router->get('/api/v1/attendance/reports/monthly', fn () => $attendance->monthlyReport());
$router->get('/api/v1/attendance/student/{id}', fn (string $id) => $attendance->forStudent($id));
$router->get('/api/v1/attendance/section/{id}', fn (string $id) => $attendance->forSection($id));
$router->get('/api/v1/attendance/lecturer/{id}', fn (string $id) => $attendance->forLecturer($id));

$router->put('/api/v1/attendance/{id}', fn (string $id) => $attendance->update($id));
$router->delete('/api/v1/attendance/{id}', fn (string $id) => $attendance->destroy($id));


$materials = new MaterialController();

$router->get('/api/v1/lms/materials', fn () => $materials->index());
$router->post('/api/v1/lms/materials', fn () => $materials->store());
$router->get('/api/v1/lms/materials/{id}/download', fn (string $id) => $materials->download($id));
$router->get('/api/v1/lms/materials/{id}', fn (string $id) => $materials->show($id));
$router->put('/api/v1/lms/materials/{id}', fn (string $id) => $materials->update($id));
$router->delete('/api/v1/lms/materials/{id}', fn (string $id) => $materials->destroy($id));


$assignments = new AssignmentController();

$router->get('/api/v1/lms/assignments', fn () => $assignments->index());
$router->post('/api/v1/lms/assignments', fn () => $assignments->store());
$router->post('/api/v1/lms/assignments/{id}/submit', fn (string $id) => $assignments->submit($id));
$router->get('/api/v1/lms/assignments/{id}', fn (string $id) => $assignments->show($id));
$router->put('/api/v1/lms/assignments/{id}', fn (string $id) => $assignments->update($id));
$router->delete('/api/v1/lms/assignments/{id}', fn (string $id) => $assignments->destroy($id));

$router->get('/api/v1/lms/submissions/{id}', fn (string $id) => $assignments->showSubmission($id));
$router->put('/api/v1/lms/submissions/{id}/grade', fn (string $id) => $assignments->gradeSubmission($id));


$quizzes = new QuizController();

$router->get('/api/v1/lms/quizzes', fn () => $quizzes->index());
$router->post('/api/v1/lms/quizzes', fn () => $quizzes->store());
$router->post('/api/v1/lms/quizzes/{id}/submit', fn (string $id) => $quizzes->submit($id));
$router->get('/api/v1/lms/quizzes/{id}/submissions', fn (string $id) => $quizzes->submissions($id));
$router->get('/api/v1/lms/quizzes/{id}', fn (string $id) => $quizzes->show($id));
$router->put('/api/v1/lms/quizzes/{id}', fn (string $id) => $quizzes->update($id));
$router->delete('/api/v1/lms/quizzes/{id}', fn (string $id) => $quizzes->destroy($id));


$lmsContent = new LmsContentController();

$router->get('/api/v1/lms/announcements', fn () => $lmsContent->announcements());
$router->post('/api/v1/lms/announcements', fn () => $lmsContent->storeAnnouncement());
$router->put('/api/v1/lms/announcements/{id}', fn (string $id) => $lmsContent->updateAnnouncement($id));
$router->delete('/api/v1/lms/announcements/{id}', fn (string $id) => $lmsContent->destroyAnnouncement($id));

$router->get('/api/v1/lms/resources', fn () => $lmsContent->resources());
$router->post('/api/v1/lms/resources', fn () => $lmsContent->storeResource());
$router->delete('/api/v1/lms/resources/{id}', fn (string $id) => $lmsContent->destroyResource($id));

$router->get('/api/v1/lms/grades', fn () => $lmsContent->grades());
$router->post('/api/v1/lms/grades', fn () => $lmsContent->storeGrade());
$router->post('/api/v1/lms/grades/publish', fn () => $lmsContent->publishGrades());

return $router;
