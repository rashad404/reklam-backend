<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $advertiser = $request->user()->advertiser;
        if (! $advertiser) {
            return response()->json(['data' => ['data' => [], 'last_page' => 1, 'total' => 0]]);
        }
        $query = $advertiser->campaigns()->with('ads')->withCount(['ads', 'impressions', 'clicks']);
        $request->validate(['status' => 'nullable|in:draft,active,paused,completed', 'search' => 'nullable|string|max:100']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return response()->json(['data' => $query->latest()->paginate(20)]);
    }

    public function show(Request $request, Campaign $campaign)
    {
        $this->authorizeOwner($request, $campaign);

        return response()->json(['data' => $campaign->load('ads')->loadCount(['impressions', 'clicks'])]);
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, Campaign $campaign)
    {
        $this->authorizeOwner($request, $campaign);

        return $this->save($request, $campaign);
    }

    private function authorizeOwner(Request $request, Campaign $campaign): void
    {
        abort_unless($request->user()->advertiser?->id === $campaign->advertiser_id, 403);
    }

    private function save(Request $request, ?Campaign $campaign = null)
    {
        $data = $request->validate([
            'request_key' => [Rule::requiredIf(! $campaign), 'nullable', 'uuid'],
            'name' => 'required|string|max:120', 'type' => 'required|in:display,text',
            'budget' => 'required|numeric|min:1|max:1000000',
            'daily_budget' => 'nullable|prohibited',
            'cpc_bid' => 'nullable|numeric|min:0.01|max:1000',
            'cpm_bid' => 'nullable|numeric|min:0.01|max:1000',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'targeting_json' => 'prohibited',
            'ads' => 'required|array|min:1|max:4',
            'ads.*.title' => 'nullable|string|max:120', 'ads.*.description' => 'nullable|string|max:240',
            'ads.*.image_url' => 'nullable|url:http,https|max:2048',
            'ads.*.destination_url' => 'required|url:http,https|max:2048',
            'ads.*.ad_format' => 'required|distinct|in:banner_300x250,banner_728x90,banner_320x50,text',
        ]);
        if ((bool) ($data['cpc_bid'] ?? null) === (bool) ($data['cpm_bid'] ?? null)) {
            throw ValidationException::withMessages(['cpc_bid' => 'Choose exactly one pricing model.']);
        }
        foreach ($data['ads'] as $ad) {
            if ($ad['ad_format'] === 'text' ? empty(trim($ad['title'] ?? '')) : empty($ad['image_url'])) {
                throw ValidationException::withMessages(['ads' => 'Text ads need a title; banners need an image.']);
            }
        }
        $created = ! $campaign;
        $saved = DB::transaction(function () use ($request, $data, $campaign) {
            // Serialize retries and profile creation for this account.
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $advertiser = $user->advertiser()->firstOrCreate([], ['company_name' => $user->name]);
            if (! $campaign) {
                $existing = $advertiser->campaigns()->where('request_key', $data['request_key'])->first();
                if ($existing) {
                    return $existing;
                }
            } else {
                $campaign = $advertiser->campaigns()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();
            }
            $values = collect($data)->except(['ads', 'request_key'])->all();
            if ($campaign) {
                $campaign->update($values);
            } else {
                $campaign = $advertiser->campaigns()->create($values + ['request_key' => $data['request_key']]);
            }
            $formats = [];
            foreach ($data['ads'] as $creative) {
                $formats[] = $creative['ad_format'];
                $ad = $campaign->ads()->firstOrNew(['ad_format' => $creative['ad_format']]);
                $creative['title'] = $creative['title'] ?? '';
                $ad->fill($creative);
                if ($ad->isDirty()) {
                    $ad->status = 'pending';
                    $ad->review_reason = null;
                }
                $ad->save();
            }
            $campaign->ads()->whereNotIn('ad_format', $formats)->delete();

            return $campaign;
        });

        return response()->json(['data' => $saved->load('ads')], $created ? 201 : 200);
    }

    public function updateStatus(Request $request, Campaign $campaign)
    {
        $this->authorizeOwner($request, $campaign);
        $data = $request->validate(['status' => 'required|in:active,paused,completed']);
        if ($data['status'] === 'active') {
            abort_unless($campaign->ads()->where('status', 'approved')->exists(), 422, 'An approved ad is required.');
            abort_unless($campaign->advertiser->status === 'active', 403);
        }
        $campaign->update($data);

        return response()->json(['data' => $campaign->fresh()->load('ads')]);
    }

    public function destroy(Request $request, Campaign $campaign)
    {
        $this->authorizeOwner($request, $campaign);
        $campaign->update(['status' => 'paused']);
        $campaign->delete();

        return response()->json(['status' => 'success']);
    }

    public function stats(Request $request, Campaign $campaign)
    {
        $this->authorizeOwner($request, $campaign);

        return response()->json(['data' => $campaign->dailyStats()->orderByDesc('date')->limit(30)->get()]);
    }
}
