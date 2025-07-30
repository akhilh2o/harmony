<?php

namespace App\Http\Controllers;

use App\Http\Resources\SessionAudioResource;
use App\Http\Resources\SessionCategoryResource;
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
}
