<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->json('evaluation')->nullable()->after('feedback');
            $table->json('criteria')->nullable()->after('evaluation');
            $table->decimal('ai_score', 5, 2)->nullable()->after('score');
            $table->decimal('human_score', 5, 2)->nullable()->after('ai_score');
            $table->text('human_notes')->nullable()->after('human_score');
            $table->enum('review_status', ['pending_review', 'accepted', 'edited', 'rejected'])->nullable()->after('human_notes');
            $table->foreignId('reviewed_by')->nullable()->after('review_status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->json('review_audit')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'evaluation',
                'criteria',
                'ai_score',
                'human_score',
                'human_notes',
                'review_status',
                'reviewed_at',
                'review_audit',
            ]);
        });
    }
};
