<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // basic, professional, enterprise
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('interval'); // month, year
            $table->string('stripe_price_id')->nullable(); // Stripe Price ID
            $table->string('stripe_product_id')->nullable(); // Stripe Product ID
            $table->json('features')->nullable(); // Array of features
            $table->boolean('is_active')->default(true);
            $table->enum('target_role', ['hr_professional', 'job_seeker', 'both'])->default('both');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
    }
};
