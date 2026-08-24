<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Setting;
use App\Traits\AuthorizesProjectAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    use AuthorizesProjectAccess;

    public const TYPES = [
        'geral' => 'Relatório geral',
        'financeiro' => 'Relatório financeiro',
        'fotos' => 'Relatório de fotos',
        'fases' => 'Relatório de fases',
    ];

    /** Photos embedded in a PDF, capped so the file stays openable. */
    private const PDF_PHOTO_LIMIT = 24;

    /**
     * Project picker — the entry point behind the "Relatórios" sidebar item.
     */
    public function index(Request $request)
    {
        $query = Project::with('client')->latest();

        // Só o gestor é limitado às suas obras. O financeiro faz relatórios de
        // todas elas — ver App\Traits\AuthorizesProjectAccess.
        if (auth()->user()->isManager()) {
            $query->where('manager_id', auth()->id());
        }

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->q.'%');
        }

        return view('admin.reports.index', [
            'projects' => $query->paginate(20)->withQueryString(),
            'types' => self::TYPES,
        ]);
    }

    public function show(Project $project, string $type)
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);
        $this->authorizeProjectAccess($project);

        return view('admin.reports.show', $this->data($project, $type) + ['pdf' => false]);
    }

    public function pdf(Project $project, string $type)
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);
        $this->authorizeProjectAccess($project);

        $data = $this->data($project, $type) + ['pdf' => true];

        $pdf = Pdf::loadView('admin.reports.pdf', $data)->setPaper('a4');

        // Never let report content pull remote resources — the HTML is built from
        // user-supplied project data.
        $pdf->getDomPDF()->getOptions()->setIsRemoteEnabled(false);

        return $pdf->download($this->filename($project, $type));
    }

    /**
     * @return array<string, mixed>
     */
    private function data(Project $project, string $type): array
    {
        $project->load(['client', 'manager']);

        $data = [
            'project' => $project,
            'type' => $type,
            'title' => self::TYPES[$type],
            'generatedAt' => now(),
            'companyName' => Setting::get('company_name', 'Obra360'),
        ];

        return $data + match ($type) {
            'geral' => [
                'phases' => $project->phases()->get(),
                'totalSpent' => $project->totalSpent(),
                'updates' => $project->updates()->with('user')->limit(20)->get(),
            ],
            'financeiro' => [
                'totalSpent' => $project->totalSpent(),
                'byCategory' => $project->expensesByCategory(),
                'byMonth' => $project->expensesByMonth(),
                'expenses' => $project->expenses()->get(),
                'payments' => $project->payments()->get(),
            ],
            'fotos' => [
                'photos' => $project->photos()->with('phase')->get(),
            ],
            'fases' => [
                'phases' => $project->phases()->get(),
            ],
        };
    }

    private function filename(Project $project, string $type): string
    {
        $slug = Str::slug($project->name) ?: $project->noun().'-'.$project->id;

        return "relatorio-{$type}-{$slug}-".now()->format('Y-m-d').'.pdf';
    }

    /**
     * Photo bytes as a data URI. PDFs cannot hit the authenticated media routes,
     * and the disk is private, so the image has to be embedded.
     */
    public static function embed(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('local')->mimeType($path) ?: 'image/jpeg';

        if (! str_starts_with($mime, 'image/')) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($path));
    }

    public static function photoLimit(): int
    {
        return self::PDF_PHOTO_LIMIT;
    }
}
