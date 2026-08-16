<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __invoke(DashboardService $dashboards): Response
    {
        return Inertia::render('admin/Analytics', $dashboards->admin());
    }
}
