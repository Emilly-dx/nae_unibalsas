<?php

namespace App\Http\Controllers\Students;

use App\Enums\FollowUp\FollowUpClosureReason;
use App\Enums\FollowUp\FollowUpStatus;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Encerramento do acompanhamento (SRS §11.2). Um novo atendimento
 * registrado depois do encerramento reabre o acompanhamento.
 */
class FollowUpController extends Controller
{
    public function close(Request $request, Student $student): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($student->followUpStatus() === FollowUpStatus::Active, 409, 'O estudante não está em acompanhamento.');

        $data = $request->validate(
            ['reason' => ['required', Rule::enum(FollowUpClosureReason::class)]],
            ['reason.required' => 'Selecione o motivo do encerramento.'],
        );

        $student->follow_up_closed_at = now()->toImmutable();
        $student->follow_up_closed_by = $user->id;
        $student->follow_up_closure_reason = FollowUpClosureReason::from((string) $data['reason']);
        $student->saveQuietly();

        activity('students')
            ->performedOn($student)
            ->event('follow_up_closed')
            ->withProperties(['reason' => $student->follow_up_closure_reason->value])
            ->log('follow_up_closed');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Acompanhamento encerrado.']);

        return to_route('students.show', $student);
    }
}
