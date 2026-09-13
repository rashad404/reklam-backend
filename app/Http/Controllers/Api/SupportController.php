<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['data' => DB::table('support_requests')->where('user_id', $request->user()->id)->latest()->paginate(10)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['request_key' => 'required|uuid', 'subject' => 'required|string|min:3|max:120', 'message' => 'required|string|min:10|max:3000']);
        $ticket = DB::transaction(function () use ($request, $data) {
            DB::table('users')->where('id', $request->user()->id)->lockForUpdate()->first();
            $query = DB::table('support_requests')->where('user_id', $request->user()->id)->where('request_key', $data['request_key']);
            if (! $query->exists()) {
                DB::table('support_requests')->insert($data + ['user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            }

            return $query->first();
        });

        return response()->json(['data' => $ticket], 201);
    }

    public function queue(Request $request)
    {
        $data = $request->validate(['status' => 'nullable|in:open,answered,closed']);

        return response()->json(['data' => DB::table('support_requests')->join('users', 'users.id', '=', 'support_requests.user_id')->select('support_requests.*', 'users.email')->where('support_requests.status', $data['status'] ?? 'open')->orderBy('support_requests.created_at')->paginate(20)]);
    }

    public function reply(Request $request, int $ticket)
    {
        $data = $request->validate(['reply' => 'required|string|min:3|max:5000', 'status' => 'required|in:answered,closed']);
        abort_unless(DB::table('support_requests')->where('id', $ticket)->exists(), 404);
        DB::table('support_requests')->where('id', $ticket)->update($data + ['replied_by' => $request->user()->id, 'replied_at' => now(), 'updated_at' => now()]);

        return response()->json(['data' => DB::table('support_requests')->where('id', $ticket)->first()]);
    }
}
