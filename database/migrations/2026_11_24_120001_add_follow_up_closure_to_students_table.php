<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Encerramento do acompanhamento (SRS §11.2). O acompanhamento em si é o
 * conjunto de atendimentos (RN-009); aqui só se registra quando, por quem
 * e por que ele foi encerrado. Um novo atendimento reabre o acompanhamento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->timestamp('follow_up_closed_at')->nullable()->after('consent_at');
            $table->foreignId('follow_up_closed_by')->nullable()->after('follow_up_closed_at')
                ->constrained('users')->nullOnDelete();
            $table->string('follow_up_closure_reason', 30)->nullable()->after('follow_up_closed_by');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('follow_up_closed_by');
            $table->dropColumn(['follow_up_closed_at', 'follow_up_closure_reason']);
        });
    }
};
