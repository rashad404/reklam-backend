<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class BannerGeneratorController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:60',
            'description' => 'nullable|string|max:120',
            'color' => 'nullable|string|max:7',
            'size' => 'nullable|string|in:728x90,300x250,320x50',
            'domain' => 'nullable|string|max:50',
            'template' => 'nullable|string|max:20',
        ]);

        $args = json_encode([
            'title' => $request->input('title'),
            'description' => $request->input('description', ''),
            'color' => $request->input('color', '#FF3131'),
            'size' => $request->input('size', '300x250'),
            'domain' => $request->input('domain', ''),
            'template' => $request->input('template', 'default'),
        ]);

        $scriptPath = base_path('scripts/generate-banner.cjs');
        $nodePath = trim(shell_exec('which node') ?? '/opt/homebrew/bin/node');
        $result = Process::timeout(30)->run("{$nodePath} {$scriptPath} " . escapeshellarg($args));

        if (!$result->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Banner generation failed',
                'debug' => $result->errorOutput(),
            ], 500);
        }

        $output = json_decode(trim($result->output()), true);

        if (!$output || !isset($output['filename'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid generator output',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'url' => url('/storage/' . $output['filename']),
                'size' => $request->input('size', '300x250'),
            ],
        ]);
    }
}
