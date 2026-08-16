<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('job_postings')->nullOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('question_count')->default(5);
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('intermediate');
            $table->enum('mode', ['text', 'voice'])->default('text');
            $table->unsignedTinyInteger('technical_percentage')->default(40);
            $table->unsignedTinyInteger('behavioral_percentage')->default(30);
            $table->unsignedTinyInteger('scenario_percentage')->default(20);
            $table->unsignedTinyInteger('cv_percentage')->default(10);
            $table->json('evaluation_criteria')->nullable();
            $table->json('question_weights')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_templates');
    }
};
