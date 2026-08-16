<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { interviews } from '@/routes';
import interviewsRoutes from '@/routes/interviews';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Video, Play, Clock, CheckCircle2, Mic, Volume2, MicOff, Pause, Send } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
// Note: Using Inertia router for requests instead of axios

const props = defineProps<{
    interview: {
        id: number;
        difficulty: string;
        mode: string;
        status: string;
        conversation_history?: Array<{
            role: string;
            content: string;
            timestamp?: string;
        }>;
        job?: { title: string };
        template?: { name: string } | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Interviews',
        href: interviews().url,
    },
    {
        title: 'Voice Interview',
        href: interviewsRoutes.show(props.interview.id).url,
    },
];

const conversationHistory = ref<Array<{ role: string; content: string; timestamp?: string }>>(
    props.interview.conversation_history || []
);
const isListening = ref(false);
const isSpeaking = ref(false);
const isProcessing = ref(false);
const recognition: any = ref(null);
const synth = ref<SpeechSynthesis | null>(null);
const currentUtterance = ref<SpeechSynthesisUtterance | null>(null);
const transcribedText = ref<string>('');
const waitingForUser = ref(false);
const ttsActivated = ref(false); // Track if TTS has been activated by user interaction

// Initialize Speech Recognition
const initSpeechRecognition = () => {
    if (typeof window !== 'undefined') {
        const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;

        if (!SpeechRecognition) {
            alert('Speech recognition is not supported in your browser. Please use Chrome or Edge.');
            return;
        }

        recognition.value = new SpeechRecognition();
        recognition.value.continuous = false;
        recognition.value.interimResults = true;
        recognition.value.lang = 'en-US';

        recognition.value.onresult = (event: any) => {
            let interimTranscript = '';
            let finalTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTranscript += transcript + ' ';
                } else {
                    interimTranscript += transcript;
                }
            }

            transcribedText.value = finalTranscript || interimTranscript;
        };

        recognition.value.onerror = (event: any) => {
            console.error('Speech recognition error:', event.error);
            isListening.value = false;
            if (event.error === 'no-speech') {
                // Restart if no speech detected
                setTimeout(() => {
                    if (waitingForUser.value) {
                        startListening();
                    }
                }, 500);
            }
        };

        recognition.value.onend = () => {
            console.log('🎤 Recognition ended', {
                hasTranscript: !!transcribedText.value.trim(),
                transcript: transcribedText.value,
                waitingForUser: waitingForUser.value
            });

            isListening.value = false;

            // If we got final transcript, process it
            if (transcribedText.value.trim() && waitingForUser.value) {
                processUserMessage(transcribedText.value.trim());
            } else if (waitingForUser.value && !transcribedText.value.trim()) {
                // If no speech detected but we're waiting, restart listening
                console.log('🔄 No speech detected, restarting listening...');
                setTimeout(() => {
                    if (waitingForUser.value && !isProcessing.value) {
                        startListening();
                    }
                }, 500);
            }
        };
    }
};

// Initialize Browser Text-to-Speech
const initTextToSpeech = () => {
    if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
        synth.value = window.speechSynthesis;

        // Load voices if available
        if (synth.value && synth.value.getVoices().length === 0) {
            synth.value.addEventListener('voiceschanged', () => {
                console.log('Browser voices loaded:', synth.value?.getVoices().length || 0);
            });
        }

        return true;
    }
    return false;
};

// Activate TTS through user interaction (required by browser autoplay policy)
const activateTTS = () => {
    if (!synth.value) {
        initTextToSpeech();
    }

    if (!synth.value) {
        return false;
    }

    // Trigger TTS by speaking a minimal utterance (in response to user click)
    try {
        const activationUtterance = new SpeechSynthesisUtterance('');
        activationUtterance.volume = 0;
        activationUtterance.onstart = () => {
            ttsActivated.value = true;
            synth.value?.cancel(); // Cancel immediately
            console.log('✅ TTS activated by user interaction');
        };
        synth.value.speak(activationUtterance);
        // Cancel immediately after queuing to activate
        setTimeout(() => {
            if (synth.value) {
                synth.value.cancel();
                ttsActivated.value = true;
            }
        }, 10);
        return true;
    } catch (e) {
        console.error('TTS activation failed:', e);
        return false;
    }
};

