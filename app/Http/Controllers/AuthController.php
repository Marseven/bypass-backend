<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: "/auth/login",
        summary: "Connexion utilisateur",
        description: "Authentifie un utilisateur et retourne un token d'accès",
        tags: ["Authentification"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["identifier", "password"],
                    properties: [
                        new OA\Property(property: "identifier", type: "string", example: "admin ou admin@exemple.com"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Connexion réussie",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "status", type: "integer", example: 200),
                            new OA\Property(
                                property: "data",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "user", ref: "#/components/schemas/User"),
                                    new OA\Property(property: "token", type: "string", example: "1|xxxxxxxxxxxxx"),
                                ]
                            ),
                            new OA\Property(property: "message", type: "array", items: new OA\Items(type: "string"), example: ["Connexion réussie"]),
                        ]
                    )
                )
            ),
            new OA\Response(response: 422, description: "Erreur de validation", ref: "#/components/schemas/Error"),
        ]
    )]
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'nullable|string',
            'username' => 'nullable|string',
            'email' => 'nullable|email',
            'password' => 'required|string',
        ]);

        $identifier = $request->input('identifier')
            ?? $request->input('username')
            ?? $request->input('email');

        if (!$identifier) {
            throw ValidationException::withMessages([
                'identifier' => ['Veuillez fournir un email ou un nom d’utilisateur.'],
            ]);
        }

        $userQuery = User::where('is_active', true)
            ->where(function ($query) use ($identifier) {
                $query->where('username', $identifier)
                      ->orWhere('email', $identifier);
            });

        $user = $userQuery->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 401,
                'data' => [],
                'message' => ['Les informations d\'identification fournies sont incorrectes.'],
            ], 401);
        }

        // If 2FA is enabled, return a temporary token for OTP verification
        if ($user->two_fa_enabled) {
            $tempToken = $user->createToken('2fa-verify', ['2fa-verify'], now()->addMinutes(5))->plainTextToken;

            return response()->json([
                'status' => 200,
                'requires_2fa' => true,
                'temp_token' => $tempToken,
                'message' => ['Vérification 2FA requise.'],
            ]);
        }

        $token = $user->createToken('auth-token', ['*'])->plainTextToken;

        AuditLog::log('User Login', $user);

        return response()->json([
            'status' => 200,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
            'message' => ['Connexion réussie'],
        ]);
    }

    #[OA\Post(
        path: "/auth/logout",
        summary: "Déconnexion utilisateur",
        description: "Invalide le token d'accès actuel de l'utilisateur",
        tags: ["Authentification"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Déconnexion réussie",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "Déconnexion réussie"),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié", ref: "#/components/schemas/Error"),
        ]
    )]
    public function logout(Request $request)
    {
        AuditLog::log('User Logout', $request->user());
        
        $token = $request->user()->currentAccessToken();
        if (method_exists($token, 'delete')) {
            $token->delete();
        }

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    #[OA\Get(
        path: "/auth/me",
        summary: "Informations utilisateur connecté",
        description: "Retourne les informations de l'utilisateur actuellement authentifié",
        tags: ["Authentification"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Informations utilisateur",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(ref: "#/components/schemas/User")
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié", ref: "#/components/schemas/Error"),
        ]
    )]
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            ...$user->toArray(),
            'two_fa_enabled' => (bool) $user->two_fa_enabled,
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        Password::sendResetLink($request->only('email'));

        // Always return 200 to not reveal whether the email exists
        return response()->json([
            'message' => 'Si cette adresse email est associée à un compte, vous recevrez un lien de réinitialisation.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Votre mot de passe a été réinitialisé avec succès.',
            ]);
        }

        return response()->json([
            'message' => 'Impossible de réinitialiser le mot de passe. Le lien est invalide ou a expiré.',
        ], 422);
    }
}
