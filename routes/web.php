<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\JobSeekerManagementController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HR\CandidateController;
use App\Http\Controllers\HR\CompanySettingsController;
use App\Http\Controllers\HR\InterviewController as HrInterviewController;
use App\Http\Controllers\HR\InterviewResultsController;
use App\Http\Controllers\HR\InterviewTemplateController;
use App\Http\Controllers\HR\JobController;
use App\Http\Controllers\HR\RankingController;
use App\Http\Controllers\HR\ReportController;
use App\Http\Controllers\InterviewMediaController;
use App\Http\Controllers\JobSeeker\AtsScoringController;
use App\Http\Controllers\JobSeeker\CvController;
use App\Http\Controllers\JobSeeker\InterviewController as JobSeekerInterviewController;
use App\Http\Controllers\JobSeeker\JobApplicationController;
use App\Http\Controllers\JobSeeker\MockInterviewController;
use App\Http\Controllers\JobSeeker\PortfolioController;
use App\Http\Controllers\JobSeeker\ProfileScoreController;
use App\Http\Controllers\JobSeeker\SkillExpectationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\PublicJobController;
use App\Http\Controllers\PublicOrganizationController;
use App\Http\Controllers\StripeWebhookController;
use App\Models\Job;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'jobs' => Job::publiclyListed()
            ->with('company:id,name,slug')
            ->latest()
            ->take(20)
            ->get(['id', 'company_id', 'title', 'slug', 'location', 'type', 'remote']),
        'jobCount' => Job::publiclyListed()->count(),
    ]);
})->name('home');

Route::get('about', fn () => Inertia::render('public/About'))->name('about');

Route::get('jobs', [PublicJobController::class, 'index'])->name('jobs.index');
Route::get('jobs/{job:slug}', [PublicJobController::class, 'show'])->name('jobs.show');
Route::get('jobs/{job:slug}/apply', [PublicJobController::class, 'start'])
    ->middleware(['auth', 'verified', 'role:job_seeker'])
    ->name('jobs.apply');
Route::post('jobs/{job:slug}/apply', [PublicJobController::class, 'apply'])
    ->middleware(['auth', 'verified', 'role:job_seeker'])
    ->name('jobs.apply.store');

Route::get('organization', [PublicOrganizationController::class, 'index'])->name('organization.index');
Route::get('organization/register', [PublicOrganizationController::class, 'create'])
    ->middleware('guest')
    ->name('organization.register');
Route::post('organization/register', [PublicOrganizationController::class, 'store'])
    ->middleware('guest')
    ->name('organization.register.store');
Route::get('organization/{company:slug}', [PublicOrganizationController::class, 'show'])->name('organization.show');

Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('interview-media/{interview}/recording', [InterviewMediaController::class, 'recording'])->name('interview-media.recording');
    Route::get('interview-media/{interview}/screenshots/{index}', [InterviewMediaController::class, 'screenshot'])->name('interview-media.screenshot');
});

