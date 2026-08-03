import { useCallback, useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { AlertTriangle, Eye, Send, ShieldCheck, Timer } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { Alert } from '../../components/Alert';
import { Button } from '../../components/Button';
import { SkeletonRows } from '../../components/Skeleton';
import { examService } from '../../services/examService';
import { readApiError } from '../../services/apiClient';
import { useExamMonitor } from '../../hooks/useExamMonitor';
import { formatCountdown, useCountdown } from '../../hooks/useCountdown';
import { useToast } from '../../hooks/useToast';

const LOW_TIME_SECONDS = 300;

export function ExamSessionPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { notify } = useToast();

    const [exam, setExam] = useState(null);
    const [session, setSession] = useState(null);
    const [answers, setAnswers] = useState({});
    const [isLoading, setIsLoading] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [notice, setNotice] = useState('');

    const isLive = session !== null && session.status === 'active';

    useEffect(() => {
        examService
            .get(id)
            .then((loaded) => {
                setExam(loaded);

                return examService.startSession(loaded.id, { browser: navigator.userAgent });
            })
            .then(setSession)
            .catch((error) => setNotice(readApiError(error, 'Unable to start the examination.').message))
            .finally(() => setIsLoading(false));
    }, [id]);

    const submit = useCallback(
        async (automatic) => {
            if (session === null || isSubmitting) {
                return;
            }

            setIsSubmitting(true);

            try {
                await examService.endSession(session.id, answers);

                notify(
                    automatic
                        ? 'Time ran out. Your answers were submitted automatically.'
                        : 'Your examination has been submitted.',
                    automatic ? 'error' : 'success',
                );

                navigate('/examinations');
            } catch (error) {
                setNotice(readApiError(error, 'Unable to submit your examination.').message);
                setIsSubmitting(false);
            }
        },
        [session, answers, isSubmitting, navigate, notify],
    );

    const onTimeUp = useCallback(() => submit(true), [submit]);

    const remaining = useCountdown(session?.seconds_remaining, onTimeUp);

    const onSessionUpdate = useCallback(
        (updated) => {
            setSession((current) => ({ ...current, ...updated }));

            if (updated.status === 'terminated') {
                notify(
                    updated.termination_reason ?? 'The examination was terminated.',
                    'error',
                );
                navigate('/examinations');
            }
        },
        [navigate, notify],
    );

    useExamMonitor(session?.id, { enabled: isLive, onSessionUpdate });

    useEffect(() => {
        if (!isLive) {
            return undefined;
        }

        const warn = (event) => {
            event.preventDefault();
            event.returnValue = '';
        };

        window.addEventListener('beforeunload', warn);

        return () => window.removeEventListener('beforeunload', warn);
    }, [isLive]);

    if (isLoading) {
        return (
            <>
                <PageHeader title="Examination" subtitle="Preparing your session." />
                <SkeletonRows rows={4} height={80} />
            </>
        );
    }

    if (session === null) {
        return (
            <>
                <PageHeader title="Examination" />
                <Alert variant="error">{notice || 'This examination could not be started.'}</Alert>
                <Button variant="secondary" onClick={() => navigate('/examinations')}>
                    Back to examinations
                </Button>
            </>
        );
    }

    return (
        <>
            <PageHeader
                title={exam.title}
                subtitle={`${exam.course_code} · ${exam.course_name}`}
                actions={
                    <div
                        className={`nsu-exam-timer${remaining <= LOW_TIME_SECONDS ? ' nsu-exam-timer--low' : ''}`}
                    >
                        <Timer size={18} aria-hidden="true" />
                        <span className="tabular" aria-live="polite">
                            {formatCountdown(remaining)}
                        </span>
                    </div>
                }
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-exam-proctor">
                <p className="nsu-exam-proctor__line">
                    <Eye size={16} aria-hidden="true" />
                    Leaving this tab or exiting fullscreen is recorded. Three critical breaches end
                    the examination.
                </p>

                {Number(session.identity_verified) === 1 ? (
                    <p className="nsu-exam-proctor__verified">
                        <ShieldCheck size={16} aria-hidden="true" />
                        Identity verified
                    </p>
                ) : (
                    <p className="nsu-exam-proctor__unverified">
                        <AlertTriangle size={16} aria-hidden="true" />
                        {session.verification_note ?? 'Identity not verified.'}
                    </p>
                )}
            </div>

            <ol className="nsu-exam-questions">
                {exam.questions.map((question, index) => (
                    <li className="nsu-card nsu-exam-question" key={question.id}>
                        <div className="nsu-exam-question__head">
                            <span className="nsu-exam-question__number">Question {index + 1}</span>
                            <span className="nsu-exam-question__marks tabular">
                                {question.marks} marks
                            </span>
                        </div>

                        <p className="nsu-exam-question__text">{question.question}</p>

                        <ExamAnswer
                            question={question}
                            value={answers[question.id] ?? ''}
                            onChange={(value) =>
                                setAnswers((current) => ({ ...current, [question.id]: value }))
                            }
                        />
                    </li>
                ))}
            </ol>

            <div className="nsu-exam-submit">
                <p className="nsu-exam-submit__count">
                    {Object.keys(answers).length} of {exam.questions.length} answered
                </p>

                <Button
                    variant="primary"
                    icon={Send}
                    isLoading={isSubmitting}
                    onClick={() => submit(false)}
                >
                    {isSubmitting ? 'Submitting' : 'Submit examination'}
                </Button>
            </div>
        </>
    );
}

function ExamAnswer({ question, value, onChange }) {
    if (question.question_type === 'Multiple Choice') {
        return (
            <div className="nsu-exam-options">
                {question.options.map((option) => (
                    <label className="nsu-exam-option" key={option.label}>
                        <input
                            type="radio"
                            name={`question-${question.id}`}
                            value={option.label}
                            checked={value === option.label}
                            onChange={(event) => onChange(event.target.value)}
                        />
                        <span className="nsu-exam-option__label">{option.label}</span>
                        <span>{option.text}</span>
                    </label>
                ))}
            </div>
        );
    }

    if (question.question_type === 'True / False') {
        return (
            <div className="nsu-exam-options">
                {['True', 'False'].map((option) => (
                    <label className="nsu-exam-option" key={option}>
                        <input
                            type="radio"
                            name={`question-${question.id}`}
                            value={option}
                            checked={value === option}
                            onChange={(event) => onChange(event.target.value)}
                        />
                        <span>{option}</span>
                    </label>
                ))}
            </div>
        );
    }

    return (
        <textarea
            className="nsu-field__input nsu-exam-essay"
            rows={question.question_type === 'Essay' ? 8 : 3}
            value={value}
            onChange={(event) => onChange(event.target.value)}
            placeholder="Type your answer"
            aria-label={`Answer for question ${question.position}`}
        />
    );
}
