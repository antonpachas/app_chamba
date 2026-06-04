<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class AvatarController extends Controller
{
    public function __construct(private readonly MediaStorageService $media) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|file|max:5120',
        ]);

        $user = $request->user();

        try {
            $oldPath = $user->avatar_path;
            $newPath = $this->media->storeImage(
                $request->file('avatar'),
                MediaStorageService::FOLDER_AVATAR,
                ['max_w' => 800, 'max_h' => 800]
            );
            $user->avatar_path = $newPath;
            $user->save();

            if ($oldPath && $oldPath !== $newPath) {
                $this->media->delete($oldPath);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'avatar_path' => $user->avatar_path,
                'avatar_url' => $this->media->publicUrl($user->avatar_path),
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->avatar_path) {
            $this->media->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        return response()->json(['data' => ['avatar_path' => null, 'avatar_url' => null]]);
    }
}
