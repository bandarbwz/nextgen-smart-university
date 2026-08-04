<?php

declare(strict_types=1);

use App\Controllers\ActivityPointController;
use App\Controllers\AnnouncementBroadcastController;
use App\Controllers\AssessmentController;
use App\Controllers\AssignmentController;
use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\CalendarController;
use App\Controllers\ChatController;
use App\Controllers\ClubController;
use App\Controllers\CourseController;
use App\Controllers\DownloadCenterController;
use App\Controllers\ExcuseController;
use App\Controllers\FinanceController;
use App\Controllers\FoodCourtController;
use App\Controllers\GradeApprovalController;
use App\Controllers\LmsContentController;
use App\Controllers\MaterialController;
use App\Controllers\NotificationController;
use App\Controllers\QuizController;
use App\Controllers\ReportController;
use App\Controllers\DepartmentController;
use App\Controllers\EnrollmentController;
use App\Controllers\EventAttendanceController;
use App\Controllers\EventController;
use App\Controllers\EventRegistrationController;
use App\Controllers\ExamController;
use App\Controllers\ExamReportController;
use App\Controllers\ExamSessionController;
use App\Controllers\FacultyController;
use App\Controllers\ProctoringController;
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


$calendar = new CalendarController();

$router->get('/api/v1/calendar', fn () => $calendar->overview());
$router->post('/api/v1/calendar/sync', fn () => $calendar->synchronise());
$router->post('/api/v1/calendar/import', fn () => $calendar->import());
$router->get('/api/v1/calendar/export', fn () => $calendar->export());

$router->get('/api/v1/calendar/events/daily', fn () => $calendar->daily());
$router->get('/api/v1/calendar/events/weekly', fn () => $calendar->weekly());
$router->get('/api/v1/calendar/events/monthly', fn () => $calendar->monthly());
$router->get('/api/v1/calendar/events', fn () => $calendar->events());
$router->post('/api/v1/calendar/events', fn () => $calendar->store());
$router->get('/api/v1/calendar/events/{id}', fn (string $id) => $calendar->show($id));
$router->put('/api/v1/calendar/events/{id}', fn (string $id) => $calendar->update($id));
$router->delete('/api/v1/calendar/events/{id}', fn (string $id) => $calendar->destroy($id));

$router->get('/api/v1/calendar/reminders', fn () => $calendar->reminders());
$router->post('/api/v1/calendar/reminders', fn () => $calendar->storeReminder());
$router->put('/api/v1/calendar/reminders/{id}/complete', fn (string $id) => $calendar->completeReminder($id));
$router->put('/api/v1/calendar/reminders/{id}', fn (string $id) => $calendar->updateReminder($id));
$router->delete('/api/v1/calendar/reminders/{id}', fn (string $id) => $calendar->destroyReminder($id));


$chat = new ChatController();

$router->get('/api/v1/chat/rooms', fn () => $chat->rooms());
$router->post('/api/v1/chat/rooms', fn () => $chat->store());
$router->post('/api/v1/chat/rooms/private', fn () => $chat->openPrivate());
$router->get('/api/v1/chat/rooms/{id}/messages', fn (string $id) => $chat->messages($id));
$router->get('/api/v1/chat/rooms/{id}/members', fn (string $id) => $chat->members($id));
$router->post('/api/v1/chat/rooms/{id}/join', fn (string $id) => $chat->join($id));
$router->post('/api/v1/chat/rooms/{id}/leave', fn (string $id) => $chat->leave($id));
$router->get('/api/v1/chat/rooms/{id}', fn (string $id) => $chat->room($id));

$router->get('/api/v1/chat/search', fn () => $chat->search());

