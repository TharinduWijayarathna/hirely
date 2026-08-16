<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->longText('parsed_text')->nullable();
            $table->json('extraction')->nullable();
            $table->json('review')->nullable();
            $table->unsignedTinyInteger('review_score')->nullable();
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('ats_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cv_document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('job_postings')->nullOnDelete();
            $table->text('job_description');
            $table->unsignedTinyInteger('score')->nullable();
            $table->json('analysis')->nullable();
            $table->timestamps();
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->foreignId('cv_document_id')->nullable()->after('resume_path')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cv_document_id');
        });
        Schema::dropIfExists('ats_analyses');
        Schema::dropIfExists('cv_documents');
    }
};
