<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CID relacionado à deficiência/acessibilidade (SRS §9.2).
     * Informação sensível: código e descrição são criptografados
     * (cast `encrypted`), por isso usam `text`.
     */
    public function up(): void
    {
        Schema::create('student_disabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('cid_code');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_disabilities');
    }
};
