<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitações de apoio ao NAE (Formulário de Solicitação do Apoio) e a
 * triagem feita pela Psicopedagoga (SRS §10.1, RN-001, RN-002).
 * Descrição e parecer são criptografados (dados sensíveis, §15.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('requester_name');
            $table->string('requester_email')->nullable();
            $table->string('origin', 30);
            $table->json('reasons')->nullable();
            $table->text('description');
            $table->boolean('student_aware')->default(true);
            $table->string('status', 30)->default('pending_triage');
            $table->string('priority', 20)->default('normal');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('triaged_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('triage_notes')->nullable();
            $table->timestamp('triaged_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};
