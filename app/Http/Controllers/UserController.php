<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Get(
        path: "/users",
        summary: "Liste des utilisateurs",
        description: "Récupère la liste des utilisateurs. Accessible uniquement aux administrateurs.",
        tags: ["Utilisateurs"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "role", in: "query", description: "Filtrer par rôle", schema: new OA\Schema(type: "string", enum: ["administrator", "supervisor", "operator", "user"])),
            new OA\Parameter(name: "search", in: "query", description: "Rechercher dans username, nom ou email", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "page", in: "query", description: "Numéro de page", schema: new OA\Schema(type: "integer", example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des utilisateurs paginée",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/User")),
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
        ]
    )]
    public function index(Request $request)
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->with('roles')->orderBy('full_name')->paginate(15);

        // Ajouter les rôles Spatie dans la réponse
        $users->getCollection()->transform(function ($user) {
            $user->spatie_roles = $user->roles->pluck('name')->toArray();
            return $user;
        });

        return response()->json($users);
    }

    public function genererNomUtilisateur($prenom, $nom) {
        if (empty($prenom) || empty($nom)) return '';
        
        $premierPrenom = explode(' ', $prenom)[0];
        $premiereLettreDuNom = substr($nom, 0, 1);
        
        return strtolower($premiereLettreDuNom . $premierPrenom);
    }

    #[OA\Post(
        path: "/users",
        summary: "Créer un utilisateur",
        description: "Crée un nouvel utilisateur. Accessible uniquement aux administrateurs.",
        tags: ["Utilisateurs"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["firstName", "lastName", "email", "password", "phone", "role"],
                    properties: [
                        new OA\Property(property: "firstName", type: "string", example: "Jean"),
                        new OA\Property(property: "lastName", type: "string", example: "Dupont"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "jean.dupont@example.com"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "password123", minLength: 8),
                        new OA\Property(property: "phone", type: "string", example: "+33123456789"),
                        new OA\Property(property: "role", type: "string", enum: ["user", "supervisor", "administrator", "director"], example: "user"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Utilisateur créé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(ref: "#/components/schemas/User")
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
            new OA\Response(response: 422, description: "Erreur de validation", ref: "#/components/schemas/Error"),
        ]
    )]
    public function store(Request $request)
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'firstName' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'role' => 'required|in:user,supervisor,administrator,director',
        ]);

        $user = User::create([
            'username' => $this->genererNomUtilisateur($request->firstName,$request->lastName),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'full_name' => $request->firstName.' '.$request->lastName,
            'phone' => $request->phone,
            'role' => $request->role,
        ]);

        // Assigner le rôle Spatie
        $user->assignRole($request->role);

        AuditLog::log(
            'User Created',
            auth()->user(),
            'User',
            $user->id,
            ['username' => $user->username, 'role' => $user->role]
        );

        $user->load('roles');
        $user->spatie_roles = $user->roles->pluck('name')->toArray();

        return response()->json($user, 201);
    }

    #[OA\Get(
        path: "/users/{user}",
        summary: "Détails d'un utilisateur",
        description: "Récupère les détails d'un utilisateur. Les utilisateurs peuvent voir leurs propres informations, les administrateurs peuvent voir toutes.",
        tags: ["Utilisateurs"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "user", in: "path", required: true, description: "ID de l'utilisateur", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Détails de l'utilisateur",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(ref: "#/components/schemas/User")
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé", ref: "#/components/schemas/Error"),
            new OA\Response(response: 404, description: "Utilisateur non trouvé", ref: "#/components/schemas/Error"),
        ]
    )]
    public function show(User $user)
    {
        if (!auth()->user()->isAdministrator() && auth()->id() !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $user->load('roles', 'permissions');
        $user->spatie_roles = $user->roles->pluck('name')->toArray();
        $user->spatie_permissions = $user->permissions->pluck('name')->toArray();

        return response()->json($user);
    }

    #[OA\Put(
        path: "/users/{user}",
        summary: "Mettre à jour un utilisateur",
        description: "Met à jour les informations d'un utilisateur. Les utilisateurs peuvent modifier leurs propres informations (sauf le rôle), les administrateurs peuvent tout modifier.",
        tags: ["Utilisateurs"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "user", in: "path", required: true, description: "ID de l'utilisateur", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "username", type: "string", example: "jdupont"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "jean.dupont@example.com"),
                        new OA\Property(property: "full_name", type: "string", example: "Jean Dupont"),
                        new OA\Property(property: "phone", type: "string", example: "+33123456789"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "newpassword123", minLength: 8),
                        new OA\Property(property: "role", type: "string", enum: ["user", "supervisor", "administrator"], description: "Uniquement modifiable par les administrateurs"),
                        new OA\Property(property: "is_active", type: "boolean", description: "Uniquement modifiable par les administrateurs"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Utilisateur mis à jour avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(ref: "#/components/schemas/User")
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé", ref: "#/components/schemas/Error"),
            new OA\Response(response: 404, description: "Utilisateur non trouvé", ref: "#/components/schemas/Error"),
        ]
    )]
    public function update(Request $request, User $user)
    {
        if (!auth()->user()->isAdministrator() && auth()->id() !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $rules = [
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'full_name' => 'sometimes|string|max:255',
        ];

        // Seul un admin peut changer le rôle
        if (auth()->user()->isAdministrator()) {
            $rules['role'] = 'sometimes|in:user,supervisor,administrator,director';
            $rules['is_active'] = 'sometimes|boolean';
        }

        if ($request->has('password')) {
            $rules['password'] = 'string|min:8';
        }

        $request->validate($rules);

        $data = $request->only(['username', 'email', 'full_name', 'phone']);

        if (auth()->user()->isAdministrator()) {
            $data = array_merge($data, $request->only(['role', 'is_active']));
        }

        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Mettre à jour le rôle Spatie si le rôle a changé
        if (auth()->user()->isAdministrator() && $request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        AuditLog::log(
            'User Updated',
            auth()->user(),
            'User',
            $user->id,
            ['username' => $user->username]
        );

        $user->load('roles');
        $user->spatie_roles = $user->roles->pluck('name')->toArray();

        return response()->json($user);
    }

    #[OA\Delete(
        path: "/users/{user}",
        summary: "Supprimer un utilisateur",
        description: "Supprime un utilisateur. Accessible uniquement aux administrateurs. Impossible de supprimer son propre compte.",
        tags: ["Utilisateurs"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "user", in: "path", required: true, description: "ID de l'utilisateur", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Utilisateur supprimé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "Utilisateur supprimé avec succès"),
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
            new OA\Response(response: 422, description: "Impossible de supprimer son propre compte", ref: "#/components/schemas/Error"),
        ]
    )]
    public function destroy(User $user)
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte'], 422);
        }

        AuditLog::log(
            'User Deleted',
            auth()->user(),
            'User',
            $user->id,
            ['username' => $user->username]
        );

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé avec succès']);
    }
}
