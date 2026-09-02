import { onUnmounted, ref } from 'vue';

function csrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export function useGoogleTts(speechUrl: string) {
    const isSpeaking = ref(false);
    const ttsActivated = ref(false);
    const ttsError = ref<string | null>(null);

    let audio: HTMLAudioElement | null = null;
    let objectUrl: string | null = null;
    let onEnded: (() => void) | null = null;

    const unlockAudio = () => {
        ttsActivated.value = true;
        const silent = new Audio(
            'data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAESsAACJWAAACABAAZGF0YQAAAAA=',
        );
        silent.volume = 0.01;
        silent.play().catch(() => {});
    };

    const activateTTS = () => {
        unlockAudio();
        return true;
    };

    const cleanupAudio = () => {
        if (audio) {
            audio.onended = null;
            audio.onerror = null;
            audio.pause();
            audio.src = '';
            audio = null;
        }
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
    };

    const fail = (message: string) => {
        cleanupAudio();
        isSpeaking.value = false;
        ttsError.value = message;
        onEnded?.();
    };

    const speakText = async (text: string, ended?: () => void) => {
        const spoken = text.trim();
        if (!spoken) {
            return;
        }

        stopSpeaking();
        onEnded = ended ?? null;
        ttsError.value = null;
        ttsActivated.value = true;
        isSpeaking.value = true;

        try {
            const response = await fetch(speechUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'audio/mpeg, application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ text: spoken }),
            });

            if (!response.ok) {
                const body = await response.json().catch(() => null);
                throw new Error(
                    (body && typeof body.message === 'string' && body.message) ||
                        'Google Text-to-Speech is unavailable. Please try again later.',
                );
            }

            const blob = await response.blob();
            if (!blob.type.includes('audio') && blob.size < 64) {
                throw new Error('Google Text-to-Speech returned an invalid audio response.');
            }

            objectUrl = URL.createObjectURL(blob);
            audio = new Audio(objectUrl);
            audio.onended = () => {
                isSpeaking.value = false;
                onEnded?.();
            };
            audio.onerror = () => {
                fail('Unable to play the interview audio. Please refresh and try again.');
            };
            await audio.play();
        } catch (error) {
            const message =
                error instanceof Error
                    ? error.message
                    : 'Google Text-to-Speech is unavailable. Please try again later.';
            fail(message);
        }
    };

    const stopSpeaking = () => {
        cleanupAudio();
        isSpeaking.value = false;
    };

    onUnmounted(() => {
        stopSpeaking();
    });

    return {
        isSpeaking,
        ttsActivated,
        ttsError,
        activateTTS,
        speakText,
        stopSpeaking,
    };
}
