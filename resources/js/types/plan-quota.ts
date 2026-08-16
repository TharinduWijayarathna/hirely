export type PlanQuota = {
    allowed: boolean;
    used?: number | null;
    limit?: number | null;
    plan_name?: string;
    billing_url?: string;
    message?: string | null;
};
