<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('role_id');
            $table->string('student_number', 20)->nullable()->unique();
            $table->string('name', 120);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->string('department', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('email_opt_in')->default(false);
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles');
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
