<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Support\Thumbnail;
use App\Traits\AuthorizesProjectAccess;
use App\Traits\ServesProjectMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectPhotoController extends Controller
{
    use AuthorizesProjectAccess, ServesProjectMedia;

    public function store(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $request->validate([
            'photos' => 'required|array|min:1',
            'photos.*' => 'required|file|mimes:jpeg,jpg,png,webp,gif,mp4,mov,avi|max:51200',
            'phase_id' => 'nullable|exists:project_phases,id,project_id,'.$project->id,
            'caption' => 'nullable|string|max:500',
            'taken_at' => 'nullable|date',
        ]);

        foreach ($request->file('photos') as $file) {
            $mime = $file->getMimeType();
            $isVideo = str_starts_with($mime, 'video/');
            $path = $file->store("projects/{$project->id}/photos", 'local');
            Photo::create([
                'project_id' => $project->id,
                'phase_id' => $request->phase_id,
                'caption' => $request->caption,
                'path' => $path,
                'thumbnail_path' => $isVideo ? null : Thumbnail::generate($path),
                'media_type' => $isVideo ? 'video' : 'image',
                'taken_at' => $request->taken_at ?: now()->toDateString(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        ProjectUpdate::log($project->id, 'Fotos adicionadas', 'foto');

        return back()->with('success', 'Fotos enviadas com sucesso.');
    }

    public function destroy(Project $project, Photo $photo)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $photo);

        foreach (array_filter([$photo->path, $photo->thumbnail_path]) as $file) {
            Storage::disk('local')->delete($file);
        }
        $photo->delete();

        return back()->with('success', 'Foto removida.');
    }

    public function cover(Project $project)
    {
        $this->authorizeProjectAccess($project);
        abort_unless($project->cover_image_path, 404);

        return $this->coverResponse($project->cover_image_path);
    }

    public function show(Request $request, Project $project, Photo $photo)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $photo);

        return $this->photoResponse($this->photoPath($photo, $request->boolean('thumb')));
    }

    /**
     * The thumbnail when one was asked for and exists, otherwise the original.
     */
    private function photoPath(Photo $photo, bool $wantsThumb): string
    {
        if ($wantsThumb && $photo->thumbnail_path && Storage::disk('local')->exists($photo->thumbnail_path)) {
            return $photo->thumbnail_path;
        }

        return $photo->path;
    }
}
