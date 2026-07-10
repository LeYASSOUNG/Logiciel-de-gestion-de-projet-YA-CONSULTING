<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Affiche le formulaire de création d'un paiement pour un projet donné.
     */
    public function createForProject(Project $project)
    {
        $this->authorize('update', $project);

        return Inertia::render('Payments/Create', [
            'project' => $project->only('id', 'name', 'budget', 'total_paid', 'balance_due'),
        ]);
    }

    /**
     * Enregistre le nouveau paiement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id'     => 'required|exists:projects,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'reference'      => 'nullable|string|max:255',
        ]);

        $project = Project::findOrFail($validated['project_id']);
        $this->authorize('update', $project);

        $payment = Payment::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        activity()->causedBy(Auth::user())
            ->performedOn($project)
            ->log('Paiement client enregistré');

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Paiement enregistré avec succès.');
    }

    /**
     * Supprime un paiement.
     */
    public function destroy(Payment $payment)
    {
        $project = $payment->project;
        $this->authorize('update', $project);

        $payment->delete();

        activity()->causedBy(Auth::user())
            ->performedOn($project)
            ->log('Paiement client supprimé');

        return back()->with('success', 'Paiement supprimé.');
    }
}
