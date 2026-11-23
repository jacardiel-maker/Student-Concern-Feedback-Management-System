<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('concern_id');
            $table->unsignedBigInteger('reply_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('original_name', 255);
            $table->string('stored_path', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->timestamp('created_at')->nullable();

            $table->foreign('concern_id')->references('id')->on('concerns')->cascadeOnDelete();
            $table->foreign('reply_id')->references('id')->on('replies')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');
            $table->index('concern_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
