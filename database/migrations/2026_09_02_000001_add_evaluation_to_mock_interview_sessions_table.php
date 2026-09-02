<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mock_interview_sessions', function (Blueprint $table) {
            $table->json('evaluation')->nullable()->after('feedback');
        });
    }

    public function down(): void
    {
        Schema::table('mock_interview_sessions', function (Blueprint $table) {
            $table->dropColumn('evaluation');
        });
    }
};
