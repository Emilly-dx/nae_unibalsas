<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agendamentos e atendimentos (SRS §10.2–10.5, RN-007, RN-008).
 * Um agendamento é um atendimento com status "scheduled"; o profissional
 * responsável registra o atendimento efetivo. O conteúdo do registro
 * (demanda, condução e combinados) é sigiloso e criptografado (RN-012).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('support_request_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('professional_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('scheduled_by')->constrained('users')->restrictOnDelete();
            $table->string('type', 30);
            $table->string('status', 20)->default('scheduled');
            $table->string('modality', 20)->default('presential');
            $table->boolean('is_first_conversation')->default(false);
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('planned_minutes')->default(50);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->text('demand')->nullable();
            $table->text('conduct')->nullable();
            $table->text('agreements')->nullable();
            $table->date('next_return_on')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['scheduled_at', 'status']);
            $table->index(['professional_id', 'scheduled_at']);
            $table->index(['student_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
