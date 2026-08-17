<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { interviewTemplates } from '@/routes';
import interviewTemplatesRoutes from '@/routes/interview-templates';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ClipboardList, Plus, Edit, Trash2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
    templates?: Array<{
        id: number;
        name: string;
        job_id?: number | null;
        question_count: number;
        duration_minutes?: number | null;
        difficulty: string;
        mode: string;
        technical_percentage: number;
        behavioral_percentage: number;
        scenario_percentage: number;
        cv_percentage: number;
        evaluation_criteria?: string[] | null;
        question_weights?: Record<string, number> | null;
        is_active: boolean;
        job?: { id: number; title: string } | null;
    }>;
    jobs?: Array<{ id: number; title: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Interview Templates',
        href: interviewTemplates().url,
    },
];

const isDialogOpen = ref(false);
const editingTemplate = ref<any>(null);
const page = usePage();
const errors = computed(() => page.props.errors || {});
const criteriaInput = ref('');

const defaultWeights = (criteria: string[]): Record<string, number> => {
    const equal = Math.max(1, Math.floor(100 / Math.max(1, criteria.length)));
    return Object.fromEntries(criteria.map((name) => [name, equal]));
};

const defaultForm = () => {
    const evaluation_criteria = ['Technical depth', 'Communication', 'Problem solving'];

    return {
        name: '',
        job_id: '',
        question_count: 10,
        duration_minutes: 30,
        difficulty: 'intermediate',
        mode: 'voice',
        technical_percentage: 40,
        behavioral_percentage: 30,
        scenario_percentage: 20,
        cv_percentage: 10,
        evaluation_criteria,
        question_weights: defaultWeights(evaluation_criteria),
        is_active: true,
    };
};

const form = ref(defaultForm());

const mixTotal = computed(
    () =>
        Number(form.value.technical_percentage) +
        Number(form.value.behavioral_percentage) +
        Number(form.value.scenario_percentage) +
        Number(form.value.cv_percentage),
);

const openDialog = (template?: any) => {
    editingTemplate.value = template || null;
    if (template) {
        form.value = {
            name: template.name,
            job_id: template.job_id || '',
            question_count: template.question_count,
            duration_minutes: template.duration_minutes || 30,
            difficulty: template.difficulty,
            mode: template.mode,
            technical_percentage: template.technical_percentage,
            behavioral_percentage: template.behavioral_percentage,
            scenario_percentage: template.scenario_percentage,
            cv_percentage: template.cv_percentage,
            evaluation_criteria: template.evaluation_criteria || [],
            question_weights: template.question_weights || defaultWeights(template.evaluation_criteria || []),
            is_active: template.is_active,
        };
    } else {
        form.value = defaultForm();
    }
    criteriaInput.value = '';
    isDialogOpen.value = true;
};

const addCriterion = () => {
    const value = criteriaInput.value.trim();
    if (!value) return;
    form.value.evaluation_criteria.push(value);
    if (!form.value.question_weights[value]) {
        form.value.question_weights[value] = 1;
    }
    criteriaInput.value = '';
};

const removeCriterion = (index: number) => {
    const [removed] = form.value.evaluation_criteria.splice(index, 1);
    if (removed) {
        delete form.value.question_weights[removed];
    }
};

const submitForm = () => {
    const payload = { ...form.value, job_id: form.value.job_id || null };
    const options = {
        onSuccess: () => {
            isDialogOpen.value = false;
            editingTemplate.value = null;
        },
    };

    if (editingTemplate.value) {
        router.put(interviewTemplatesRoutes.update(editingTemplate.value.id).url, payload, options);
    } else {
        router.post(interviewTemplatesRoutes.store().url, payload, options);
    }
};

const deleteTemplate = (id: number) => {
    if (confirm('Delete this interview template?')) {
        router.delete(interviewTemplatesRoutes.destroy(id).url);
    }
};
</script>

