<?php
namespace App\Http\Controllers;

use App\Http\Resources\SessionAudioResource;
use App\Models\UserActivity;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserActivityController extends Controller
{
    // ─── ACTIVITY ─────────────────────────────────────────────

    // Audio play hone pe track karo
    public function trackPlay(Request $request): JsonResponse
    {
        $request->validate([
            'session_audio_id' => 'required|exists:session_audio,id',
            'listened_seconds' => 'nullable|integer|min:0',
            'action'           => 'nullable|in:played,completed,paused',
        ]);

        // Same audio same din dobara play kare toh update karo
        $activity = UserActivity::updateOrCreate(
            [
                'user_id'          => $request->user()->id,
                'session_audio_id' => $request->session_audio_id,
                'action'           => $request->action ?? 'played',
            ],
            [
                'listened_seconds' => $request->listened_seconds ?? 0,
            ]
        );

        $activity = UserActivity::create([
            'user_id'          => $request->user()->id,
            'session_audio_id' => $request->session_audio_id,
            'action'           => $request->action ?? 'played',
            'listened_seconds' => $request->listened_seconds ?? 0,
        ]);

        return $this->sendResponse([
            'activity_id' => $activity->id,
        ], 'Activity tracked.');
    }

    // User ki activity list
    public function getActivities(Request $request): JsonResponse
    {
        $activities = UserActivity::with('sessionAudio.session_category')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return $this->sendResponse([
            'data'         => $activities->map(function ($a) {
                $audio = $a->sessionAudio;
                return [
                    'activity_id'      => $a->id,
                    'action'           => $a->action,
                    'listened_seconds' => $a->listened_seconds,
                    'played_at'        => $a->created_at->toDateTimeString(),
                    'session_audio'    => $audio ? SessionAudioResource::make($audio) : null,
                ];
            }),
            'total'        => $activities->total(),
            'current_page' => $activities->currentPage(),
            'last_page'    => $activities->lastPage(),
        ], 'Activities retrieved.');
    }

    // Activity clear karo
    public function clearActivities(Request $request): JsonResponse
    {
        UserActivity::where('user_id', $request->user()->id)->delete();
        return $this->sendResponse([], 'Activity history cleared.');
    }

    // ─── WISHLIST ──────────────────────────────────────────────

    // Wishlist mein add karo
    public function addToWishlist(Request $request): JsonResponse
    {
        $request->validate([
            'session_audio_id' => 'required|exists:session_audio,id',
        ]);

        // Already hai toh error
        $exists = Wishlist::where('user_id', $request->user()->id)
            ->where('session_audio_id', $request->session_audio_id)
            ->exists();

        if ($exists) {
            return $this->sendError('Already in wishlist.', [], 422);
        }

        Wishlist::create([
            'user_id'          => $request->user()->id,
            'session_audio_id' => $request->session_audio_id,
        ]);

        return $this->sendResponse([], 'Added to wishlist.');
    }

    // Wishlist se remove karo
    public function removeFromWishlist(Request $request, $sessionAudioId): JsonResponse
    {
        $deleted = Wishlist::where('user_id', $request->user()->id)
            ->where('session_audio_id', $sessionAudioId)
            ->delete();

        if (!$deleted) {
            return $this->sendError('Not found in wishlist.', [], 404);
        }

        return $this->sendResponse([], 'Removed from wishlist.');
    }

    // Wishlist list
    public function getWishlist(Request $request): JsonResponse
    {
        $wishlist = Wishlist::with('sessionAudio.session_category')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return $this->sendResponse(
            $wishlist->map(function ($w) {
                $audio = $w->sessionAudio;
                return [
                    'wishlist_id'   => $w->id,
                    'added_at'      => $w->created_at->toDateTimeString(),
                    'session_audio' => $audio ? SessionAudioResource::make($audio) : null,
                ];
            }),
            'Wishlist retrieved.'
        );
    }

    // Toggle — ek hi call se add/remove
    public function toggleWishlist(Request $request): JsonResponse
    {
        $request->validate([
            'session_audio_id' => 'required|exists:session_audio,id',
        ]);

        $existing = Wishlist::where('user_id', $request->user()->id)
            ->where('session_audio_id', $request->session_audio_id)
            ->first();

        if ($existing) {
            $existing->delete();
            return $this->sendResponse(['is_wishlisted' => false], 'Removed from wishlist.');
        }

        Wishlist::create([
            'user_id'          => $request->user()->id,
            'session_audio_id' => $request->session_audio_id,
        ]);

        return $this->sendResponse(['is_wishlisted' => true], 'Added to wishlist.');
    }
}