Route::middleware(['auth', 'verified', 'role:job_seeker'])->group(function () {
    Route::get('cv-review', [CvController::class, 'index'])->name('cv-review');
    Route::post('cv-review', [CvController::class, 'store'])->name('cv-review.store');
    Route::delete('cv-review/{cvDocument}', [CvController::class, 'destroy'])->name('cv-review.destroy');

    Route::get('ats-scoring', [AtsScoringController::class, 'index'])->name('ats-scoring');
    Route::post('ats-scoring', [AtsScoringController::class, 'store'])->name('ats-scoring.store');

    Route::get('mock-interview', [MockInterviewController::class, 'index'])->name('mock-interview');
    Route::post('mock-interview', [MockInterviewController::class, 'store'])->name('mock-interview.store');
    Route::get('mock-interview/{session}', [MockInterviewController::class, 'session'])->name('mock-interview.session');
    Route::put('mock-interview/{session}', [MockInterviewController::class, 'update'])->name('mock-interview.update');
    Route::post('mock-interview/{session}/follow-up', [MockInterviewController::class, 'followUp'])->name('mock-interview.follow-up');
    Route::post('mock-interview/{session}/conversation', [MockInterviewController::class, 'processConversation'])->name('mock-interview.conversation');
    Route::get('mock-interview/{session}/initial', [MockInterviewController::class, 'getInitialMessage'])->name('mock-interview.initial');
    Route::post('mock-interview/{session}/speech', [MockInterviewController::class, 'speech'])->name('mock-interview.speech');

    Route::get('profile-score', ProfileScoreController::class)->name('profile-score');

    Route::get('portfolio', [PortfolioController::class, 'index'])->name('portfolio');
    Route::post('portfolio', [PortfolioController::class, 'store'])->name('portfolio.store');
    Route::put('portfolio/{portfolio}', [PortfolioController::class, 'update'])->name('portfolio.update');
    Route::delete('portfolio/{portfolio}', [PortfolioController::class, 'destroy'])->name('portfolio.destroy');

    Route::get('skill-expectations', [SkillExpectationController::class, 'index'])->name('skill-expectations');
    Route::post('skill-expectations', [SkillExpectationController::class, 'store'])->name('skill-expectations.store');
    Route::put('skill-expectations/{skillExpectation}', [SkillExpectationController::class, 'update'])->name('skill-expectations.update');
    Route::delete('skill-expectations/{skillExpectation}', [SkillExpectationController::class, 'destroy'])->name('skill-expectations.destroy');

    Route::get('job-applications', [JobApplicationController::class, 'index'])->name('job-applications');
    Route::get('browse-jobs', [JobApplicationController::class, 'browse'])->name('browse-jobs');
    Route::post('job-applications', [JobApplicationController::class, 'store'])->name('job-applications.store');
    Route::delete('job-applications/{jobApplication}', [JobApplicationController::class, 'destroy'])->name('job-applications.destroy');

    Route::get('payments', [PaymentController::class, 'index'])->name('payments');

    Route::get('interviews', [JobSeekerInterviewController::class, 'index'])->name('interviews');
    Route::get('interviews/{interview}', [JobSeekerInterviewController::class, 'show'])->name('interviews.show');
    Route::put('interviews/{interview}', [JobSeekerInterviewController::class, 'update'])->name('interviews.update');
    Route::post('interviews/{interview}/follow-up', [JobSeekerInterviewController::class, 'followUp'])->name('interviews.follow-up');
    Route::post('interviews/{interview}/conversation', [JobSeekerInterviewController::class, 'conversation'])->name('interviews.conversation');
    Route::get('interviews/{interview}/initial', [JobSeekerInterviewController::class, 'initial'])->name('interviews.initial');
    Route::post('interviews/{interview}/speech', [JobSeekerInterviewController::class, 'speech'])->name('interviews.speech');
    Route::post('interviews/{interview}/screenshots', [JobSeekerInterviewController::class, 'storeScreenshot'])->name('interviews.screenshots.store');
    Route::post('interviews/{interview}/recording', [JobSeekerInterviewController::class, 'storeRecording'])->name('interviews.recording.store');
});

