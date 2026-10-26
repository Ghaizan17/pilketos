<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('nipd')->unique();
            $table->string('nisn')->nullable()->unique();
            $table->enum('jk', ['L', 'P'])->nullable();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
