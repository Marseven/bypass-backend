<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a TOTP secret and return the otpauth:// URL for QR code.
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->two_fa_enabled) {
            return response()->json([
                'message' => 'La 2FA est déjà activée.',
            ], 422);
        }

        $secret = $this->google2fa->generateSecretKey();

        $user->update([
            'two_fa_secret' => Crypt::encryptString($secret),
        ]);

        $otpauthUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'ByPass'),
            $user->email,
            $secret
        );

        return response()->json([
            'secret' => $secret,
            'otpauth_url' => $otpauthUrl,
        ]);
    }

    /**
     * Verify the OTP code and enable 2FA + generate backup codes.
     */
    public function enable(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if ($user->two_fa_enabled) {
            return response()->json([
                'message' => 'La 2FA est déjà activée.',
            ], 422);
        }

        if (!$user->two_fa_secret) {
            return response()->json([
                'message' => 'Veuillez d\'abord configurer la 2FA via /auth/2fa/setup.',
            ], 422);
        }

        $secret = Crypt::decryptString($user->two_fa_secret);

        if (!$this->google2fa->verifyKey($secret, $request->code)) {
            return response()->json([
                'message' => 'Code OTP invalide.',
            ], 422);
        }

        // Generate 8 backup codes
        $backupCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $backupCodes[] = Str::random(10);
        }

        // Store hashed backup codes
        $hashedCodes = array_map(fn (string $code) => Hash::make($code), $backupCodes);

        $user->update([
            'two_fa_enabled' => true,
            'two_fa_verified_at' => now(),
            'two_fa_backup_codes' => Crypt::encryptString(json_encode($hashedCodes)),
        ]);

        return response()->json([
            'message' => '2FA activée avec succès.',
            'backup_codes' => $backupCodes,
        ]);
    }

    /**
     * Disable 2FA after password verification.
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!$user->two_fa_enabled) {
            return response()->json([
                'message' => 'La 2FA n\'est pas activée.',
            ], 422);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Mot de passe incorrect.',
            ], 422);
        }

        $user->update([
            'two_fa_enabled' => false,
            'two_fa_secret' => null,
            'two_fa_backup_codes' => null,
            'two_fa_verified_at' => null,
        ]);

        return response()->json([
            'message' => '2FA désactivée avec succès.',
        ]);
    }

    /**
     * Verify OTP or backup code during login (temp token with 2fa-verify ability).
     */
    public function verifyLogin(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();
        $token = $user->currentAccessToken();

        // Ensure this is a temp 2FA token
        if (!$token->can('2fa-verify') || $token->can('*')) {
            return response()->json([
                'message' => 'Token invalide pour la vérification 2FA.',
            ], 403);
        }

        $code = $request->code;
        $verified = false;

        // Try OTP verification first (6-digit codes)
        if (strlen($code) === 6 && ctype_digit($code)) {
            $secret = Crypt::decryptString($user->two_fa_secret);
            $verified = $this->google2fa->verifyKey($secret, $code);
        }

        // If not verified via OTP, try backup codes
        if (!$verified && $user->two_fa_backup_codes) {
            $hashedCodes = json_decode(Crypt::decryptString($user->two_fa_backup_codes), true);

            foreach ($hashedCodes as $index => $hashedCode) {
                if (Hash::check($code, $hashedCode)) {
                    // Remove used backup code
                    unset($hashedCodes[$index]);
                    $user->update([
                        'two_fa_backup_codes' => Crypt::encryptString(json_encode(array_values($hashedCodes))),
                    ]);
                    $verified = true;
                    break;
                }
            }
        }

        if (!$verified) {
            return response()->json([
                'message' => 'Code de vérification invalide.',
            ], 422);
        }

        // Delete temp token and create full-access token
        $token->delete();
        $newToken = $user->createToken('auth-token', ['*'])->plainTextToken;

        return response()->json([
            'status' => 200,
            'data' => [
                'user' => $user,
                'token' => $newToken,
            ],
            'message' => ['Vérification 2FA réussie.'],
        ]);
    }
}
