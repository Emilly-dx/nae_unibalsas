<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Documentos anexados ao estudante (SRS §9.2, RF-017/RF-018, §15.8).
     * Os arquivos ficam no disco privado (`local` => storage/app/private).
     */
    public function up(): void
    {
        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'uploaded_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};
