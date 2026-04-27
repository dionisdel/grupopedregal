<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminImageController extends Controller
{
    /**
     * Upload an image and return its public URL path.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|image|max:5120',
        ]);

        $path = $request->file('image')->store('images', 'public');

        return response()->json([
            'url' => '/storage/' . $path,
        ], 201);
    }
}