// Speak text using browser's built-in TTS
const speakText = (text: string) => {
    if (!text || !text.trim()) {
        console.warn('Empty text provided to speakText');
        return;
    }

    // Ensure TTS is initialized
    if (!synth.value) {
        if (!initTextToSpeech()) {
            console.error('Browser TTS not available');
            alert('Text-to-speech is not supported in your browser. Please use Chrome, Edge, or Safari.');
            return;
        }
    }

    // Check if TTS needs activation (first time)
    if (!ttsActivated.value) {
        console.warn('⚠️ TTS not activated - requiring user interaction first');
        // Try to activate it
        if (!activateTTS()) {
            console.warn('⚠️ Could not activate TTS automatically');
        }
    }

    // Wait for voices to be loaded if they're not ready
    if (!synth.value) {
        console.error('TTS not initialized');
        return;
    }

    if (synth.value.getVoices().length === 0) {
        console.log('Waiting for voices before speaking...');
        const voiceCheck = setInterval(() => {
            if (synth.value && synth.value.getVoices().length > 0) {
                clearInterval(voiceCheck);
                performSpeech(text);
            }
        }, 100);
        // Timeout after 2 seconds
        setTimeout(() => {
            clearInterval(voiceCheck);
            if (synth.value && synth.value.getVoices().length > 0) {
                performSpeech(text);
            } else {
                console.warn('Voices not loaded, attempting to speak anyway...');
                performSpeech(text);
            }
        }, 2000);
        return;
    }

    // If already speaking, stop it first
    if (synth.value.speaking || synth.value.pending) {
        synth.value.cancel();
        // Wait a bit longer for cancellation to complete
        setTimeout(() => {
            performSpeech(text);
        }, 150);
    } else {
        performSpeech(text);
    }
};

