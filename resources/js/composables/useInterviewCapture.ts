import { onUnmounted, ref } from 'vue';

function csrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function randomBetween(min: number, max: number): number {
    return min + Math.floor(Math.random() * (max - min + 1));
}

export type CapturePreview = {
    url: string;
    label: string;
    at: string;
};

type CaptureOptions = {
    screenshotUrl: string;
    recordingUrl: string;
    minIntervalMs?: number;
    maxIntervalMs?: number;
};

export function useInterviewCapture(options: CaptureOptions) {
    const videoRef = ref<HTMLVideoElement | null>(null);
    const cameraReady = ref(false);
    const isRecording = ref(false);
    const screenshotCount = ref(0);
    const previews = ref<CapturePreview[]>([]);
    const flash = ref(false);
    const lastCapturedAt = ref<string | null>(null);
    const error = ref('');

    const minIntervalMs = options.minIntervalMs ?? 8_000;
    const maxIntervalMs = options.maxIntervalMs ?? 18_000;

    let stream: MediaStream | null = null;
    let peer: RTCPeerConnection | null = null;
    let recorder: MediaRecorder | null = null;
    let chunks: Blob[] = [];
    let screenshotTimer: number | null = null;
    let capturing = false;
    let flashTimer: number | null = null;

    const rememberPreview = (blob: Blob, label: string) => {
        const url = URL.createObjectURL(blob);
        previews.value = [...previews.value, { url, label, at: new Date().toISOString() }].slice(-8);
        lastCapturedAt.value = new Date().toLocaleTimeString();
        flash.value = true;

        if (flashTimer) {
            window.clearTimeout(flashTimer);
        }

        flashTimer = window.setTimeout(() => {
            flash.value = false;
        }, 700);
    };

    const takeScreenshot = async (label: string) => {
        const video = videoRef.value;
        if (!video || video.readyState < 2 || video.videoWidth === 0 || capturing) {
            return;
        }

        capturing = true;

        try {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d')?.drawImage(video, 0, 0);

            const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.82));
            if (!blob) {
                return;
            }

            rememberPreview(blob, label);

            const form = new FormData();
            form.append('screenshot', blob, `${label}.jpg`);
            form.append('label', label);

            await fetch(options.screenshotUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: form,
            });

            screenshotCount.value += 1;
        } catch {
            // Keep the interview running if a still fails to upload.
        } finally {
            capturing = false;
        }
    };

    const clearScreenshotTimer = () => {
        if (screenshotTimer) {
            window.clearTimeout(screenshotTimer);
            screenshotTimer = null;
        }
    };

    const scheduleRandomScreenshot = () => {
        clearScreenshotTimer();

        screenshotTimer = window.setTimeout(() => {
            void takeScreenshot('random').then(() => {
                if (cameraReady.value) {
                    scheduleRandomScreenshot();
                }
            });
        }, randomBetween(minIntervalMs, maxIntervalMs));
    };

    const startRecording = () => {
        if (!stream) {
            return;
        }

        chunks = [];
        const mimeType = [
            'video/webm;codecs=vp9,opus',
            'video/webm;codecs=vp8,opus',
            'video/webm',
        ].find((type) => MediaRecorder.isTypeSupported(type));

        recorder = mimeType ? new MediaRecorder(stream, { mimeType }) : new MediaRecorder(stream);
        recorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                chunks.push(event.data);
            }
        };
        recorder.start(4000);
        isRecording.value = true;
    };

    const start = async () => {
        error.value = '';

        stream = await navigator.mediaDevices.getUserMedia({
            audio: { echoCancellation: true, noiseSuppression: true },
            video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
        });

        peer = new RTCPeerConnection();
        stream.getTracks().forEach((track) => peer?.addTrack(track, stream as MediaStream));

        if (videoRef.value) {
            videoRef.value.srcObject = stream;
            await videoRef.value.play();
        }

        cameraReady.value = true;
        startRecording();
        await takeScreenshot('session_start');
        scheduleRandomScreenshot();
    };

    const stopAndUpload = async () => {
        clearScreenshotTimer();
        cameraReady.value = false;

        await takeScreenshot('session_end');

        await new Promise<void>((resolve) => {
            if (!recorder || recorder.state === 'inactive') {
                resolve();
                return;
            }

            recorder.onstop = () => resolve();
            recorder.stop();
        });

        isRecording.value = false;
        stream?.getTracks().forEach((track) => track.stop());
        peer?.close();
        stream = null;
        peer = null;

        if (chunks.length === 0) {
            return;
        }

        const blob = new Blob(chunks, { type: recorder?.mimeType || 'video/webm' });
        const form = new FormData();
        form.append('recording', blob, 'interview.webm');

        await fetch(options.recordingUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: form,
        });

        chunks = [];
        recorder = null;
    };

    onUnmounted(() => {
        clearScreenshotTimer();
        if (flashTimer) {
            window.clearTimeout(flashTimer);
        }
        previews.value.forEach((preview) => URL.revokeObjectURL(preview.url));
        recorder?.stop();
        stream?.getTracks().forEach((track) => track.stop());
        peer?.close();
    });

    return {
        videoRef,
        cameraReady,
        isRecording,
        screenshotCount,
        previews,
        flash,
        lastCapturedAt,
        error,
        start,
        takeScreenshot,
        stopAndUpload,
    };
}
