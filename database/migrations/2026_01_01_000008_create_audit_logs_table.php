<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('concern_id')->nullable();
            $table->string('action', 60);
            $table->string('old_value', 150)->nullable();
            $table->string('new_value', 150)->nullable();
            $table->string('reason', 300)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('concern_id')->references('id')->on('concerns')->cascadeOnDelete();
            $table->index('concern_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
