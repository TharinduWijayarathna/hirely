<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->json('limits')->nullable()->after('features');
        });

        DB::table('payment_plans')
            ->where('target_role', 'hr_professional')
            ->where('amount', 0)
            ->update(['limits' => json_encode(['jobs' => 5, 'reports' => false])]);

        DB::table('payment_plans')
            ->where('target_role', 'hr_professional')
            ->where('amount', '>', 0)
            ->update(['limits' => json_encode(['jobs' => null, 'reports' => true])]);

        DB::table('payment_plans')
            ->where('target_role', 'job_seeker')
            ->where('amount', 0)
            ->update(['limits' => json_encode([
                'mock_interviews_per_month' => 3,
                'cv_documents' => 1,
                'ats' => false,
            ])]);

        DB::table('payment_plans')
            ->where('target_role', 'job_seeker')
            ->where('amount', '>', 0)
            ->update(['limits' => json_encode([
                'mock_interviews_per_month' => null,
                'cv_documents' => null,
                'ats' => true,
            ])]);
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn('limits');
        });
    }
};
