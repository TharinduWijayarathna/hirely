<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\HR\CandidateController;
use App\Http\Controllers\HR\JobController;
use App\Http\Controllers\JobSeeker\JobApplicationController;
use App\Http\Controllers\JobSeeker\PortfolioController;
use App\Http\Controllers\JobSeeker\SkillExpectationController;
use App\Http\Controllers\Payment\PaymentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Job Seeker Routes
    Route::get('cv-review', function () {
        return Inertia::render('job-seeker/CVReview');
    })->name('cv-review');

    Route::get('ats-scoring', function () {
        return Inertia::render('job-seeker/ATSScoring');
    })->name('ats-scoring');

    // Mock Interview
    Route::get('mock-interview', [\App\Http\Controllers\JobSeeker\MockInterviewController::class, 'index'])->name('mock-interview');
    Route::post('mock-interview', [\App\Http\Controllers\JobSeeker\MockInterviewController::class, 'store'])->name('mock-interview.store');
    Route::get('mock-interview/{session}', [\App\Http\Controllers\JobSeeker\MockInterviewController::class, 'session'])->name('mock-interview.session');
    Route::put('mock-interview/{session}', [\App\Http\Controllers\JobSeeker\MockInterviewController::class, 'update'])->name('mock-interview.update');
    Route::post('mock-interview/{session}/conversation', [\App\Http\Controllers\JobSeeker\MockInterviewController::class, 'processConversation'])->name('mock-interview.conversation');
    Route::get('mock-interview/{session}/initial', [\App\Http\Controllers\JobSeeker\MockInterviewController::class, 'getInitialMessage'])->name('mock-interview.initial');

    Route::get('profile-score', function () {
        return Inertia::render('job-seeker/ProfileScore');
    })->name('profile-score');

    // Portfolio CRUD
    Route::get('portfolio', [PortfolioController::class, 'index'])->name('portfolio');
    Route::post('portfolio', [PortfolioController::class, 'store'])->name('portfolio.store');
    Route::put('portfolio/{portfolio}', [PortfolioController::class, 'update'])->name('portfolio.update');
    Route::delete('portfolio/{portfolio}', [PortfolioController::class, 'destroy'])->name('portfolio.destroy');

    // Skill Expectations CRUD
    Route::get('skill-expectations', [SkillExpectationController::class, 'index'])->name('skill-expectations');
    Route::post('skill-expectations', [SkillExpectationController::class, 'store'])->name('skill-expectations.store');
    Route::put('skill-expectations/{skillExpectation}', [SkillExpectationController::class, 'update'])->name('skill-expectations.update');
    Route::delete('skill-expectations/{skillExpectation}', [SkillExpectationController::class, 'destroy'])->name('skill-expectations.destroy');

    // Job Applications
    Route::get('job-applications', [JobApplicationController::class, 'index'])->name('job-applications');
    Route::get('browse-jobs', [JobApplicationController::class, 'browse'])->name('browse-jobs');
    Route::post('job-applications', [JobApplicationController::class, 'store'])->name('job-applications.store');
    Route::delete('job-applications/{jobApplication}', [JobApplicationController::class, 'destroy'])->name('job-applications.destroy');

    // Payment Routes (HR and Job Seekers)
    Route::get('subscriptions', [PaymentController::class, 'index'])->name('subscriptions');
    Route::get('payments', [PaymentController::class, 'index'])->name('payments');
    Route::post('payment/checkout/{plan}', [PaymentController::class, 'checkout'])
        ->where('plan', '[0-9]+')
        ->name('payment.checkout');
    Route::get('payment/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
    Route::get('payment/billing-portal', [PaymentController::class, 'billingPortal'])->name('payment.billing-portal');
    Route::post('subscription/{subscription}/cancel', [PaymentController::class, 'cancelSubscription'])->name('subscription.cancel');
    Route::post('subscription/{subscription}/resume', [PaymentController::class, 'resumeSubscription'])->name('subscription.resume');

    // Jobs CRUD
    Route::get('post-jobs', [JobController::class, 'index'])->name('post-jobs');
    Route::post('post-jobs', [JobController::class, 'store'])->name('post-jobs.store');
    Route::put('post-jobs/{job}', [JobController::class, 'update'])->name('post-jobs.update');
    Route::delete('post-jobs/{job}', [JobController::class, 'destroy'])->name('post-jobs.destroy');

    // Review Candidates
    Route::get('review-candidates', [CandidateController::class, 'index'])->name('review-candidates');
    Route::put('review-candidates/{application}', [CandidateController::class, 'updateApplication'])->name('review-candidates.update');

    // Filter Candidates
    Route::get('filter-candidates', [CandidateController::class, 'filter'])->name('filter-candidates');

    // Admin Routes
    Route::get('analytics', function () {
        return Inertia::render('admin/Analytics');
    })->name('analytics');

    Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments');
    Route::get('payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');

    // Company Management CRUD
    Route::get('company-management', [CompanyController::class, 'index'])->name('company-management');
    Route::post('company-management', [CompanyController::class, 'store'])->name('company-management.store');
    Route::put('company-management/{company}', [CompanyController::class, 'update'])->name('company-management.update');
    Route::delete('company-management/{company}', [CompanyController::class, 'destroy'])->name('company-management.destroy');

    // User Management (Admins and HR Professionals only)
    Route::get('user-management', [UserManagementController::class, 'index'])->name('user-management');
    Route::post('user-management', [UserManagementController::class, 'store'])->name('user-management.store');
    Route::put('user-management/{user}', [UserManagementController::class, 'update'])->name('user-management.update');
    Route::delete('user-management/{user}', [UserManagementController::class, 'destroy'])->name('user-management.destroy');

    // Job Seeker Management
    Route::get('job-seeker-management', [\App\Http\Controllers\Admin\JobSeekerManagementController::class, 'index'])->name('job-seeker-management');
    Route::post('job-seeker-management', [\App\Http\Controllers\Admin\JobSeekerManagementController::class, 'store'])->name('job-seeker-management.store');
    Route::put('job-seeker-management/{user}', [\App\Http\Controllers\Admin\JobSeekerManagementController::class, 'update'])->name('job-seeker-management.update');
    Route::delete('job-seeker-management/{user}', [\App\Http\Controllers\Admin\JobSeekerManagementController::class, 'destroy'])->name('job-seeker-management.destroy');

    // HR Management
    Route::get('hr-management', [UserManagementController::class, 'hrIndex'])->name('hr-management');
    Route::post('hr-management', [UserManagementController::class, 'hrStore'])->name('hr-management.store');
    Route::put('hr-management/{user}', [UserManagementController::class, 'hrUpdate'])->name('hr-management.update');
    Route::delete('hr-management/{user}', [UserManagementController::class, 'hrDestroy'])->name('hr-management.destroy');
});

// API Routes can be added here if needed in the future

require __DIR__.'/settings.php';