Route::middleware(['auth', 'verified', 'role:hr_professional'])->group(function () {
    Route::get('subscriptions', [PaymentController::class, 'index'])->name('subscriptions');

    Route::get('post-jobs', [JobController::class, 'index'])->name('post-jobs');
    Route::post('post-jobs', [JobController::class, 'store'])->name('post-jobs.store');
    Route::put('post-jobs/{job}', [JobController::class, 'update'])->name('post-jobs.update');
    Route::delete('post-jobs/{job}', [JobController::class, 'destroy'])->name('post-jobs.destroy');

    Route::get('review-candidates', [CandidateController::class, 'index'])->name('review-candidates');
    Route::put('review-candidates/{application}', [CandidateController::class, 'updateApplication'])->name('review-candidates.update');
    Route::post('review-candidates/{application}/interviews', [HrInterviewController::class, 'store'])->name('review-candidates.interviews.store');

    Route::get('filter-candidates', [CandidateController::class, 'filter'])->name('filter-candidates');

    Route::get('interview-templates', [InterviewTemplateController::class, 'index'])->name('interview-templates');
    Route::post('interview-templates', [InterviewTemplateController::class, 'store'])->name('interview-templates.store');
    Route::put('interview-templates/{interviewTemplate}', [InterviewTemplateController::class, 'update'])->name('interview-templates.update');
    Route::delete('interview-templates/{interviewTemplate}', [InterviewTemplateController::class, 'destroy'])->name('interview-templates.destroy');

    Route::get('interview-results', [InterviewResultsController::class, 'index'])->name('interview-results');
    Route::get('interview-results/{interview}', [InterviewResultsController::class, 'show'])->name('interview-results.show');
    Route::put('interview-results/{interview}/review', [InterviewResultsController::class, 'review'])->name('interview-results.review');

    Route::get('rankings', [RankingController::class, 'index'])->name('rankings');
    Route::get('rankings/{job}/compare', [RankingController::class, 'compare'])->name('rankings.compare');

    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports', ReportController::class)->name('reports');

    Route::get('company-settings', [CompanySettingsController::class, 'edit'])->name('company-settings');
    Route::put('company-settings', [CompanySettingsController::class, 'update'])->name('company-settings.update');
});

Route::middleware(['auth', 'verified', 'role:job_seeker,hr_professional'])->group(function () {
    Route::post('payment/checkout/{plan}', [PaymentController::class, 'checkout'])
        ->where('plan', '[0-9]+')
        ->name('payment.checkout');
    Route::get('payment/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
    Route::get('payment/billing-portal', [PaymentController::class, 'billingPortal'])->name('payment.billing-portal');
    Route::post('subscription/{subscription}/cancel', [PaymentController::class, 'cancelSubscription'])->name('subscription.cancel');
    Route::post('subscription/{subscription}/resume', [PaymentController::class, 'resumeSubscription'])->name('subscription.resume');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('analytics', AnalyticsController::class)->name('analytics');

    Route::get('admin/payments', [AdminPaymentController::class, 'index'])->name('admin.payments');
    Route::get('admin/payments/{payment}', [AdminPaymentController::class, 'show'])->name('admin.payments.show');

    Route::get('company-management', [CompanyController::class, 'index'])->name('company-management');
    Route::post('company-management', [CompanyController::class, 'store'])->name('company-management.store');
    Route::put('company-management/{company}', [CompanyController::class, 'update'])->name('company-management.update');
    Route::delete('company-management/{company}', [CompanyController::class, 'destroy'])->name('company-management.destroy');

    Route::get('user-management', [UserManagementController::class, 'index'])->name('user-management');
    Route::post('user-management', [UserManagementController::class, 'store'])->name('user-management.store');
    Route::put('user-management/{user}', [UserManagementController::class, 'update'])->name('user-management.update');
    Route::delete('user-management/{user}', [UserManagementController::class, 'destroy'])->name('user-management.destroy');

    Route::get('job-seeker-management', [JobSeekerManagementController::class, 'index'])->name('job-seeker-management');
    Route::post('job-seeker-management', [JobSeekerManagementController::class, 'store'])->name('job-seeker-management.store');
    Route::put('job-seeker-management/{user}', [JobSeekerManagementController::class, 'update'])->name('job-seeker-management.update');
    Route::delete('job-seeker-management/{user}', [JobSeekerManagementController::class, 'destroy'])->name('job-seeker-management.destroy');

    Route::get('hr-management', [UserManagementController::class, 'hrIndex'])->name('hr-management');
    Route::post('hr-management', [UserManagementController::class, 'hrStore'])->name('hr-management.store');
    Route::put('hr-management/{user}', [UserManagementController::class, 'hrUpdate'])->name('hr-management.update');
    Route::delete('hr-management/{user}', [UserManagementController::class, 'hrDestroy'])->name('hr-management.destroy');
});

require __DIR__.'/settings.php';
