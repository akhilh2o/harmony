<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlaylistResource;
use App\Http\Resources\SessionAudioResource;
use App\Http\Resources\SessionCategoryResource;
use App\Models\Playlist;
use App\Models\PlaylistAudio;
use App\Models\SessionAudio;
use App\Models\SessionCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    // ─── SESSION CATEGORIES ──────────────────────────────────

    public function sessionCategories(): JsonResponse
    {
        $categories = SessionCategory::where('is_active', 1)->get();
        return $this->sendResponse(SessionCategoryResource::collection($categories), 'Session Categories retrieved successfully.');
    }

    // ─── SESSION AUDIOS ───────────────────────────────────────

    /**
     * GET /session-audios
     * Filters: category_id, is_free, search, sort_by, sort_dir, limit, page
     */
    public function sessionAudios(Request $request): JsonResponse
    {
        $query = SessionAudio::with('session_category')->where('is_active', 1);

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('session_category_id', $request->category_id);
        }

        // Filter free / paid / premium
        if ($request->has('is_free')) {
            $query->where('is_free', filter_var($request->is_free, FILTER_VALIDATE_BOOLEAN));
        } elseif ($request->has('is_premium')) {
            $isPremium = filter_var($request->is_premium, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_free', !$isPremium);
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sort & Limit
        if ($request->boolean('top_played')) {
            $query->orderBy('play_count', 'desc');
            $limit = min((int) ($request->limit ?? 10), 50);
        } elseif ($request->boolean('latest')) {
            $query->orderBy('created_at', 'desc');
            $limit = min((int) ($request->limit ?? 10), 50);
        } elseif ($request->boolean('is_top')) {
            $query->orderBy('created_at', 'desc');
            $limit = min((int) ($request->limit ?? 10), 50);
        } else {
            $allowedSorts = ['name', 'duration', 'created_at', 'play_count'];
            $sortBy  = in_array($request->sort_by, $allowedSorts) ? $request->sort_by : 'created_at';
            $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortBy, $sortDir);
            $limit = min((int) ($request->limit ?? 20), 100);
        }

        $audios = $query->paginate($limit);

        return $this->sendResponse([
            'data'         => SessionAudioResource::collection($audios->items()),
            'total'        => $audios->total(),
            'per_page'     => $audios->perPage(),
            'current_page' => $audios->currentPage(),
            'last_page'    => $audios->lastPage(),
        ], 'Session Audios retrieved successfully.');
    }

    public function sessionAudioByIdOrSlug(string $param): JsonResponse
    {
        $query = SessionAudio::with('session_category')->where('is_active', 1);

        // Agar pure number hai toh ID se dhundo, warna slug se
        if (ctype_digit($param)) {
            $audio = $query->findOrFail($param);
        } else {
            $audio = $query->where('slug', $param)->firstOrFail();
        }

        return $this->sendResponse(SessionAudioResource::make($audio), 'Session Audio retrieved successfully.');
    }

    // public function sessionAudioById($id): JsonResponse
    // {
    //     $audio = SessionAudio::with('session_category')->where('is_active', 1)->findOrFail($id);
    //     return $this->sendResponse(SessionAudioResource::make($audio), 'Session Audio retrieved successfully.');
    // }
    // public function sessionAudioBySlug(string $slug): JsonResponse
    // {
    //     $audio = SessionAudio::with('session_category')->where('is_active', 1)->where('slug', $slug)->firstOrFail();

    //     return $this->sendResponse(SessionAudioResource::make($audio), 'Session Audio retrieved successfully.');
    // }

    public function sessionAudioByCategoryId($id): JsonResponse
    {
        $audios = SessionAudio::with('session_category')
            ->where('is_active', 1)
            ->where('session_category_id', $id)
            ->get();
        return $this->sendResponse(SessionAudioResource::collection($audios), 'Session Audios retrieved successfully.');
    }

    /**
     * GET /session-audios/free  — only free sessions (no auth needed)
     */
    public function freeSessionAudios(): JsonResponse
    {
        $audios = SessionAudio::with('session_category')
            ->where('is_active', 1)
            ->where('is_free', true)
            ->get();
        return $this->sendResponse(SessionAudioResource::collection($audios), 'Free Session Audios retrieved successfully.');
    }

    /**
     * GET /session-audios/premium  — requires subscription
     */
    public function premiumSessionAudios(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasActiveSubscription()) {
            return $this->sendError('Subscription required to access premium content.', [], 403);
        }

        $audios = SessionAudio::with('session_category')
            ->where('is_active', 1)
            ->where('is_free', false)
            ->get();
        return $this->sendResponse(SessionAudioResource::collection($audios), 'Premium Session Audios retrieved successfully.');
    }

    // ─── PLAYLISTS ────────────────────────────────────────────

    public function createPlaylist(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_free'     => 'nullable|boolean',
            'color'       => 'nullable|string|max:10',
            'session_ids' => 'nullable|array',
            'session_ids.*' => 'exists:session_audio,id',
        ]);

        $playlist = Playlist::create([
            'name'        => $request->name,
            'description' => $request->description,
            'user_id'     => $request->user()->id,
            'is_active'   => true,
            'is_public'   => $request->boolean('is_public', false),
            'is_free'     => $request->boolean('is_free', true),
            'color'       => $request->color,
        ]);

        // Attach sessions if provided
        if ($request->filled('session_ids')) {
            foreach ($request->session_ids as $index => $sessionId) {
                PlaylistAudio::create([
                    'playlist_id'     => $playlist->id,
                    'session_audio_id'=> $sessionId,
                    'order'           => $index,
                    'is_active'       => true,
                    'is_public'       => false,
                ]);
            }
        }

        $playlist->load('audios.sessionAudio');
        return $this->sendResponse(new PlaylistResource($playlist), 'Playlist created successfully.');
    }

    public function getPlaylists(Request $request): JsonResponse
    {
        $query = Playlist::withCount('audios')  // sirf count, poora load nahi
            ->where('user_id', $request->user()->id);

        if ($request->has('is_free')) {
            $query->where('is_free', filter_var($request->is_free, FILTER_VALIDATE_BOOLEAN));
        }

        $playlists = $query->latest()->get();
        return $this->sendResponse(PlaylistResource::collection($playlists), 'Playlists retrieved successfully.');
    }


    public function getPlaylistById($id): JsonResponse
    {
        $playlist = Playlist::with(['audios.sessionAudio.session_category'])->findOrFail($id);
        return $this->sendResponse(new PlaylistResource($playlist), 'Playlist retrieved successfully.');
    }

    public function updatePlaylist(Request $request, $id): JsonResponse
    {
        $playlist = Playlist::where('user_id', $request->user()->id)->findOrFail($id);

        $request->validate([
            'name'          => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'is_active'     => 'nullable|boolean',
            'is_public'     => 'nullable|boolean',
            'is_free'       => 'nullable|boolean',
            'color'         => 'nullable|string|max:10',
            'session_ids'   => 'nullable|array',
            'session_ids.*' => 'exists:session_audio,id',
        ]);

        $playlist->update($request->only(['name', 'description', 'is_active', 'is_public', 'is_free', 'color']));

        // Replace all sessions if provided
        if ($request->has('session_ids')) {
            $playlist->audios()->delete();
            foreach ($request->session_ids as $index => $sessionId) {
                PlaylistAudio::create([
                    'playlist_id'      => $playlist->id,
                    'session_audio_id' => $sessionId,
                    'order'            => $index,
                    'is_active'        => true,
                    'is_public'        => false,
                ]);
            }
        }

        $playlist->load('audios.sessionAudio');
        return $this->sendResponse(new PlaylistResource($playlist), 'Playlist updated successfully.');
    }

    public function deletePlaylist(Request $request, $id): JsonResponse
    {
        $playlist = Playlist::where('user_id', $request->user()->id)->findOrFail($id);
        $playlist->audios()->delete();
        $playlist->delete();
        return $this->sendResponse([], 'Playlist deleted successfully.');
    }

    public function addAudioToPlaylist(Request $request, $playlistId): JsonResponse
    {
        $request->validate([
            'session_audio_id' => 'required|exists:session_audio,id',
            'order'            => 'nullable|integer',
        ]);

        $playlist = Playlist::where('user_id', $request->user()->id)->findOrFail($playlistId);

        if ($playlist->audios()->where('session_audio_id', $request->session_audio_id)->exists()) {
            return $this->sendError('Audio already exists in this playlist.', [], 422);
        }

        $maxOrder = $playlist->audios()->max('order') ?? -1;
        PlaylistAudio::create([
            'playlist_id'      => $playlist->id,
            'session_audio_id' => $request->session_audio_id,
            'order'            => $request->order ?? ($maxOrder + 1),
            'is_active'        => true,
            'is_public'        => false,
        ]);

        $playlist->load('audios.sessionAudio');
        return $this->sendResponse(new PlaylistResource($playlist), 'Audio added to playlist successfully.');
    }

    public function removeAudioFromPlaylist(Request $request, $playlistId, $audioId): JsonResponse
    {
        $playlist = Playlist::where('user_id', $request->user()->id)->findOrFail($playlistId);
        $playlist->audios()->where('session_audio_id', $audioId)->firstOrFail()->delete();
        return $this->sendResponse([], 'Audio removed from playlist successfully.');
    }

    public function reorderPlaylistAudios(Request $request, $playlistId): JsonResponse
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'exists:session_audio,id',
        ]);

        $playlist = Playlist::where('user_id', $request->user()->id)->findOrFail($playlistId);

        foreach ($request->order as $index => $sessionAudioId) {
            $playlist->audios()
                ->where('session_audio_id', $sessionAudioId)
                ->update(['order' => $index]);
        }

        $playlist->load('audios.sessionAudio');
        return $this->sendResponse(new PlaylistResource($playlist), 'Playlist reordered successfully.');
    }
}