// Perform the actual speech
const performSpeech = (text: string) => {
    if (!synth.value) {
        console.error('TTS not initialized');
        return;
    }

    try {
        // Cancel any pending speech first
        if (synth.value.speaking || synth.value.pending) {
            synth.value.cancel();
        }

        const utterance = new SpeechSynthesisUtterance(text);

        // Configure voice - prefer female English voices
        const voices = synth.value.getVoices();
        if (voices.length > 0) {
            const preferredVoice = voices.find(
                (voice) => voice.lang.startsWith('en') && (voice.name.includes('Female') || voice.name.includes('Samantha') || voice.name.includes('Karen'))
            ) || voices.find((voice) => voice.lang.startsWith('en-US')) || voices.find((voice) => voice.lang.startsWith('en'));

            if (preferredVoice) {
                utterance.voice = preferredVoice;
                console.log('Using voice:', preferredVoice.name);
            }
        }

        utterance.lang = 'en-US';
        utterance.rate = 0.9;
        utterance.pitch = 1.0;
        utterance.volume = 1.0;

        utterance.onstart = () => {
            isSpeaking.value = true;
            console.log('✅ Speech started:', text.substring(0, 50));
        };

        utterance.onend = () => {
            isSpeaking.value = false;
            waitingForUser.value = true;
            console.log('✅ Speech ended successfully');

            // Ensure speech recognition is initialized
            if (!recognition.value) {
                initSpeechRecognition();
            }

            // Auto-start listening after AI speaks
            setTimeout(() => {
                console.log('🔄 Attempting to auto-start listening...', {
                    waitingForUser: waitingForUser.value,
                    isProcessing: isProcessing.value,
                    isListening: isListening.value,
                    isSpeaking: isSpeaking.value,
                    hasRecognition: !!recognition.value
                });

                if (waitingForUser.value && !isProcessing.value && !isListening.value && !isSpeaking.value) {
                    startListening();
                } else {
                    console.warn('⚠️ Cannot start listening:', {
                        waitingForUser: waitingForUser.value,
                        isProcessing: isProcessing.value,
                        isListening: isListening.value,
                        isSpeaking: isSpeaking.value
                    });
                }
            }, 500);
        };

        utterance.onerror = (event: any) => {
            // "canceled" is not really an error - it happens when we cancel intentionally
            if (event.error === 'canceled') {
                console.log('⚠️ Speech was canceled (normal if interrupted)');
                isSpeaking.value = false;
            } else {
                console.error('❌ Speech synthesis error:', event.error);
                isSpeaking.value = false;
                waitingForUser.value = true;
            }
        };

        currentUtterance.value = utterance;

        // Speak immediately - don't delay unnecessarily
        try {
            if (!synth.value) {
                console.error('Synth not available');
                return;
            }

            console.log('📢 Calling speak() - synth state:', {
                speaking: synth.value.speaking,
                pending: synth.value.pending,
                paused: synth.value.paused
            });

            synth.value.speak(utterance);
            console.log('✅ Speech queued for synthesis');

            // Force resume if paused (some browsers pause by default)
            if (synth.value.paused) {
                console.log('▶️ Resuming paused synthesis...');
                synth.value.resume();
            }

            // Double-check and force play if needed
            setTimeout(() => {
                if (!synth.value) return;

                const isActuallyPlaying = isSpeaking.value || synth.value.speaking || synth.value.pending;

                if (!isActuallyPlaying && !synth.value.paused) {
                    console.warn('⚠️ Speech may not have started, attempting to trigger...');
                    // Cancel and retry
                    synth.value.cancel();
                    setTimeout(() => {
                        if (synth.value && !synth.value.speaking) {
                            synth.value.speak(utterance);
                            console.log('🔄 Retried speaking');
                            if (synth.value.paused) {
                                synth.value.resume();
                            }
                        }
                    }, 50);
                } else if (synth.value.paused) {
                    console.log('▶️ Forcing resume...');
                    synth.value.resume();
                } else {
                    console.log('✅ Speech appears to be playing');
                }
            }, 200);

        } catch (speakError) {
            console.error('❌ Error calling speak():', speakError);
            // Retry once
            setTimeout(() => {
                if (synth.value && !isSpeaking.value) {
                    try {
                        synth.value.speak(utterance);
                        console.log('🔄 Retry after error');
                    } catch (retryError) {
                        console.error('❌ Retry also failed:', retryError);
                    }
                }
            }, 300);
        }

    } catch (error) {
        console.error('Error creating speech utterance:', error);
        isSpeaking.value = false;
        waitingForUser.value = true;
    }
};


// Start listening for voice input
const startListening = () => {
    console.log('🎤 startListening() called', {
        hasRecognition: !!recognition.value,
        isListening: isListening.value,
        isSpeaking: isSpeaking.value,
        isProcessing: isProcessing.value
    });

    // Initialize if needed
    if (!recognition.value) {
        console.log('🔧 Initializing speech recognition...');
        initSpeechRecognition();

        // If still not initialized, it's not supported
        if (!recognition.value) {
            console.error('❌ Speech recognition not available');
            return;
        }
    }

    // Check conditions
    if (!recognition.value) {
        console.warn('⚠️ Recognition not available');
        return;
    }

    if (isListening.value) {
        console.log('⚠️ Already listening');
        return;
    }

    if (isSpeaking.value) {
        console.log('⚠️ Cannot start listening while speaking');
        return;
    }

    if (isProcessing.value) {
        console.log('⚠️ Cannot start listening while processing');
        return;
    }

    try {
        transcribedText.value = '';
        isListening.value = true;
        recognition.value.start();
        console.log('✅ Speech recognition started');
    } catch (error: any) {
        console.error('❌ Error starting speech recognition:', error);
        isListening.value = false;

        // If error is "already started", try stopping first
        if (error.message?.includes('already') || error.message?.includes('started')) {
            try {
                recognition.value.stop();
                setTimeout(() => {
                    isListening.value = true;
                    recognition.value.start();
                }, 100);
            } catch (retryError) {
                console.error('❌ Retry failed:', retryError);
            }
        }
    }
};

