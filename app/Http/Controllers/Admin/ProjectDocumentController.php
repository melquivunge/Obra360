<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Traits\AuthorizesProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectDocumentController extends Controller
{
    use AuthorizesProjectAccess;

    public function store(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $request->validate([
            'file' => 'required|file|mimes:pdf,docx,xlsx,xls,doc,jpg,jpeg,png,webp|max:51200',
            'name' => 'required|string|max:200',
            'type' => 'required|in:'.implode(',', array_keys(Document::TYPES)),
            'visibility' => 'required|in:interno,cliente',
            'notes' => 'nullable|string|max:500',
        ]);

        $path = $request->file('file')->store("projects/{$project->id}/documents", 'local');

        $doc = Document::create([
            'project_id' => $project->id,
            'name' => $request->name,
            'type' => $request->type,
            'visibility' => $request->visibility,
            'path' => $path,
            'notes' => $request->notes,
            'uploaded_by' => auth()->id(),
        ]);

        if ($doc->isClientVisible()) {
            ProjectUpdate::log($project->id, 'Documento liberado: '.$doc->name, 'documento');
        }

        return back()->with('success', 'Documento enviado.');
    }

    public function destroy(Project $project, Document $document)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $document);

        Storage::disk('local')->delete($document->path);
        Storage::disk('public')->delete($document->path);
        $document->delete();

        return back()->with('success', 'Documento removido.');
    }

    public function download(Project $project, Document $document)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $document);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->name);
    }
}
