<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use App\Models\Campaign;

class CampaignQueueController extends Controller
{
    public static function isPaused($campaignId)
    {
        return Cache::get("campaign_{$campaignId}_paused", false);
    }

    public static function pause($campaignId)
    {
        Cache::put("campaign_{$campaignId}_paused", true);
    }

    public static function resume($campaignId)
    {
        Cache::forget("campaign_{$campaignId}_paused");
    }
}
