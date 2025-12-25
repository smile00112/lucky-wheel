<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Spin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestSpinNotificationController extends Controller
{
    public function sendNotification(Request $request, int $spinId): JsonResponse
    {
        $spin = Spin::with(['guest', 'prize', 'wheel'])->findOrFail($spinId);

        if (!$spin->isWin()) {
            return response()->json([
                'success' => false,
                'message' => 'Spin is not a win',
                'spin_id' => $spin->id,
            ], 400);
        }

        $guest = $spin->guest;
        if (!$guest) {
            return response()->json([
                'success' => false,
                'message' => 'Guest not found for this spin',
                'spin_id' => $spin->id,
            ], 404);
        }

        if (!$guest->email) {
            return response()->json([
                'success' => false,
                'message' => 'Guest has no email address',
                'spin_id' => $spin->id,
                'guest_id' => $guest->id,
            ], 400);
        }

        try {
            $sent = $spin->sendWinEmail();

            if ($sent) {
                return response()->json([
                    'success' => true,
                    'message' => 'Win notification sent successfully',
                    'spin_id' => $spin->id,
                    'guest_id' => $guest->id,
                    'guest_email' => $guest->email,
                    'prize_name' => $spin->prize?->name,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Notification already sent or failed to send',
                'spin_id' => $spin->id,
                'email_notification' => $spin->email_notification,
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage(),
                'spin_id' => $spin->id,
            ], 500);
        }
    }
}



