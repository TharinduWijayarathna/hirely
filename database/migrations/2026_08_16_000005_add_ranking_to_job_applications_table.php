<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->decimal('ranking_score', 5, 2)->nullable()->after('notes');
            $table->unsignedInteger('ranking_position')->nullable()->after('ranking_score');
            $table->json('ranking_breakdown')->nullable()->after('ranking_position');
            $table->timestamp('ranked_at')->nullable()->after('ranking_breakdown');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'ranking_score',
                'ranking_position',
                'ranking_breakdown',
                'ranked_at',
            ]);
        });
    }
};
