<?php

namespace App\Http\Controllers;

use App\Http\Resources\SiteResource;
use App\Models\AuditLog;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SiteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Site::with('zones');

        if ($request->boolean('active_only')) {
            $query->active();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return SiteResource::collection($query->orderBy('name')->paginate(15));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:sites,code',
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $site = Site::create($validated);

        AuditLog::log('Site Created', auth()->user(), 'Site', $site->id, ['name' => $site->name]);

        return (new SiteResource($site))->response()->setStatusCode(201);
    }

    public function show(Site $site): SiteResource
    {
        return new SiteResource($site->load('zones'));
    }

    public function update(Request $request, Site $site): SiteResource
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:20|unique:sites,code,' . $site->id,
            'name' => 'sometimes|string|max:255',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $site->update($validated);

        AuditLog::log('Site Updated', auth()->user(), 'Site', $site->id, ['name' => $site->name]);

        return new SiteResource($site);
    }

    public function destroy(Site $site): JsonResponse
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        AuditLog::log('Site Deleted', auth()->user(), 'Site', $site->id, ['name' => $site->name]);
        $site->delete();

        return response()->json(['message' => 'Site supprimé avec succès']);
    }
}