// Stop listening
const stopListening = () => {
    if (recognition.value && isListening.value) {
        recognition.value.stop();
        isListening.value = false;
    }
};

// Stop speaking
const stopSpeaking = () => {
    if (synth.value && synth.value.speaking) {
        synth.value.cancel();
        isSpeaking.value = false;
    }
    waitingForUser.value = false;
};

// Process user message and get AI response
const processUserMessage = async (message: string) => {
    if (!message.trim() || isProcessing.value) return;

    isProcessing.value = true;
    stopListening();
    waitingForUser.value = false;

    // Add user message to conversation
    conversationHistory.value.push({
        role: 'user',
        content: message,
        timestamp: new Date().toISOString(),
    });

    try {
        // Use direct URL construction to avoid route helper issues
        const conversationUrl = `/interviews/${props.interview.id}/conversation`;

        router.post(
            conversationUrl,
            { user_message: message },
            {
                preserveState: false,
                preserveScroll: true,
                onSuccess: (page) => {
                    const pageProps = (page.props as any);
                    const updatedInterview = pageProps?.interview;

                    if (updatedInterview?.conversation_history) {
                        conversationHistory.value = updatedInterview.conversation_history;

                        // Get the latest AI message and speak it
                        const lastMessage = conversationHistory.value[conversationHistory.value.length - 1];
                        if (lastMessage && lastMessage.role === 'assistant' && lastMessage.content) {
                            console.log('AI response to speak:', lastMessage.content.substring(0, 50));
                            setTimeout(() => speakText(lastMessage.content), 100);
                        } else {
                            console.warn('No assistant message content found');
                        }
                    } else if (pageProps?.ai_response) {
                        conversationHistory.value.push({
                            role: 'assistant',
                            content: pageProps.ai_response,
                            timestamp: new Date().toISOString(),
                        });
                        console.log('AI response to speak (from props):', pageProps.ai_response.substring(0, 50));
                        setTimeout(() => speakText(pageProps.ai_response), 100);
                    } else {
                        router.reload({
                            only: ['interview'],
                            onSuccess: (reloadPage) => {
                                const interview = (reloadPage.props as any)?.interview;
                                if (interview?.conversation_history) {
                                    conversationHistory.value = interview.conversation_history;
                                    const lastMessage = conversationHistory.value[conversationHistory.value.length - 1];
                                    if (lastMessage && lastMessage.role === 'assistant' && lastMessage.content) {
                                        setTimeout(() => speakText(lastMessage.content), 100);
                                    }
                                }
                            },
                        });
                    }

                    isProcessing.value = false;
                    transcribedText.value = '';
                },
                onError: () => {
                    const errorMessage = 'I apologize, but I encountered an error. Could you please repeat that?';
                    conversationHistory.value.push({
                        role: 'assistant',
                        content: errorMessage,
                        timestamp: new Date().toISOString(),
                    });
                    setTimeout(() => speakText(errorMessage), 100);
                    isProcessing.value = false;
                    transcribedText.value = '';
                },
            }
        );
    } catch (error) {
        console.error('Error processing conversation:', error);
        const errorMessage = 'I apologize, but I encountered an error. Could you please repeat that?';
        conversationHistory.value.push({
            role: 'assistant',
            content: errorMessage,
            timestamp: new Date().toISOString(),
        });
        setTimeout(() => speakText(errorMessage), 100);
        isProcessing.value = false;
        transcribedText.value = '';
    }
};

// Complete interview
const completeInterview = () => {
    stopSpeaking();
    stopListening();
    waitingForUser.value = false;

    router.put(
        interviewsRoutes.update(props.interview.id).url,
        {
            conversation_history: conversationHistory.value,
            status: 'completed',
        },
        {
            onSuccess: () => {
                router.visit(interviewsRoutes.show(props.interview.id).url);
            },
        }
    );
};

