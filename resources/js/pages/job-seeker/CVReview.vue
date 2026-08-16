<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { cvReview } from '@/routes';
import cvReviewRoutes from '@/routes/cv-review';
import InputError from '@/components/InputError.vue';
import PlanQuotaNotice from '@/components/PlanQuotaNotice.vue';
import { type PlanQuota } from '@/types/plan-quota';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Upload, FileText, CheckCircle2, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Extraction {
    full_name?: string | null;
    email?: string | null;
    summary?: string | null;
    skills?: string[];
    technologies?: string[];
    education?: Array<{ institution?: string; degree?: string; field?: string }>;
    experience?: Array<{ company?: string; title?: string; description?: string }>;
    qualifications?: string[];
    projects?: Array<{ name?: string; description?: string; technologies?: string[] }>;
    certifications?: Array<{ name?: string; issuer?: string }>;
    relevant_experience?: string[];
    experience_years?: number;
    experience_level?: string;
}

interface Review {
    score?: number;
    summary?: string;
    strengths?: string[];
    improvements?: string[];
}

interface CvDocument {
    id: number;
    original_name: string;
    status: string;
    review_score?: number | null;
    extraction?: Extraction | null;
    review?: Review | null;
    error_message?: string | null;
    created_at: string;
}

const props = defineProps<{
    documents?: CvDocument[];
    quota?: PlanQuota;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'CV Review', href: cvReview().url }];
const page = usePage();
const errors = computed(() => page.props.errors || {});
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const uploading = ref(false);
const canUpload = computed(() => props.quota?.allowed !== false);

const latest = computed(() => props.documents?.[0] ?? null);

const chooseFile = () => {
    if (!canUpload.value) {
        return;
    }
    fileInput.value?.click();
};

const onFile = (event: Event) => {
    const input = event.target as HTMLInputElement;
    selectedFile.value = input.files?.[0] ?? null;
};

const upload = () => {
    if (!canUpload.value || !selectedFile.value) return;
    uploading.value = true;
    router.post(cvReviewRoutes.store().url, { cv: selectedFile.value }, {
        forceFormData: true,
        onFinish: () => {
            uploading.value = false;
            selectedFile.value = null;
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const remove = (id: number) => {
    if (confirm('Remove this CV?')) {
        router.delete(cvReviewRoutes.destroy(id).url);
    }
};

const list = (items?: string[]) => items?.filter(Boolean) ?? [];
</script>

<template>
    <Head title="CV Review" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">CV Review</h1>
                <p class="text-muted-foreground mt-2">
                    Upload a PDF or DOCX resume. We extract your background and score the CV.
                </p>
                <PlanQuotaNotice class="mt-3" :quota="quota" />
            </div>

            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle>Upload Your Resume</CardTitle>
                    <CardDescription>Supported formats: PDF, DOCX (Max 10MB)</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <input ref="fileInput" type="file" class="hidden" accept=".pdf,.docx,application/pdf" @change="onFile" />
                    <div
                        class="hover:border-primary/50 flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed p-12 transition-colors"
                        @click="chooseFile"
                    >
                        <Upload class="text-muted-foreground mb-4 h-12 w-12" />
                        <p class="text-muted-foreground mb-2 text-sm">
                            {{ selectedFile ? selectedFile.name : 'Click to choose a file' }}
                        </p>
                        <Button type="button" variant="outline" @click.stop="chooseFile">
                            <Upload class="mr-2 h-4 w-4" />
                            Choose File
                        </Button>
                    </div>
                    <InputError :message="errors.cv" />
                    <InputError :message="errors.plan" />
                    <Button :disabled="!canUpload || !selectedFile || uploading" @click="upload">Analyze CV</Button>
                </CardContent>
            </Card>

            <Card v-if="latest && latest.status === 'processed'" class="shadow-sm">
                <CardHeader>
                    <CardTitle class="flex items-center justify-between">
                        <span>{{ latest.original_name }}</span>
                        <span class="text-primary text-2xl font-bold">{{ latest.review_score ?? '—' }}/100</span>
                    </CardTitle>
                    <CardDescription>{{ latest.review?.summary }}</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-6 md:grid-cols-2">
                    <div>
                        <h3 class="mb-2 font-semibold">Strengths</h3>
                        <ul class="text-muted-foreground list-disc space-y-1 pl-5 text-sm">
                            <li v-for="item in list(latest.review?.strengths)" :key="item">{{ item }}</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-2 font-semibold">Improvements</h3>
                        <ul class="text-muted-foreground list-disc space-y-1 pl-5 text-sm">
                            <li v-for="item in list(latest.review?.improvements)" :key="item">{{ item }}</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-2 font-semibold">Skills</h3>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="skill in list(latest.extraction?.skills)" :key="skill" class="bg-secondary rounded px-2 py-1 text-xs">
                                {{ skill }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <h3 class="mb-2 font-semibold">Technologies</h3>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="tech in list(latest.extraction?.technologies)"
                                :key="tech"
                                class="bg-secondary rounded px-2 py-1 text-xs"
                            >
                                {{ tech }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <h3 class="mb-2 font-semibold">Education</h3>
                        <p v-for="(item, index) in latest.extraction?.education || []" :key="index" class="text-muted-foreground text-sm">
                            {{ item.degree }} {{ item.field }} — {{ item.institution }}
                        </p>
                    </div>
                    <div>
                        <h3 class="mb-2 font-semibold">Experience</h3>
                        <p class="text-muted-foreground mb-2 text-sm">
                            {{ latest.extraction?.experience_years || 0 }} years ·
                            {{ latest.extraction?.experience_level || 'entry' }}
                        </p>
                        <p v-for="(item, index) in latest.extraction?.experience || []" :key="index" class="text-muted-foreground text-sm">
                            {{ item.title }} at {{ item.company }}
                        </p>
                    </div>
                    <div>
                        <h3 class="mb-2 font-semibold">Projects</h3>
                        <p v-for="(item, index) in latest.extraction?.projects || []" :key="index" class="text-muted-foreground text-sm">
                            {{ item.name }}
                        </p>
                    </div>
                    <div>
                        <h3 class="mb-2 font-semibold">Certifications & qualifications</h3>
                        <p v-for="item in list(latest.extraction?.qualifications)" :key="item" class="text-muted-foreground text-sm">{{ item }}</p>
                        <p
                            v-for="(item, index) in latest.extraction?.certifications || []"
                            :key="'c-' + index"
                            class="text-muted-foreground text-sm"
                        >
                            {{ item.name }} {{ item.issuer ? `(${item.issuer})` : '' }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <div>
                <h2 class="mb-4 text-xl font-semibold">Recent uploads</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="document in documents" :key="document.id" class="shadow-sm">
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-lg">{{ document.original_name }}</CardTitle>
                                <CheckCircle2 v-if="document.status === 'processed'" class="h-5 w-5 text-green-500" />
                                <FileText v-else class="text-muted-foreground h-5 w-5" />
                            </div>
                            <CardDescription>
                                {{ document.status }} · {{ new Date(document.created_at).toLocaleDateString() }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="flex items-center justify-between">
                            <p class="text-muted-foreground text-sm">
                                Score: {{ document.review_score ?? '—' }}/100
                            </p>
                            <Button variant="outline" size="sm" @click="remove(document.id)">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
