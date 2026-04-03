<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function image(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
        ]);

        $path = $request->file('image')->store('ads', 'public');

        return response()->json([
            'status' => 'success',
            'data' => [
                'url' => url('/storage/' . $path),
            ],
        ]);
    }
}
