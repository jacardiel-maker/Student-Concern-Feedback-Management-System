<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concerns', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_no', 20)->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedSmallInteger('category_id');
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('subject', 150);
            $table->text('description');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['pending', 'under_review', 'in_progress', 'resolved', 'closed'])->default('pending');
            $table->boolean('is_anonymous')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('recipient_id')->references('id')->on('users')->nullOnDelete();

            $table->index('status');
            $table->index('category_id');
            $table->index('recipient_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concerns');
    }
};
