<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('replies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('concern_id');
            $table->unsignedBigInteger('user_id');
            $table->text('body');
            $table->boolean('is_internal_note')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->foreign('concern_id')->references('id')->on('concerns')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');
            $table->index('concern_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replies');
    }
};
