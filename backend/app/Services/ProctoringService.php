<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUpload;
use App\Models\AIViolation;
use App\Models\BrowserActivity;
use App\Models\ExamRecording;
use App\Models\ExamSession;
use App\Models\EyeTracking;
use App\Models\FaceDetection;
use App\Models\HeadPose;

class ProctoringService
{
    private const CRITICAL_LIMIT = 3;

    private const OFF_SCREEN_LIMIT = 10;

    private const HEAD_TURN_LIMIT = 35.0;

    private const BROWSER_VIOLATIONS = [
        'tab_hidden' => 'Tab Switching',
        'fullscreen_exit' => 'Fullscreen Exit',
    ];

    public function __construct(
        private readonly ExamSession $sessions = new ExamSession(),
        private readonly AIViolation $violations = new AIViolation(),
        private readonly FaceDetection $faceDetections = new FaceDetection(),
        private readonly EyeTracking $eyeTracking = new EyeTracking(),
        private readonly HeadPose $headPoses = new HeadPose(),
        private readonly BrowserActivity $browserActivity = new BrowserActivity(),
        private readonly ExamRecording $recordings = new ExamRecording(),
        private readonly ExamSessionService $sessionService = new ExamSessionService(),
        private readonly FaceVerificationService $faces = new FaceVerificationService(),
        private readonly AiProctorService $proctor = new AiProctorService(),
        private readonly CourseAccessService $access = new CourseAccessService()
    ) {
    }

    public function verifyFace(array $user, int $sessionId, string $image): array
    {
        $session = $this->requireLiveSession($sessionId, $user);

        $detection = $this->proctor->detectFaces(['image' => $image]);

        $this->faceDetections->create([
            'session_id' => $sessionId,
            'faces_detected' => $detection['faces_detected'],
            'confidence_score' => $detection['confidence'],
            'captured_at' => gmdate('Y-m-d H:i:s'),
        ]);

        if ($detection['faces_detected'] > 1) {
            $this->record($session, 'Multiple Faces', 'critical', $detection['confidence']);

            return ['verified' => false, 'reason' => 'More than one face was visible.'];
        }

        if ($detection['faces_detected'] === 0) {
            $this->record($session, 'Face Not Detected', 'warning', $detection['confidence']);

            return ['verified' => false, 'reason' => 'No face was visible.'];
        }

        $result = $this->faces->verify((int) $user['user_id'], $image);

        if (!$result['verified']) {
            $this->record($session, 'Face Not Detected', 'critical', $result['confidence']);

            return ['verified' => false, 'reason' => 'The face did not match the registered student.'];
        }

        $this->sessions->update($sessionId, [
            'identity_verified' => true,
            'verification_note' => 'Identity verified at ' . gmdate('Y-m-d H:i:s') . ' UTC.',
        ]);

        return ['verified' => true, 'confidence' => $result['confidence']];
    }

    public function trackEyes(array $user, int $sessionId, string $image): array
    {
        $session = $this->requireLiveSession($sessionId, $user);

        $result = $this->proctor->trackEyes(['image' => $image]);

        $this->eyeTracking->create([
            'session_id' => $sessionId,
            'gaze_direction' => $result['gaze_direction'],
            'off_screen_seconds' => $result['off_screen_seconds'],
            'confidence_score' => $result['confidence'],
            'captured_at' => gmdate('Y-m-d H:i:s'),
        ]);

        if ($result['off_screen_seconds'] >= self::OFF_SCREEN_LIMIT) {
            $this->record(
                $session,
                'Looking Away',
                'warning',
                $result['confidence'],
                'Looked away for ' . $result['off_screen_seconds'] . ' seconds.'
            );
        }

        return $result;
    }

    public function estimateHeadPose(array $user, int $sessionId, string $image): array
    {
        $session = $this->requireLiveSession($sessionId, $user);

        $result = $this->proctor->estimateHeadPose(['image' => $image]);

        $this->headPoses->create([
            'session_id' => $sessionId,
            'yaw' => $result['yaw'],
            'pitch' => $result['pitch'],
            'roll' => $result['roll'],
            'confidence_score' => $result['confidence'],
            'captured_at' => gmdate('Y-m-d H:i:s'),
        ]);

        if (abs($result['yaw']) >= self::HEAD_TURN_LIMIT || abs($result['pitch']) >= self::HEAD_TURN_LIMIT) {
            $this->record(
                $session,
                'Head Pose Warning',
                'warning',
                $result['confidence'],
                'Yaw ' . $result['yaw'] . ', pitch ' . $result['pitch'] . '.'
            );
        }

        return $result;
    }

