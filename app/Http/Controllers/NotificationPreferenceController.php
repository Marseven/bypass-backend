<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function index(Request $request)
    {
        $preferences = NotificationPreference::where('user_id', $request->user()->id)->get();

        return response()->json($preferences);
    }

    public function update(Request $request)
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.channel' => 'required|string|in:email,whatsapp,push,in_app',
            'preferences.*.event_type' => 'required|string|in:request_created,validation_result,expiration_warning,reminder',
            'preferences.*.enabled' => 'required|boolean',
        ]);

        $userId = $request->user()->id;

        foreach ($request->preferences as $pref) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $userId,
                    'channel' => $pref['channel'],
                    'event_type' => $pref['event_type'],
                ],
                [
                    'enabled' => $pref['enabled'],
                ]
            );
        }

        $preferences = NotificationPreference::where('user_id', $userId)->get();

        return response()->json([
            'message' => 'Préférences mises à jour',
            'data' => $preferences,
        ]);
    }
}
