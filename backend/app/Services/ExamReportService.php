<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AIReport;
use App\Models\AIViolation;
use App\Models\BrowserActivity;
use App\Models\ExamRecording;
use App\Models\EyeTracking;
use App\Models\FaceDetection;
use App\Models\HeadPose;

class ExamReportService
{
    private const SEVERITY_PENALTY = [
        'critical' => 20,
        'warning' => 8,
        'info' => 2,
    ];

    private const UNVERIFIED_CEILING = 60;

    public function __construct(
        private readonly AIReport $reports = new AIReport(),
        private readonly AIViolation $violations = new AIViolation(),
        private readonly FaceDetection $faceDetections = new FaceDetection(),
        private readonly EyeTracking $eyeTracking = new EyeTracking(),
        private readonly HeadPose $headPoses = new HeadPose(),
        private readonly BrowserActivity $browserActivity = new BrowserActivity(),
        private readonly ExamRecording $recordings = new ExamRecording(),
        private readonly ExamSessionService $sessionService = new ExamSessionService(),
        private readonly ReportExporter $exporter = new ReportExporter()
    ) {
    }

    public function generate(int $sessionId, array $user): array
    {
        $session = $this->sessionService->requireSupervisedSession($sessionId, $user);

        $violations = $this->violations->forSession($sessionId);
        $verified = (bool) $session['identity_verified'];
        $score = $this->integrityScore($violations, $verified);

        $fields = [
            'session_id' => $sessionId,
            'integrity_score' => $score,
            'total_violations' => count($violations),
            'critical_violations' => $this->violations->countBySeverity($sessionId, 'critical'),
            'summary' => $this->summary($session, $violations, $score, $verified),
            'identity_verified' => $verified,
            'generated_at' => gmdate('Y-m-d H:i:s'),
        ];

        $existing = $this->reports->findBySession($sessionId);

        if ($existing === null) {
            $id = $this->reports->create($fields);
        } else {
            $id = (int) $existing['id'];

            unset($fields['session_id']);
            $this->reports->update($id, $fields);
        }

        return $this->get($id, $user);
    }

    public function get(int $id, array $user): array
    {
        $report = $this->reports->findDetailed($id);

        if ($report === null) {
            throw new ApiException('AI report not found.', 404);
        }

        $sessionId = (int) $report['session_id'];

        $this->guardVisible($sessionId, $user);

        $report['violations'] = $this->violations->forSession($sessionId);
        $report['violation_summary'] = $this->violations->summaryForSession($sessionId);
        $report['recordings'] = $this->recordings->forSession($sessionId);

        if ($user['role'] !== 'Student') {
            $report['telemetry'] = [
                'face_detections' => $this->faceDetections->forSession($sessionId),
                'eye_tracking' => $this->eyeTracking->forSession($sessionId),
                'head_poses' => $this->headPoses->forSession($sessionId),
                'browser_activity' => $this->browserActivity->forSession($sessionId),
            ];
        }

        return $report;
    }

    public function download(int $id, array $user, string $format): array
    {
        $report = $this->get($id, $user);

        return $this->exporter->export(
            [
                'title' => 'AI Examination Report - ' . $report['exam_title'] . ' - ' . $report['student_name'],
                'columns' => ['detected_at', 'violation_type', 'severity', 'confidence_score', 'detail'],
                'rows' => $report['violations'],
                'note' => $report['summary'],
            ],
            $format
        );
    }

    private function integrityScore(array $violations, bool $verified): int
    {
        $score = 100;

        foreach ($violations as $violation) {
            $score -= self::SEVERITY_PENALTY[$violation['severity']] ?? 0;
        }

        $score = max(0, $score);

        return $verified ? $score : min($score, self::UNVERIFIED_CEILING);
    }

    private function summary(array $session, array $violations, int $score, bool $verified): string
    {
        $lines = [
            $session['student_name'] . ' (' . $session['student_number'] . ') sat "'
                . $session['exam_title'] . '" and scored ' . $score . ' out of 100 for integrity.',
        ];

        $lines[] = $violations === []
            ? 'No integrity violations were detected during this session.'
            : count($violations) . ' violation(s) were detected: ' . $this->listTypes($violations) . '.';

        if (!$verified) {
            $note = $session['verification_note'] ?? 'The student\'s identity was never verified.';

            $lines[] = 'Identity was NOT verified. ' . $note
                . ' This report cannot confirm who sat the examination, so the integrity score is'
                . ' capped at ' . self::UNVERIFIED_CEILING . '.';
        }

        if ($session['status'] === 'terminated') {
            $lines[] = 'The session was terminated: ' . $session['termination_reason'];
        }

        return implode(' ', $lines);
    }

    private function listTypes(array $violations): string
    {
        $counts = [];

        foreach ($violations as $violation) {
            $type = $violation['violation_type'];
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }

        $parts = [];

        foreach ($counts as $type => $count) {
            $parts[] = $type . ' x' . $count;
        }

        return implode(', ', $parts);
    }

    private function guardVisible(int $sessionId, array $user): void
    {
        if ($user['role'] === 'Student') {
            $this->sessionService->requireOwnSession($sessionId, $user);

            return;
        }

        $this->sessionService->requireSupervisedSession($sessionId, $user);
    }
}
