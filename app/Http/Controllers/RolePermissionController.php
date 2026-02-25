<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use OpenApi\Attributes as OA;

class RolePermissionController extends Controller
{
    #[OA\Get(
        path: "/roles",
        summary: "Liste des rôles",
        description: "Récupère la liste de tous les rôles avec leurs permissions et le nombre d'utilisateurs",
        tags: ["Rôles et Permissions"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des rôles",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé", ref: "#/components/schemas/Error"),
        ]
    )]
    public function index()
    {
        $user = auth()->user();
        
        // Vérifier si l'utilisateur a la permission ou est administrateur
        if (!$user->hasPermissionTo('users.view') && !$user->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'guard_name' => $permission->guard_name,
                    ];
                })->values()->toArray(), // Convertir en tableau
                'user_count' => User::role($role->name)->count(),
            ];
        })->values()->toArray(); // Convertir en tableau

        return response()->json(['data' => $roles]);
    }

    #[OA\Get(
        path: "/permissions",
        summary: "Liste des permissions",
        description: "Récupère la liste de toutes les permissions disponibles",
        tags: ["Rôles et Permissions"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des permissions",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé", ref: "#/components/schemas/Error"),
        ]
    )]
    public function permissions()
    {
        $user = auth()->user();
        
        // Vérifier si l'utilisateur a la permission ou est administrateur
        if (!$user->hasPermissionTo('users.view') && !$user->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $permissions = Permission::all()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
            ];
        })->values()->toArray(); // Convertir en tableau

        return response()->json(['data' => $permissions]);
    }

    #[OA\Put(
        path: "/roles/{role}/permissions",
        summary: "Mettre à jour les permissions d'un rôle",
        description: "Met à jour les permissions assignées à un rôle",
        tags: ["Rôles et Permissions"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "role", in: "path", required: true, description: "ID ou nom du rôle", schema: new OA\Schema(type: "string")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["permissions"],
                    properties: [
                        new OA\Property(property: "permissions", type: "array", items: new OA\Items(type: "string"), example: ["requests.create", "requests.view.all"]),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Permissions mises à jour avec succès",
            ),
            new OA\Response(response: 403, description: "Non autorisé", ref: "#/components/schemas/Error"),
            new OA\Response(response: 404, description: "Rôle non trouvé", ref: "#/components/schemas/Error"),
        ]
    )]
    public function updatePermissions(Request $request, $roleId)
    {
        if (!auth()->user()->hasPermissionTo('users.update')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        // Essayer de trouver le rôle par ID d'abord, puis par nom
        if (is_numeric($roleId)) {
            $role = Role::find($roleId);
        } else {
            $role = Role::where('name', $roleId)->first();
        }
        
        if (!$role) {
            return response()->json(['message' => 'Rôle non trouvé'], 404);
        }

        $role->syncPermissions($request->permissions);

        // Recharger le rôle avec les permissions
        $role->load('permissions');

        return response()->json([
            'message' => 'Permissions mises à jour avec succès',
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'guard_name' => $permission->guard_name,
                    ];
                }),
            ]
        ]);
    }
}