$router->post('/api/v1/chat/messages', fn () => $chat->send());
$router->post('/api/v1/chat/messages/{id}/reply', fn (string $id) => $chat->reply($id));
$router->put('/api/v1/chat/messages/{id}/pin', fn (string $id) => $chat->pin($id));
$router->post('/api/v1/chat/messages/{id}/reaction', fn (string $id) => $chat->react($id));
$router->delete('/api/v1/chat/messages/{id}/reaction', fn (string $id) => $chat->removeReaction($id));
$router->put('/api/v1/chat/messages/{id}/read', fn (string $id) => $chat->markRead($id));
$router->get('/api/v1/chat/messages/{id}/read', fn (string $id) => $chat->readReceipts($id));
$router->put('/api/v1/chat/messages/{id}', fn (string $id) => $chat->edit($id));
$router->delete('/api/v1/chat/messages/{id}', fn (string $id) => $chat->destroy($id));

$router->get('/api/v1/chat/attachments/{id}', fn (string $id) => $chat->downloadAttachment($id));


$finance = new FinanceController();

$router->get('/api/v1/finance/tuition-fees', fn () => $finance->tuitionFees());
$router->post('/api/v1/finance/tuition-fees', fn () => $finance->storeTuitionFee());
$router->put('/api/v1/finance/tuition-fees/{id}', fn (string $id) => $finance->updateTuitionFee($id));
$router->delete('/api/v1/finance/tuition-fees/{id}', fn (string $id) => $finance->destroyTuitionFee($id));

$router->get('/api/v1/finance/invoices', fn () => $finance->invoices());
$router->post('/api/v1/finance/invoices/generate', fn () => $finance->generateInvoice());
$router->put('/api/v1/finance/invoices/{id}/cancel', fn (string $id) => $finance->cancelInvoice($id));
$router->get('/api/v1/finance/invoices/{id}', fn (string $id) => $finance->invoice($id));

$router->get('/api/v1/finance/payments', fn () => $finance->payments());
$router->post('/api/v1/finance/payments', fn () => $finance->storePayment());
$router->get('/api/v1/finance/payments/{id}', fn (string $id) => $finance->payment($id));

$router->get('/api/v1/finance/scholarships', fn () => $finance->scholarships());
$router->post('/api/v1/finance/scholarships', fn () => $finance->storeScholarship());
$router->put('/api/v1/finance/scholarships/{id}/revoke', fn (string $id) => $finance->revokeScholarship($id));

$router->get('/api/v1/finance/holds', fn () => $finance->holds());
$router->post('/api/v1/finance/holds', fn () => $finance->storeHold());
$router->put('/api/v1/finance/holds/{id}/release', fn (string $id) => $finance->releaseHold($id));

$router->get('/api/v1/finance/standing', fn () => $finance->standing());

$router->get('/api/v1/finance/reports/balances', fn () => $finance->balanceReport());
$router->get('/api/v1/finance/reports/revenue', fn () => $finance->revenueReport());
$router->get('/api/v1/finance/reports/outstanding', fn () => $finance->outstandingReport());


$foodCourt = new FoodCourtController();

$router->get('/api/v1/food-court/restaurants', fn () => $foodCourt->restaurants());
$router->post('/api/v1/food-court/restaurants', fn () => $foodCourt->storeRestaurant());
$router->get('/api/v1/food-court/restaurants/{id}/menu', fn (string $id) => $foodCourt->menu($id));
$router->get('/api/v1/food-court/restaurants/{id}/reviews', fn (string $id) => $foodCourt->reviews($id));
$router->get('/api/v1/food-court/restaurants/{id}/sales', fn (string $id) => $foodCourt->salesReport($id));
$router->get('/api/v1/food-court/restaurants/{id}', fn (string $id) => $foodCourt->restaurant($id));
$router->put('/api/v1/food-court/restaurants/{id}', fn (string $id) => $foodCourt->updateRestaurant($id));
$router->delete('/api/v1/food-court/restaurants/{id}', fn (string $id) => $foodCourt->destroyRestaurant($id));

