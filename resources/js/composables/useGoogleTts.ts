import { onUnmounted, ref } from 'vue';

function csrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export function useGoogleTts(speechUrl: string) {
    const isSpeaking = ref(false);
    const ttsActivated = ref(false);

    let audio: HTMLAudioElement | null = null;
    let objectUrl: string | null = null;
    let synth: SpeechSynthesis | null = null;
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

    const speakWithBrowser = (text: string) => {
        if (typeof window === 'undefined' || !('speechSynthesis' in window)) {
            isSpeaking.value = false;
            onEnded?.();
            return;
        }

        synth = window.speechSynthesis;
        synth.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US';
        utterance.rate = 0.95;
        const voices = synth.getVoices();
        const preferred =
            voices.find((voice) => voice.lang.startsWith('en') && /female|samantha|google us/i.test(voice.name)) ||
            voices.find((voice) => voice.lang.startsWith('en-US')) ||
            voices.find((voice) => voice.lang.startsWith('en'));

        if (preferred) {
            utterance.voice = preferred;
        }

        utterance.onstart = () => {
            isSpeaking.value = true;
        };
        utterance.onend = () => {
            isSpeaking.value = false;
            onEnded?.();
        };
        utterance.onerror = () => {
            isSpeaking.value = false;
            onEnded?.();
        };

        synth.speak(utterance);
    };

    const speakText = async (text: string, ended?: () => void) => {
        const spoken = text.trim();
        if (!spoken) {
            return;
        }

        stopSpeaking();
        onEnded = ended ?? null;
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
                throw new Error('Google TTS unavailable');
            }

            const blob = await response.blob();
            if (!blob.type.includes('audio') && blob.size < 64) {
                throw new Error('Not audio');
            }

            objectUrl = URL.createObjectURL(blob);
            audio = new Audio(objectUrl);
            audio.onended = () => {
                isSpeaking.value = false;
                onEnded?.();
            };
            audio.onerror = () => {
                cleanupAudio();
                speakWithBrowser(spoken);
            };
            await audio.play();
        } catch {
            speakWithBrowser(spoken);
        }
    };

    const stopSpeaking = () => {
        cleanupAudio();
        synth?.cancel();
        isSpeaking.value = false;
    };

    onUnmounted(() => {
        stopSpeaking();
    });

    return {
        isSpeaking,
        ttsActivated,
        activateTTS,
        speakText,
        stopSpeaking,
    };
}