<template>
    <Head title="Interview Templates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Interview Templates</h1>
                    <p class="text-muted-foreground mt-2">
                        Configure question mix, difficulty, duration, and evaluation criteria for candidate interviews.
                    </p>
                </div>
                <Button @click="openDialog()">
                    <Plus class="mr-2 h-4 w-4" />
                    New template
                </Button>
            </div>

            <div v-if="templates && templates.length > 0" class="grid gap-4 md:grid-cols-2">
                <Card v-for="template in templates" :key="template.id" class="shadow-sm">
                    <CardHeader>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <CardTitle>{{ template.name }}</CardTitle>
                                <CardDescription>
                                    {{ template.job?.title || 'Any job' }} · {{ template.question_count }} questions ·
                                    {{ template.duration_minutes || '—' }} min
                                </CardDescription>
                            </div>
                            <span
                                class="rounded px-2 py-1 text-xs"
                                :class="template.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                            >
                                {{ template.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <p class="text-sm text-muted-foreground">
                            {{ template.difficulty }} · {{ template.mode }} · Tech {{ template.technical_percentage }}% /
                            Behavioral {{ template.behavioral_percentage }}% / Scenario
                            {{ template.scenario_percentage }}% / CV {{ template.cv_percentage }}%
                        </p>
                        <div class="flex gap-2">
                            <Button variant="outline" size="sm" @click="openDialog(template)">
                                <Edit class="mr-1 h-4 w-4" />
                                Edit
                            </Button>
                            <Button variant="outline" size="sm" @click="deleteTemplate(template.id)">
                                <Trash2 class="mr-1 h-4 w-4" />
                                Delete
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
            <Card v-else class="shadow-sm">
                <CardContent class="flex flex-col items-center justify-center py-12 text-center">
                    <ClipboardList class="text-muted-foreground mb-4 h-12 w-12" />
                    <p class="text-muted-foreground text-sm">No templates yet. Create one to assign interviews to candidates.</p>
                </CardContent>
            </Card>

            <Dialog :open="isDialogOpen" @update:open="(val) => (isDialogOpen = val)">
                <DialogContent class="max-h-[90vh] max-w-2xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{{ editingTemplate ? 'Edit template' : 'New interview template' }}</DialogTitle>
                        <DialogDescription>Question mix must add up to 100%.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="job_id">Job (optional)</Label>
                            <select id="job_id" v-model="form.job_id" class="border-input bg-background h-9 rounded-md border px-3 text-sm">
                                <option value="">Any job</option>
                                <option v-for="job in jobs" :key="job.id" :value="job.id">{{ job.title }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="question_count">Questions</Label>
                                <Input id="question_count" v-model.number="form.question_count" type="number" min="1" max="20" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="duration_minutes">Duration (minutes)</Label>
                                <Input id="duration_minutes" v-model.number="form.duration_minutes" type="number" min="5" max="180" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="difficulty">Difficulty</Label>
                                <select id="difficulty" v-model="form.difficulty" class="border-input bg-background h-9 rounded-md border px-3 text-sm">
                                    <option value="beginner">Beginner</option>
                                    <option value="intermediate">Intermediate</option>
                                    <option value="advanced">Advanced</option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="mode">Mode</Label>
                                <select id="mode" v-model="form.mode" class="border-input bg-background h-9 rounded-md border px-3 text-sm">
                                    <option value="voice">Voice assistant</option>
                                    <option value="text">Text</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label>Technical %</Label>
                                <Input v-model.number="form.technical_percentage" type="number" min="0" max="100" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Behavioral %</Label>
                                <Input v-model.number="form.behavioral_percentage" type="number" min="0" max="100" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Scenario %</Label>
                                <Input v-model.number="form.scenario_percentage" type="number" min="0" max="100" />
                            </div>
                            <div class="grid gap-2">
                                <Label>CV-based %</Label>
                                <Input v-model.number="form.cv_percentage" type="number" min="0" max="100" />
                            </div>
                        </div>
                        <p class="text-sm" :class="mixTotal === 100 ? 'text-muted-foreground' : 'text-destructive'">
                            Mix total: {{ mixTotal }}%
                        </p>
                        <InputError :message="errors.technical_percentage" />
                        <div class="grid gap-2">
                            <Label>Evaluation criteria</Label>
                            <div class="flex gap-2">
                                <Input v-model="criteriaInput" placeholder="Add a criterion" @keyup.enter="addCriterion" />
                                <Button type="button" variant="outline" @click="addCriterion">Add</Button>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="(criterion, index) in form.evaluation_criteria"
                                    :key="index"
                                    class="bg-secondary flex items-center gap-2 rounded px-2 py-1 text-xs"
                                >
                                    {{ criterion }}
                                    <input
                                        v-model.number="form.question_weights[criterion]"
                                        type="number"
                                        min="1"
                                        max="100"
                                        class="border-input bg-background h-6 w-14 rounded border px-1 text-xs"
                                        :aria-label="`${criterion} weight`"
                                    />
                                    <button type="button" class="ml-1" @click="removeCriterion(index)">×</button>
                                </span>
                            </div>
                            <p class="text-muted-foreground text-xs">
                                Weights are used as a weighted average of criterion scores (not required to total 100).
                            </p>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.is_active" type="checkbox" />
                            Active
                        </label>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isDialogOpen = false">Cancel</Button>
                        <Button :disabled="mixTotal !== 100" @click="submitForm">Save template</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
