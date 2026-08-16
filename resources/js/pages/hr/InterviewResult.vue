<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InterviewEvaluationPanel, { type InterviewEvaluation } from '@/components/InterviewEvaluationPanel.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { interviewResults } from '@/routes';
import interviewResultsRoutes from '@/routes/interview-results';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    interview: {
        id: number;
        score?: number | null;
        ai_score?: number | null;
        human_score?: number | null;
        review_status?: string | null;
        human_notes?: string | null;
        evaluation?: InterviewEvaluation | null;
        review_audit?: Array<{
            action: string;
            user_name?: string;
            at?: string;
            notes?: string;
            human_score?: number | null;
        }>;
        job?: { title: string } | null;
        candidate?: { name: string; email: string } | null;
        template?: { name: string } | null;
        reviewed_by?: { name: string } | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Interview Results', href: interviewResults().url },
    { title: props.interview.candidate?.name || 'Result', href: interviewResultsRoutes.show(props.interview.id).url },
];

const page = usePage();
const errors = computed(() => page.props.errors || {});

const form = ref({
    action: (props.interview.review_status && props.interview.review_status !== 'pending_review'
        ? props.interview.review_status
        : 'accepted') as 'accepted' | 'edited' | 'rejected',
    human_score: props.interview.human_score ?? props.interview.ai_score ?? props.interview.score ?? 0,
    human_notes: props.interview.human_notes ?? '',
});

const submitReview = () => {
    router.put(interviewResultsRoutes.review(props.interview.id).url, form.value);
};
</script>

<template>
    <Head title="Interview Result" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        {{ interview.candidate?.name || 'Candidate' }}
                    </h1>
                    <p class="text-muted-foreground mt-2">
                        {{ interview.job?.title || 'Interview' }} ·
                        {{ interview.template?.name || 'Recruitment interview' }}
                        <span v-if="interview.candidate?.email"> · {{ interview.candidate.email }}</span>
                    </p>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="interviewResults().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Link>
                </Button>
            </div>

            <InterviewEvaluationPanel
                :evaluation="interview.evaluation"
                :score="interview.score"
                :ai-score="interview.ai_score"
                :human-score="interview.human_score"
                :review-status="interview.review_status"
            />

            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle>Human review</CardTitle>
                    <CardDescription>
                        Accept the AI score, edit it, or reject it. A note is required for the audit log.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="action">Decision</Label>
                        <select
                            id="action"
                            v-model="form.action"
                            class="border-input bg-background h-9 w-full rounded-md border px-3 text-sm"
                        >
                            <option value="accepted">Accept AI score</option>
                            <option value="edited">Edit score</option>
                            <option value="rejected">Reject AI score</option>
                        </select>
                        <InputError :message="errors.action" />
                    </div>
                    <div v-if="form.action === 'edited'" class="grid gap-2">
                        <Label for="human_score">Human score (0–100)</Label>
                        <input
                            id="human_score"
                            v-model.number="form.human_score"
                            type="number"
                            min="0"
                            max="100"
                            class="border-input bg-background h-9 w-full rounded-md border px-3 text-sm"
                        />
                        <InputError :message="errors.human_score" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="human_notes">Review notes</Label>
                        <textarea
                            id="human_notes"
                            v-model="form.human_notes"
                            class="border-input bg-background min-h-[120px] w-full rounded-md border px-3 py-2 text-sm"
                            placeholder="Explain why you accept, edit, or reject this evaluation."
                        />
                        <InputError :message="errors.human_notes" />
                    </div>
                    <Button @click="submitReview">Save review</Button>
                </CardContent>
            </Card>

            <Card v-if="interview.review_audit?.length" class="shadow-sm">
                <CardHeader>
                    <CardTitle>Audit log</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="(entry, index) in interview.review_audit"
                        :key="index"
                        class="rounded-md border p-3 text-sm"
                    >
                        <p class="font-medium">
                            {{ entry.action }}
                            <span v-if="entry.user_name"> · {{ entry.user_name }}</span>
                        </p>
                        <p class="text-muted-foreground mt-1">{{ entry.notes }}</p>
                        <p v-if="entry.human_score != null" class="mt-1">Score set to {{ entry.human_score }}</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
