let soundOn = true;
let audioCtx = null;

const listeners = new Set();

function getCtx() {
    if (!audioCtx) {
        const AC = window.AudioContext || window.webkitAudioContext;

        if (!AC) {
            return null;
        }

        audioCtx = new AC();
    }

    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }

    return audioCtx;
}

function tone(freq, startOffset, duration, type, peak) {
    if (!soundOn) {
        return;
    }

    const ctx = getCtx();

    if (!ctx) {
        return;
    }

    const t0 = ctx.currentTime + startOffset;
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = type || 'sine';
    osc.frequency.setValueAtTime(freq, t0);
    gain.gain.setValueAtTime(0, t0);
    gain.gain.linearRampToValueAtTime(peak, t0 + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, t0 + duration);
    osc.connect(gain).connect(ctx.destination);
    osc.start(t0);
    osc.stop(t0 + duration + 0.05);
}

export function playStartSound() {
    if (!soundOn) {
        return;
    }

    const ctx = getCtx();

    if (!ctx) {
        return;
    }

    const t0 = ctx.currentTime;
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = 'sine';
    osc.frequency.setValueAtTime(210, t0);
    osc.frequency.exponentialRampToValueAtTime(420, t0 + 0.6);
    gain.gain.setValueAtTime(0, t0);
    gain.gain.linearRampToValueAtTime(0.07, t0 + 0.08);
    gain.gain.exponentialRampToValueAtTime(0.0001, t0 + 0.7);
    osc.connect(gain).connect(ctx.destination);
    osc.start(t0);
    osc.stop(t0 + 0.75);
}

export function playChime() {
    tone(880, 0, 0.4, 'sine', 0.11);
    tone(1318.5, 0.09, 0.45, 'sine', 0.08);
}

export function playClick() {
    tone(640, 0, 0.05, 'square', 0.025);
}

export function isSoundOn() {
    return soundOn;
}

export function toggleSound() {
    soundOn = !soundOn;

    if (soundOn) {
        getCtx();
        tone(700, 0, 0.08, 'sine', 0.05);
    }

    listeners.forEach((listener) => listener(soundOn));

    return soundOn;
}

export function subscribeToSound(listener) {
    listeners.add(listener);

    return () => listeners.delete(listener);
}

export function unlockAudio() {
    getCtx();
}
