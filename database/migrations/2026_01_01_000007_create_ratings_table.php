<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('concern_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('score');
            $table->string('comment', 300)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('concern_id')->references('id')->on('concerns')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
