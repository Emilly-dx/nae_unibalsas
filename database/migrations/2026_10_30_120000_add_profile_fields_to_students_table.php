<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos do cadastro conforme os wireframes validados para o M3:
 * nome social, turno, modalidade, apoio/acessibilidade e consentimento (LGPD).
 * Os campos guardian_* passam a representar o "contato de emergência".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('social_name')->nullable()->after('name');
            $table->string('shift', 20)->nullable()->after('period');
            $table->string('modality', 20)->default('presential')->after('shift');
            $table->boolean('needs_accessibility')->default(false)->after('guardian_relationship');
            $table->json('accessibility_resources')->nullable()->after('needs_accessibility');
            $table->boolean('has_external_follow_up')->default(false)->after('accessibility_resources');
            $table->timestamp('consent_at')->nullable()->after('has_external_follow_up');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'social_name', 'shift', 'modality', 'needs_accessibility',
                'accessibility_resources', 'has_external_follow_up', 'consent_at',
            ]);
        });
    }
};