// Load initial conversation or get initial message
const initializeConversation = async () => {
    if (conversationHistory.value.length === 0) {
        // Use direct URL construction if route helper doesn't exist
        const initialUrl = `/interviews/${props.interview.id}/initial`;

        router.get(
            initialUrl,
            {},
            {
                preserveState: false,
                preserveScroll: true,
                onSuccess: (page: any) => {
                    const interview = (page.props as any)?.interview;
                    if (interview?.conversation_history) {
                        conversationHistory.value = interview.conversation_history;

                        // Speak the initial message
                        const lastMessage = conversationHistory.value[conversationHistory.value.length - 1];
                        if (lastMessage && lastMessage.role === 'assistant' && lastMessage.content) {
                            console.log('Initial message to speak:', lastMessage.content.substring(0, 50));
                            // Small delay to ensure TTS is ready
                            setTimeout(() => {
                                speakText(lastMessage.content);
                            }, 200);
                        } else {
                            console.warn('No assistant message found in conversation history');
                        }
                    } else if ((page.props as any)?.conversation_history) {
                        conversationHistory.value = (page.props as any).conversation_history;

                        // Speak the initial message
                        const lastMessage = conversationHistory.value[conversationHistory.value.length - 1];
                        if (lastMessage && lastMessage.role === 'assistant' && lastMessage.content) {
                            console.log('Initial message to speak (from props):', lastMessage.content.substring(0, 50));
                            setTimeout(() => {
                                speakText(lastMessage.content);
                            }, 200);
                        }
                    } else {
                        console.warn('No conversation history found in response');
                    }
                },
                onError: () => {
                    console.error('Error loading initial conversation');
                },
            }
        );
    } else {
        // Continue existing conversation - speak the last AI message if exists
        const lastMessage = conversationHistory.value[conversationHistory.value.length - 1];
        if (lastMessage && lastMessage.role === 'assistant' && lastMessage.content) {
            setTimeout(() => {
                speakText(lastMessage.content);
            }, 200);
        }
    }
};

// Cleanup on unmount
onUnmounted(() => {
    stopSpeaking();
    stopListening();
    if (recognition.value) {
        recognition.value.abort();
    }
});

// Initialize on mount
onMounted(async () => {
    // Initialize speech recognition first
    initSpeechRecognition();
    console.log('✅ Speech recognition initialized:', !!recognition.value);

    // Initialize browser TTS
    if (initTextToSpeech()) {
        // Wait for voices to load (some browsers need this)
        const waitForVoices = () => {
            if (synth.value && synth.value.getVoices().length > 0) {
                console.log('✅ Voices available:', synth.value.getVoices().length);
                // Pre-warm TTS with a very short utterance to activate TTS engine
                try {
                    const warmUpUtterance = new SpeechSynthesisUtterance(' ');
                    warmUpUtterance.volume = 0.01; // Very quiet
                    warmUpUtterance.rate = 10; // Very fast
                    warmUpUtterance.onstart = () => {
                        console.log('🔊 TTS warm-up started');
                    };
                    warmUpUtterance.onend = () => {
                        console.log('✅ Browser TTS warmed up successfully');
                    };
                    warmUpUtterance.onerror = () => {
                        console.warn('⚠️ TTS warm-up had an issue (may need user interaction)');
                    };
                    if (synth.value) {
                        synth.value.speak(warmUpUtterance);
                        // Resume in case it's paused
                        setTimeout(() => {
                            if (synth.value && synth.value.paused) {
                                synth.value.resume();
                            }
                        }, 100);
                    }
                } catch (e) {
                    console.warn('Browser TTS warm-up failed:', e);
                }
            } else {
                // Retry after a short delay
                setTimeout(waitForVoices, 100);
            }
        };

        // Check if voices are already loaded
        if (synth.value && synth.value.getVoices().length === 0) {
            console.log('Waiting for voices to load...');
            waitForVoices();
        } else {
            waitForVoices();
        }
    } else {
        console.error('Browser TTS not available');
    }

    // Don't auto-initialize conversation - wait for user to click "Start Interview"
    // This ensures TTS works (browser autoplay policy requires user interaction)
    // User must click "Start Interview" button to begin
});
</script>

