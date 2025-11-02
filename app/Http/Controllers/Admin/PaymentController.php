<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Display payment management dashboard
     */
    public function index(Request $request): Response
    {
        // Payment statistics
        $totalRevenue = Payment::successful()->sum('amount');
        $monthlyRevenue = Payment::successful()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        $activeSubscriptions = Subscription::active()->count();

        // Recent payments
        $recentPayments = Payment::with(['user', 'subscription', 'paymentPlan'])
            ->latest()
            ->paginate(20);

        // Revenue by month (last 12 months) - Database agnostic
        $revenueByMonth = Payment::successful()
            ->where('created_at', '>=', now()->subMonths(12))
            ->get()
            ->groupBy(function ($payment) {
                return $payment->created_at->format('Y-m');
            })
            ->map(function ($payments, $month) {
                return [
                    'month' => $month,
                    'revenue' => $payments->sum('amount'),
                ];
            })
            ->values()
            ->sortBy('month');

        // Subscription statistics
        $subscriptionsByStatus = Subscription::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return Inertia::render('admin/Payments', [
            'stats' => [
                'totalRevenue' => $totalRevenue,
                'monthlyRevenue' => $monthlyRevenue,
                'activeSubscriptions' => $activeSubscriptions,
            ],
            'recentPayments' => $recentPayments,
            'revenueByMonth' => $revenueByMonth,
            'subscriptionsByStatus' => $subscriptionsByStatus,
        ]);
    }

    /**
     * Show payment details
     */
    public function show(Payment $payment)
    {
        $payment->load(['user', 'subscription', 'paymentPlan']);

        return Inertia::render('admin/PaymentDetails', [
            'payment' => $payment,
        ]);
    }
}
