<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Encaminhamentos internos e externos (SRS §12, RF-034 a RF-040).
 * Motivo e retorno são criptografados: visíveis só para quem encaminhou e,
 * no interno, para o profissional de destino (RN-012).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('kind', 20);
            $table->foreignId('destination_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('destination_name')->nullable();
            $table->text('reason');
            $table->date('referred_on');
            $table->date('return_deadline')->nullable();
            $table->string('status', 20)->default('sent');
            $table->text('return_notes')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'referred_on']);
            $table->index(['student_id', 'referred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
