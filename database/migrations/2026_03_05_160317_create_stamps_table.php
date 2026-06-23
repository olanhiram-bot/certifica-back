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
        Schema::create('stamps', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false);
            $table->date('vigency_start_date')->nullable();
            $table->date('vigency_end_date')->nullable();
            $table->date('take_review_date')->nullable();
            $table->date('reject_date')->nullable();

            $table->foreignId('provider_id')->constrained('users');
            $table->foreignId('reviewer_id')->nullable()->constrained('users');
            $table->foreignId('course_call_id')->nullable()->constrained('course_calls');
            $table->foreignId('modality_type_id')->constrained('modality_types');
            $table->foreignId('stamp_status_id')->default(1)->constrained('stamp_statuses');

            $table->unsignedInteger('percent')->nullable();
            $table->unsignedInteger('percent_moduls')->default(0);
            $table->unsignedInteger('percent_evaluations')->default(0);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->string('deleted_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stamps');
    }
};
