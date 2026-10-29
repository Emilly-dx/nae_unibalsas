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
            $table->string('name');
            $table->string('registration')->unique();
            $table->string('course');
            $table->unsignedTinyInteger('period');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone', 20)->nullable();
            $table->string('guardian_relationship', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('course');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
