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
        Schema::create('stamp_activities', function (Blueprint $table) {
            $table->id();
            $table->integer('score')->default(0);
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('extension')->nullable();
            $table->decimal('file_size')->nullable();
            $table->string('unit_size')->nullable();
            $table->dateTime('upload_date')->nullable();
            $table->boolean('material_was_view')->default(false);
            $table->unsignedBigInteger('stamp_id')->constrained();
            $table->foreign('stamp_id')->references('id')->on('stamps');
            $table->unsignedBigInteger('activity_id')->constrained();
            $table->foreign('activity_id')->references('id')->on('activities');
            $table->unsignedBigInteger('stamp_activity_status_id')->constrained();
            $table->foreign('stamp_activity_status_id')->references('id')->on('stamp_activity_statuses');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->string('deleted_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stamp_activities');
    }
};