<template>
    <Head title="Voice Interview Session" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Session Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ interview.job?.title || 'Voice Interview' }}</h1>
                    <p class="text-muted-foreground mt-2">
                        {{ interview.template?.name || 'Recruitment interview' }} · {{ interview.difficulty }} · spoken conversation
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="isListening" class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium animate-pulse">
                        🎤 Listening...
                    </span>
                    <span v-else-if="isSpeaking" class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                        🔊 Speaking...
                    </span>
                    <span v-else-if="isProcessing" class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                        ⏳ Processing...
                    </span>
                </div>
            </div>

            <!-- Conversation Card -->
            <Card class="shadow-sm flex-1 flex flex-col">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Volume2 class="h-5 w-5" />
                        Interview Conversation
                    </CardTitle>
                    <CardDescription>
                        Speak naturally - the AI interviewer will respond to your answers
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex-1 flex flex-col space-y-4">
                    <!-- Conversation Messages -->
                    <div class="flex-1 overflow-y-auto space-y-4 pb-4 min-h-[400px]">
                        <div
                            v-for="(message, index) in conversationHistory"
                            :key="index"
                            class="flex"
                            :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                        >
                            <div
                                class="max-w-[80%] rounded-lg px-4 py-2"
                                :class="message.role === 'user'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-secondary text-secondary-foreground'"
                            >
                                <p class="text-sm whitespace-pre-wrap">{{ message.content }}</p>
                                <p v-if="message.timestamp" class="text-xs opacity-70 mt-1">
                                    {{ new Date(message.timestamp).toLocaleTimeString() }}
                                </p>
                            </div>
                        </div>

                        <!-- Current transcription (interim) -->
                        <div v-if="transcribedText && isListening" class="flex justify-end">
                            <div class="max-w-[80%] rounded-lg px-4 py-2 bg-primary/50 text-primary-foreground">
                                <p class="text-sm italic">{{ transcribedText }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Start Interview Button (shows if TTS not activated) -->
                    <div v-if="!ttsActivated && conversationHistory.length === 0" class="border-t pt-6 text-center">
                        <p class="text-sm text-muted-foreground mb-4">
                            Click the button below to start the interview and enable voice output
                        </p>
                        <Button
                            size="lg"
                            @click="activateTTS(); initializeConversation()"
                            class="gap-2"
                        >
                            <Play class="h-5 w-5" />
                            Start Interview
                        </Button>
                    </div>

                    <!-- Voice Input Controls -->
                    <div class="border-t pt-4">
                        <div class="flex items-center gap-4">
                            <Button
                                :variant="isListening ? 'destructive' : 'default'"
                                size="lg"
                                class="rounded-full w-16 h-16 flex-shrink-0"
                                :disabled="isSpeaking || isProcessing || !ttsActivated"
                                @click="() => { if (!ttsActivated) { activateTTS(); } isListening ? stopListening() : startListening(); }"
                            >
                                <Mic v-if="!isListening" class="h-6 w-6" />
                                <MicOff v-else class="h-6 w-6" />
                            </Button>

                            <div class="flex-1">
                                <p v-if="isListening" class="text-sm font-medium text-primary animate-pulse">
                                    🎤 Listening... Speak now
                                </p>
                                <p v-else-if="isSpeaking" class="text-sm text-muted-foreground">
                                    🔊 AI is speaking...
                                </p>
                                <p v-else-if="isProcessing" class="text-sm text-muted-foreground">
                                    ⏳ Processing your response...
                                </p>
                                <p v-else class="text-sm text-muted-foreground">
                                    Click the microphone to start speaking
                                </p>
                            </div>

                            <Button
                                variant="outline"
                                @click="completeInterview"
                                :disabled="isListening || isSpeaking || isProcessing"
                            >
                                <CheckCircle2 class="mr-2 h-4 w-4" />
                                End Interview
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
