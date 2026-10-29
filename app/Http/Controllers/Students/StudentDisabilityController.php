<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\StoreStudentDisabilityRequest;
use App\Models\Student;
use App\Models\StudentDisability;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * CID do estudante — informação sensível (SRS §9.2, §15.2, RN-012).
 */
class StudentDisabilityController extends Controller
{
    public function store(StoreStudentDisabilityRequest $request, Student $student): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $disability = new StudentDisability($request->validated());
        $disability->student()->associate($student);
        $disability->created_by = $user->id;
        $disability->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'CID registrado.']);

        return to_route('students.show', $student);
    }

    /**
     * Remove da visualização (soft delete). Não há exclusão definitiva (RN-013).
     */
    public function destroy(Student $student, StudentDisability $disability): RedirectResponse
    {
        Gate::authorize('delete', $disability);

        $disability->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'CID removido.']);

        return to_route('students.show', $student);
    }
}
