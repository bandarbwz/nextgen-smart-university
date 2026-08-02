import { useCallback, useEffect, useRef, useState } from 'react';
import { MessagesSquare, Pin, Send, Trash2 } from 'lucide-react';
import { PageHeader } from '../components/PageHeader';
import { EmptyState } from '../components/EmptyState';
import { SkeletonRows } from '../components/Skeleton';
import { Badge } from '../components/Badge';
import { Button } from '../components/Button';
import { Alert } from '../components/Alert';
import { chatService } from '../services/chatService';
import { readApiError } from '../services/apiClient';
import { useAuth } from '../hooks/useAuth';
import { useToast } from '../hooks/useToast';

const POLL_INTERVAL_MS = 5000;

function timeOf(value) {
    return value ? value.slice(11, 16) : '';
}

export function ChatPage() {
    const { user } = useAuth();
    const { notify } = useToast();

    const [rooms, setRooms] = useState([]);
    const [activeRoomId, setActiveRoomId] = useState(null);
    const [messages, setMessages] = useState([]);
    const [draft, setDraft] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [isSending, setIsSending] = useState(false);
    const [notice, setNotice] = useState('');

    const latestIdRef = useRef(null);
    const threadRef = useRef(null);

    const canModerate = user.role === 'Lecturer' || user.role === 'Administrator';

    useEffect(() => {
        chatService
            .rooms()
            .then((data) => {
                setRooms(data);

                if (data.length > 0) {
                    setActiveRoomId(data[0].id);
                }
            })
            .catch((error) => setNotice(readApiError(error, 'Unable to load your chats.').message))
            .finally(() => setIsLoading(false));
    }, []);

    const loadThread = useCallback(async (roomId) => {
        const result = await chatService.messages(roomId, { limit: 50 });

        setMessages(result.messages);
        latestIdRef.current = result.latest_id;
    }, []);

    useEffect(() => {
        if (activeRoomId === null) {
            return;
        }

        latestIdRef.current = null;
        setMessages([]);

        loadThread(activeRoomId).catch((error) =>
            setNotice(readApiError(error, 'Unable to open this chat.').message),
        );
    }, [activeRoomId, loadThread]);

    const pollOnce = useCallback(async () => {
        if (activeRoomId === null || latestIdRef.current === null) {
            return;
        }

        try {
            const result = await chatService.messages(activeRoomId, {
                afterId: latestIdRef.current,
            });

            if (result.messages.length > 0) {
                setMessages((current) => [...current, ...result.messages]);
                latestIdRef.current = result.latest_id;
            }
        } catch {
            // A failed poll is not worth interrupting the conversation for.
        }
    }, [activeRoomId]);

    useEffect(() => {
        if (activeRoomId === null) {
            return undefined;
        }

        const timer = setInterval(() => {
            if (!document.hidden) {
                pollOnce();
            }
        }, POLL_INTERVAL_MS);

        const catchUpWhenVisible = () => {
            if (!document.hidden) {
                pollOnce();
            }
        };

        document.addEventListener('visibilitychange', catchUpWhenVisible);

        return () => {
            clearInterval(timer);
            document.removeEventListener('visibilitychange', catchUpWhenVisible);
        };
    }, [activeRoomId, pollOnce]);

    useEffect(() => {
        if (threadRef.current) {
            threadRef.current.scrollTop = threadRef.current.scrollHeight;
        }
    }, [messages]);

    async function handleSend(event) {
        event.preventDefault();

        const body = draft.trim();

        if (!body) {
            return;
        }

        setIsSending(true);

        try {
            const sent = await chatService.send(activeRoomId, body);

            setMessages((current) => [...current, sent]);
            latestIdRef.current = sent.id;
            setDraft('');
        } catch (error) {
            notify(readApiError(error, 'Unable to send this message.').message, 'error');
        } finally {
            setIsSending(false);
        }
    }

    async function handleDelete(id) {
        try {
            await chatService.remove(id);

            setMessages((current) =>
                current.map((message) =>
                    message.id === id ? { ...message, message: null, deleted_at: 'now' } : message,
                ),
            );
        } catch (error) {
            notify(readApiError(error, 'Unable to delete this message.').message, 'error');
        }
    }

    async function handlePin(message) {
        try {
            await chatService.pin(message.id, !Number(message.pinned));

            await loadThread(activeRoomId);
        } catch (error) {
            notify(readApiError(error, 'Unable to pin this message.').message, 'error');
        }
    }

    if (isLoading) {
        return (
            <>
                <PageHeader title="Chat" subtitle="Course discussions and direct messages." />
                <SkeletonRows rows={4} height={64} />
            </>
        );
    }

    if (rooms.length === 0) {
        return (
            <>
                <PageHeader title="Chat" subtitle="Course discussions and direct messages." />
                <div className="nsu-card">
                    <EmptyState
                        icon={MessagesSquare}
                        title="No chat rooms yet"
                        description="Course chats appear automatically once your registration is approved."
                    />
                </div>
            </>
        );
    }

    const activeRoom = rooms.find((room) => room.id === activeRoomId);

    return (
        <>
            <PageHeader title="Chat" subtitle="Course discussions and direct messages." />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-chat">
                <nav className="nsu-chat__rooms" aria-label="Chat rooms">
                    {rooms.map((room) => (
                        <button
                            key={room.id}
                            type="button"
                            className={`nsu-chat__room ${room.id === activeRoomId ? 'nsu-chat__room--active' : ''}`}
                            onClick={() => setActiveRoomId(room.id)}
                            aria-current={room.id === activeRoomId}
                        >
                            <span className="nsu-chat__room-name">{room.room_name}</span>
                            <span className="nsu-chat__room-meta">
                                {room.room_type} - {room.member_count} members
                            </span>
                            {Number(room.unread_count) > 0 && (
                                <span className="nsu-chat__unread">{room.unread_count}</span>
                            )}
                        </button>
                    ))}
                </nav>

                <section className="nsu-chat__panel nsu-card">
                    <header className="nsu-chat__header">
                        <h2 className="nsu-chat__title">{activeRoom?.room_name}</h2>
                        <Badge>{activeRoom?.room_type}</Badge>
                    </header>

                    <div className="nsu-chat__thread" ref={threadRef} aria-live="polite">
                        {messages.length === 0 ? (
                            <EmptyState
                                icon={MessagesSquare}
                                title="No messages yet"
                                description="Start the conversation below."
                            />
                        ) : (
                            messages.map((message) => {
                                const isMine = Number(message.sender_id) === user.id;
                                const isDeleted = message.deleted_at !== null;

                                return (
                                    <article
                                        key={message.id}
                                        className={`nsu-chat__message ${isMine ? 'nsu-chat__message--mine' : ''}`}
                                    >
                                        <div className="nsu-chat__bubble">
                                            <p className="nsu-chat__sender">
                                                {message.sender_name}
                                                {Number(message.pinned) === 1 && (
                                                    <Pin size={12} aria-label="Pinned" />
                                                )}
                                            </p>
                                            <p
                                                className={`nsu-chat__body ${isDeleted ? 'nsu-chat__body--deleted' : ''}`}
                                            >
                                                {isDeleted ? 'This message was deleted' : message.message}
                                            </p>
                                            <p className="nsu-chat__time tabular">
                                                {timeOf(message.sent_at)}
                                                {Number(message.edited) === 1 ? ' - edited' : ''}
                                            </p>
                                        </div>

                                        {!isDeleted && (
                                            <div className="nsu-chat__actions">
                                                {canModerate && (
                                                    <button
                                                        type="button"
                                                        className="nsu-chat__action"
                                                        onClick={() => handlePin(message)}
                                                        aria-label={
                                                            Number(message.pinned) === 1
                                                                ? 'Unpin message'
                                                                : 'Pin message'
                                                        }
                                                    >
                                                        <Pin size={14} />
                                                    </button>
                                                )}
                                                {(isMine || canModerate) && (
                                                    <button
                                                        type="button"
                                                        className="nsu-chat__action"
                                                        onClick={() => handleDelete(message.id)}
                                                        aria-label="Delete message"
                                                    >
                                                        <Trash2 size={14} />
                                                    </button>
                                                )}
                                            </div>
                                        )}
                                    </article>
                                );
                            })
                        )}
                    </div>

                    <form className="nsu-chat__composer" onSubmit={handleSend}>
                        <label className="visually-hidden" htmlFor="chat-draft">
                            Message
                        </label>
                        <input
                            id="chat-draft"
                            className="nsu-field__input"
                            value={draft}
                            onChange={(event) => setDraft(event.target.value)}
                            placeholder="Write a message"
                            autoComplete="off"
                        />
                        <Button
                            type="submit"
                            icon={Send}
                            isLoading={isSending}
                            disabled={!draft.trim()}
                        >
                            Send
                        </Button>
                    </form>
                </section>
            </div>
        </>
    );
}