$router->get('/api/v1/food-court/categories', fn () => $foodCourt->categories());
$router->post('/api/v1/food-court/categories', fn () => $foodCourt->storeCategory());
$router->delete('/api/v1/food-court/categories/{id}', fn (string $id) => $foodCourt->destroyCategory($id));

$router->post('/api/v1/food-court/menu', fn () => $foodCourt->storeMenuItem());
$router->put('/api/v1/food-court/menu/{id}', fn (string $id) => $foodCourt->updateMenuItem($id));
$router->delete('/api/v1/food-court/menu/{id}', fn (string $id) => $foodCourt->destroyMenuItem($id));

$router->get('/api/v1/food-court/orders', fn () => $foodCourt->orders());
$router->post('/api/v1/food-court/orders', fn () => $foodCourt->storeOrder());
$router->put('/api/v1/food-court/orders/{id}/cancel', fn (string $id) => $foodCourt->cancelOrder($id));
$router->put('/api/v1/food-court/orders/{id}/status', fn (string $id) => $foodCourt->updateOrderStatus($id));
$router->get('/api/v1/food-court/orders/{id}', fn (string $id) => $foodCourt->order($id));

$router->post('/api/v1/food-court/reviews', fn () => $foodCourt->storeReview());
$router->delete('/api/v1/food-court/reviews/{id}', fn (string $id) => $foodCourt->destroyReview($id));


$reports = new ReportController();

$router->get('/api/v1/reports', fn () => $reports->index());
$router->post('/api/v1/reports/export', fn () => $reports->export());
$router->get(
    '/api/v1/reports/{category}/{name}',
    fn (string $category, string $name) => $reports->generate($category, $name)
);


$downloads = new DownloadCenterController();

$router->get('/api/v1/download-center/files', fn () => $downloads->files());
$router->post('/api/v1/download-center/files', fn () => $downloads->store());
$router->get('/api/v1/download-center/files/{id}/download', fn (string $id) => $downloads->download($id));
$router->get('/api/v1/download-center/files/{id}', fn (string $id) => $downloads->file($id));
$router->put('/api/v1/download-center/files/{id}', fn (string $id) => $downloads->update($id));
$router->delete('/api/v1/download-center/files/{id}', fn (string $id) => $downloads->destroy($id));

$router->get('/api/v1/download-center/history', fn () => $downloads->history());
$router->get('/api/v1/download-center/transcript', fn () => $downloads->transcript());
$router->get('/api/v1/download-center/schedule', fn () => $downloads->schedule());
$router->get('/api/v1/download-center/invoices/{id}', fn (string $id) => $downloads->invoice($id));


$exams = new ExamController();

$router->get('/api/v1/ai-exam/examinations', fn () => $exams->index());
$router->post('/api/v1/ai-exam/examinations', fn () => $exams->store());
$router->get('/api/v1/ai-exam/examinations/{id}', fn (string $id) => $exams->show($id));
$router->put('/api/v1/ai-exam/examinations/{id}', fn (string $id) => $exams->update($id));
$router->delete('/api/v1/ai-exam/examinations/{id}', fn (string $id) => $exams->destroy($id));

$router->get('/api/v1/ai-exam/examinations/{id}/submissions', fn (string $id) => $exams->submissions($id));
$router->put('/api/v1/ai-exam/submissions/{id}/grade', fn (string $id) => $exams->grade($id));


$examSessions = new ExamSessionController();

$router->post('/api/v1/ai-exam/session/start', fn () => $examSessions->start());
$router->post('/api/v1/ai-exam/session/end', fn () => $examSessions->end());
$router->put('/api/v1/ai-exam/session/pause', fn () => $examSessions->pause());
$router->put('/api/v1/ai-exam/session/resume', fn () => $examSessions->resume());

$router->get('/api/v1/ai-exam/sessions', fn () => $examSessions->mine());
$router->get('/api/v1/ai-exam/examinations/{id}/sessions', fn (string $id) => $examSessions->forExam($id));


$proctoring = new ProctoringController();

