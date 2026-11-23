<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->tinyIncrements('id');
            $table->string('name', 30)->unique();
            $table->string('description', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
