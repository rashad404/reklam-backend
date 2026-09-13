<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function image(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,webp|max:5120|dimensions:max_width=4096,max_height=4096',
            'size' => 'required|in:300x250,728x90,320x50',
        ]);

        $dimensions = getimagesize($request->file('image')->getRealPath());
        [$width, $height] = array_map('intval', explode('x', $request->size));
        abort_unless($dimensions[0] === $width && $dimensions[1] === $height, 422, 'Image dimensions must match the selected placement.');

        $path = $request->file('image')->store('ads', 'public');

        return response()->json([
            'status' => 'success',
            'data' => [
                'url' => url('/storage/'.$path),
            ],
        ]);
    }
}