$router->post('/api/v1/ai-exam/verify-face', fn () => $proctoring->verifyFace());
$router->post('/api/v1/ai-exam/eye-tracking', fn () => $proctoring->eyeTracking());
$router->post('/api/v1/ai-exam/head-pose', fn () => $proctoring->headPose());
$router->post('/api/v1/ai-exam/browser-monitor', fn () => $proctoring->browserMonitor());
$router->post('/api/v1/ai-exam/device-monitor', fn () => $proctoring->deviceMonitor());

$router->get('/api/v1/ai-exam/violations', fn () => $proctoring->violations());
$router->post('/api/v1/ai-exam/violations', fn () => $proctoring->storeViolation());
$router->get('/api/v1/ai-exam/violations/{id}', fn (string $id) => $proctoring->studentViolations($id));

$router->post('/api/v1/ai-exam/recordings', fn () => $proctoring->storeRecording());
$router->get('/api/v1/ai-exam/recordings/{id}', fn (string $id) => $proctoring->recording($id));


$examReports = new ExamReportController();

$router->post('/api/v1/ai-exam/reports/generate', fn () => $examReports->generate());
$router->get('/api/v1/ai-exam/reports/{id}/download', fn (string $id) => $examReports->download($id));
$router->get('/api/v1/ai-exam/reports/{id}', fn (string $id) => $examReports->show($id));


$clubs = new ClubController();

$router->get('/api/v1/activities/clubs', fn () => $clubs->index());
$router->post('/api/v1/activities/clubs', fn () => $clubs->store());
$router->get('/api/v1/activities/clubs/{id}', fn (string $id) => $clubs->show($id));
$router->put('/api/v1/activities/clubs/{id}', fn (string $id) => $clubs->update($id));
$router->delete('/api/v1/activities/clubs/{id}', fn (string $id) => $clubs->destroy($id));


$events = new EventController();
$eventRegistrations = new EventRegistrationController();
$eventAttendance = new EventAttendanceController();

$router->get('/api/v1/activities/events', fn () => $events->index());
$router->post('/api/v1/activities/events', fn () => $events->store());

$router->get(
    '/api/v1/activities/events/{id}/registrations',
    fn (string $id) => $eventRegistrations->forEvent($id)
);
$router->get(
    '/api/v1/activities/events/{id}/attendance',
    fn (string $id) => $eventAttendance->forEvent($id)
);
$router->post('/api/v1/activities/events/{id}/qr', fn (string $id) => $eventAttendance->openQr($id));
$router->delete('/api/v1/activities/events/{id}/qr', fn (string $id) => $eventAttendance->closeQr($id));
$router->put('/api/v1/activities/events/{id}/cancel', fn (string $id) => $events->cancel($id));

$router->get('/api/v1/activities/events/{id}', fn (string $id) => $events->show($id));
$router->put('/api/v1/activities/events/{id}', fn (string $id) => $events->update($id));
$router->delete('/api/v1/activities/events/{id}', fn (string $id) => $events->destroy($id));

$router->post('/api/v1/activities/register', fn () => $eventRegistrations->store());
$router->get('/api/v1/activities/registrations', fn () => $eventRegistrations->mine());
$router->put(
    '/api/v1/activities/registrations/{id}/cancel',
    fn (string $id) => $eventRegistrations->cancel($id)
);
$router->put(
    '/api/v1/activities/registrations/{id}/approve',
    fn (string $id) => $eventRegistrations->approve($id)
);
$router->put(
    '/api/v1/activities/registrations/{id}/reject',
    fn (string $id) => $eventRegistrations->reject($id)
);

$router->post('/api/v1/activities/attendance', fn () => $eventAttendance->scan());
$router->post('/api/v1/activities/attendance/manual', fn () => $eventAttendance->storeManual());


$activityPoints = new ActivityPointController();