    public function recordBrowserActivity(array $user, int $sessionId, string $type, ?string $detail): array
    {
        $session = $this->requireLiveSession($sessionId, $user);

        $this->browserActivity->create([
            'session_id' => $sessionId,
            'activity_type' => $type,
            'detail' => $detail,
            'occurred_at' => gmdate('Y-m-d H:i:s'),
        ]);

        if (isset(self::BROWSER_VIOLATIONS[$type])) {
            $this->record($session, self::BROWSER_VIOLATIONS[$type], 'critical', null, $detail);
        }

        return $this->sessions->find($sessionId);
    }

    public function recordDevice(array $user, int $sessionId, array $fields): array
    {
        $session = $this->requireLiveSession($sessionId, $user);

        $this->sessions->update($sessionId, [
            'browser' => mb_substr((string) ($fields['browser'] ?? $session['browser']), 0, 100),
            'device' => mb_substr((string) ($fields['device'] ?? $session['device']), 0, 100),
        ]);

        if (array_key_exists('camera_enabled', $fields) && $fields['camera_enabled'] === false) {
            $this->record($session, 'Camera Disabled', 'critical', null, 'The camera stream stopped.');
        }

        return $this->sessions->find($sessionId);
    }

    public function reportViolation(array $user, int $sessionId, array $fields): array
    {
        $session = $this->requireLiveSession($sessionId, $user);

        $id = $this->record(
            $session,
            $fields['violation_type'],
            $fields['severity'] ?? 'warning',
            isset($fields['confidence_score']) ? (float) $fields['confidence_score'] : null,
            $fields['detail'] ?? null,
            $fields['detected_at'] ?? null
        );

        return $this->violations->find($id);
    }

    public function violationsForExam(int $examId, array $user): array
    {
        $exam = (new ExamService())->requireExam($examId);

        $this->access->guardSectionOwned((int) $exam['section_id'], $user);

        return $this->violations->forExam($examId);
    }

    public function violationsForSession(int $sessionId, array $user): array
    {
        $this->requireVisibleSession($sessionId, $user);

        return $this->violations->forSession($sessionId);
    }

    public function violationsForStudent(int $studentId, array $user): array
    {
        if ($user['role'] === 'Student'
            && $this->access->requireStudentId($user['user_id']) !== $studentId) {
            throw new ApiException('You can only view your own violations.', 403);
        }

        return $this->violations->forStudent($studentId);
    }

    public function storeRecording(array $user, int $sessionId, array $file): array
    {
        $session = $this->requireLiveSession($sessionId, $user);

        $path = FileUpload::store($file, 'exam-recordings', FileUpload::PROFILE_EXAM_RECORDING);

        $id = $this->recordings->create([
            'session_id' => (int) $session['id'],
            'file_path' => $path,
            'file_name' => $file['name'],
            'file_size' => (int) $file['size'],
            'recorded_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return $this->recordings->find($id);
    }

    public function recording(int $id, array $user): array
    {
        $recording = $this->recordings->findDetailed($id);

        if ($recording === null) {
            throw new ApiException('Recording not found.', 404);
        }

        $this->requireVisibleSession((int) $recording['session_id'], $user);

        $path = FileUpload::absolutePath($recording['file_path']);

        if (!is_file($path)) {
            throw new ApiException('The recording file is missing.', 404);
        }

        return [
            'name' => $recording['file_name'],
            'path' => $path,
        ];
    }

    private function record(
        array $session,
        string $type,
        string $severity,
        ?float $confidence,
        ?string $detail = null,
        ?string $detectedAt = null
    ): int {
        $sessionId = (int) $session['id'];

        $id = $this->violations->create([
            'session_id' => $sessionId,
            'violation_type' => $type,
            'severity' => $severity,
            'confidence_score' => $confidence,
            'detail' => $detail,
            'detected_at' => $detectedAt === null
                ? gmdate('Y-m-d H:i:s')
                : gmdate('Y-m-d H:i:s', strtotime($detectedAt)),
        ]);

        $this->sessions->refreshViolationCount($sessionId);

        if ($severity === 'critical'
            && $this->violations->countBySeverity($sessionId, 'critical') >= self::CRITICAL_LIMIT) {
            $this->sessionService->terminate(
                $sessionId,
                'Terminated after ' . self::CRITICAL_LIMIT . ' critical integrity violations.'
            );
        }

        return $id;
    }

    private function requireLiveSession(int $sessionId, array $user): array
    {
        $session = $this->sessionService->requireOwnSession($sessionId, $user);

        if ($session['status'] !== 'active' && $session['status'] !== 'paused') {
            throw new ApiException('This examination session is no longer open.', 409);
        }

        return $session;
    }

    private function requireVisibleSession(int $sessionId, array $user): array
    {
        if ($user['role'] === 'Student') {
            return $this->sessionService->requireOwnSession($sessionId, $user);
        }

        return $this->sessionService->requireSupervisedSession($sessionId, $user);
    }
}
