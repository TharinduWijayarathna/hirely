<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    /**
     * Stripe statuses that grant the subscribed plan's features.
     *
     * @var list<string>
     */
    public const ENTITLED_STATUSES = ['active', 'trialing'];

    protected $fillable = [
        'user_id',
        'payment_plan_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'canceled_at',
        'cancel_at_period_end',
        'stripe_metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'stripe_metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isActive(): bool
    {
        if (! in_array($this->status, self::ENTITLED_STATUSES, true)) {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->isFuture();
    }

    public function isTrial(): bool
    {
        if ($this->status === 'trialing') {
            return $this->trial_ends_at === null || $this->trial_ends_at->isFuture();
        }

        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ENTITLED_STATUSES);
    }
}