$router->get('/api/v1/activities/points', fn () => $activityPoints->mine());
$router->post('/api/v1/activities/points', fn () => $activityPoints->store());
$router->get('/api/v1/activities/points/leaderboard', fn () => $activityPoints->leaderboard());
$router->get('/api/v1/activities/points/{id}', fn (string $id) => $activityPoints->forStudent($id));


$notifications = new NotificationController();
$announcements = new AnnouncementBroadcastController();

$router->get('/api/v1/notifications/unread', fn () => $notifications->unread());
$router->get('/api/v1/notifications/preferences', fn () => $notifications->preferences());
$router->put('/api/v1/notifications/preferences', fn () => $notifications->updatePreferences());
$router->put('/api/v1/notifications/read-all', fn () => $notifications->markAllRead());

$router->get('/api/v1/notifications/announcements', fn () => $announcements->index());
$router->post('/api/v1/notifications/announcements', fn () => $announcements->store());
$router->put(
    '/api/v1/notifications/announcements/{id}',
    fn (string $id) => $announcements->update($id)
);
$router->delete(
    '/api/v1/notifications/announcements/{id}',
    fn (string $id) => $announcements->destroy($id)
);

$router->post('/api/v1/notifications/broadcast', fn () => $announcements->broadcast());
$router->post('/api/v1/notifications/push', fn () => $notifications->push());
$router->post('/api/v1/notifications/sms', fn () => $notifications->sms());

$router->get('/api/v1/notifications', fn () => $notifications->index());
$router->post('/api/v1/notifications', fn () => $notifications->store());
$router->delete('/api/v1/notifications', fn () => $notifications->destroyAll());

$router->put('/api/v1/notifications/{id}/read', fn (string $id) => $notifications->markRead($id));
$router->put('/api/v1/notifications/{id}/archive', fn (string $id) => $notifications->archive($id));
$router->get('/api/v1/notifications/{id}', fn (string $id) => $notifications->show($id));
$router->delete('/api/v1/notifications/{id}', fn (string $id) => $notifications->destroy($id));


$assessments = new AssessmentController();

$router->get('/api/v1/assessments/results', fn () => $assessments->myResults());
$router->get(
    '/api/v1/assessments/sections/{id}/weights',
    fn (string $id) => $assessments->weights($id)
);
$router->get(
    '/api/v1/assessments/sections/{id}/course-result',
    fn (string $id) => $assessments->courseResult($id)
);

$router->get('/api/v1/assessments', fn () => $assessments->index());
$router->post('/api/v1/assessments', fn () => $assessments->store());

$router->get('/api/v1/assessments/{id}/results', fn (string $id) => $assessments->results($id));
$router->post('/api/v1/assessments/{id}/results', fn (string $id) => $assessments->storeResult($id));
$router->put('/api/v1/assessments/{id}/publish', fn (string $id) => $assessments->publish($id));

$router->get('/api/v1/assessments/{id}', fn (string $id) => $assessments->show($id));
$router->put('/api/v1/assessments/{id}', fn (string $id) => $assessments->update($id));
$router->delete('/api/v1/assessments/{id}', fn (string $id) => $assessments->destroy($id));


$gradeApprovals = new GradeApprovalController();

$router->get('/api/v1/grade-approvals/history', fn () => $gradeApprovals->history());
$router->get('/api/v1/grade-approvals', fn () => $gradeApprovals->index());
$router->post('/api/v1/grade-approvals', fn () => $gradeApprovals->store());
$router->put('/api/v1/grade-approvals/{id}/approve', fn (string $id) => $gradeApprovals->approve($id));
$router->put('/api/v1/grade-approvals/{id}/reject', fn (string $id) => $gradeApprovals->reject($id));
$router->put(
    '/api/v1/grade-approvals/{id}/return',
    fn (string $id) => $gradeApprovals->returnForRevision($id)
);
$router->get('/api/v1/grade-approvals/{id}', fn (string $id) => $gradeApprovals->show($id));

return $router;
