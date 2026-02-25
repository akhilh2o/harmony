<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlaylistResource;
use App\Http\Resources\SessionAudioResource;
use App\Http\Resources\SessionCategoryResource;
use App\Models\Playlist;
use App\Models\SessionAudio;
use App\Models\SessionCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{

    public function sessionCategories(): JsonResponse
    {
        $sessionCategories = SessionCategory::where('is_active', 1)->get();

        return $this->sendResponse(SessionCategoryResource::collection($sessionCategories), 'Session Categories retrieved successfully.');
    }

    public function sessionAudios(): JsonResponse
    {
        $sessionAudios = SessionAudio::with('session_category')->where('is_active', 1)->get();

        return $this->sendResponse(SessionAudioResource::collection($sessionAudios), 'Session Audios retrieved successfully.');
    }
    
    public function sessionAudioById($id)
    {
        $sessionAudio = SessionAudio::with('session_category')->where('is_active', 1)->where('id', $id)->first();

        return $this->sendResponse(SessionAudioResource::make($sessionAudio), 'Session Audio retrieved successfully.');
    }
    
    public function sessionAudioByCategoryId($id)
    {
        $sessionAudios = SessionAudio::with('session_category')->where('is_active', 1)->where('session_category_id', $id)->get();

        return $this->sendResponse(SessionAudioResource::collection($sessionAudios), 'Session Audios retrieved successfully.');
    }

    public function createPlaylist(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $playlist = Playlist::create($request->only(['name', 'description']) + ['user_id' => $request->user()->id, 'is_active' => true, 'is_public' => false]);
        
        return $this->sendResponse(new PlaylistResource($playlist), 'Playlist created successfully.');
    }

    public function getPlaylists(Request $request)
    {
        $playlists = Playlist::with(['user', 'audios.sessionAudio'])->where('user_id', $request->user()->id)->get();

        return $this->sendResponse(PlaylistResource::collection($playlists), 'Playlists retrieved successfully.');
    }

    public function getPlaylistById($id)
    {
        $playlist = Playlist::with(['user', 'audios.sessionAudio'])->findOrFail($id);

        return $this->sendResponse(new PlaylistResource($playlist), 'Playlist retrieved successfully.');
    }

    public function updatePlaylist(Request $request, $id)
    {
        $playlist = Playlist::findOrFail($id);
        $playlist->update($request->only(['name', 'description', 'is_active', 'is_public']));

        return $this->sendResponse(new PlaylistResource($playlist), 'Playlist updated successfully.');
    }

    public function deletePlaylist($id)
    {
        $playlist = Playlist::findOrFail($id);
        $playlist->delete();

        return $this->sendResponse([], 'Playlist deleted successfully.');
    }

    public function addAudioToPlaylist(Request $request, $playlistId)
    {
        $request->validate([
            'session_audio_id' => 'required|exists:session_audio,id',
            'order' => 'nullable|integer',
        ]);
        $playlist = Playlist::findOrFail($playlistId);

        // Check if the audio is already in the playlist
        if ($playlist->audios()->where('session_audio_id', $request->session_audio_id)->exists()) {
            return $this->sendError('Audio already exists in this playlist.');
        }

        $playlistAudio = $playlist->audios()->create([
            'session_audio_id' => $request->session_audio_id,
            'order' => $request->order ?? 0,
            'is_active' => true,
            'is_public' => false,
        ]);

        // Optionally, you can load the session audio details
        $playlist->load('audios');
        
        // Return the updated playlist with the new audio
        $playlist->load('audios.sessionAudio');

        return $this->sendResponse(new PlaylistResource($playlist), 'Audio added to playlist successfully.');
    }

    public function removeAudioFromPlaylist($playlistId, $audioId)
    {
        $playlist = Playlist::findOrFail($playlistId);
        $playlistAudio = $playlist->audios()->where('session_audio_id', $audioId)->firstOrFail();
        $playlistAudio->delete();

        return $this->sendResponse([], 'Audio removed from playlist successfully.');
    }
}
