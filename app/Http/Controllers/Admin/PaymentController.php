<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Project;
use App\Traits\AuthorizesProjectAccess;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use AuthorizesProjectAccess;

    public function store(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:boleto,pix',
            'status' => 'required|in:pending,paid,cancelled',
            'due_date' => 'nullable|date',
            'barcode' => 'nullable|string|max:60',
            'pix_key' => 'nullable|string|max:100',
            'pix_qrcode' => 'nullable|string|max:2000',
        ]);

        $data['client_id'] = $project->client_id;

        if ($data['status'] === 'paid') {
            $data['paid_at'] = now();
        }

        $project->payments()->create($data);

        return back()->with('success', __('Cobrança registrada.'));
    }

    public function update(Request $request, Project $project, Payment $payment)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $payment);

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:boleto,pix',
            'status' => 'required|in:pending,paid,cancelled',
            'due_date' => 'nullable|date',
            'barcode' => 'nullable|string|max:60',
            'pix_key' => 'nullable|string|max:100',
            'pix_qrcode' => 'nullable|string|max:2000',
        ]);

        if ($data['status'] === 'paid' && ! $payment->paid_at) {
            $data['paid_at'] = now();
        } elseif ($data['status'] !== 'paid') {
            $data['paid_at'] = null;
        }

        $payment->update($data);

        return back()->with('success', __('Cobrança atualizada.'));
    }

    public function markPaid(Project $project, Payment $payment)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $payment);

        $payment->update(['status' => 'paid', 'paid_at' => now()]);

        return back()->with('success', __('Cobrança marcada como paga.'));
    }

    public function destroy(Project $project, Payment $payment)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $payment);

        $payment->delete();

        return back()->with('success', __('Cobrança removida.'));
    }
}
