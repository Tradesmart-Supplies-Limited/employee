<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CompanyLogoResolver
{
    /*
    |--------------------------------------------------------------------------
    | Returns the company logo as a base64 data URI, suitable for embedding
    | directly in a DomPDF-rendered Blade view with NO remote HTTP request
    | happening during PDF generation itself.
    |
    | The remote fetch happens here, ONCE, and is cached for 24 hours — so
    | even if the logo server is slow or briefly down, payslip generation
    | (including bulk email sends via the queue) keeps working off the
    | cached version instead of stalling on a live network call.
    |
    | If the fetch fails (network issue, 404, etc.), returns null so the
    | Blade template's existing fallback (initials badge) takes over
    | instead of breaking the whole PDF.
    |
    | Usage:
    |   CompanyLogoResolver::resolve();                 // uses config('company.logo_url')
    |   CompanyLogoResolver::resolve('https://...');     // explicit override
    |--------------------------------------------------------------------------
    */
    public static function resolve(?string $logoUrl = null): ?string
    {
        $logoUrl = $logoUrl ?? config('company.logo_url');

        if (empty($logoUrl)) {
            return null;
        }

        $cacheKey = 'company_logo_base64_' . md5($logoUrl);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($logoUrl) {
            try {
                $response = Http::timeout(5)->get($logoUrl);

                if (!$response->successful()) {
                    Log::warning("CompanyLogoResolver: failed to fetch logo, HTTP {$response->status()}", [
                        'url' => $logoUrl,
                    ]);
                    return null;
                }

                $mimeType = $response->header('Content-Type') ?: 'image/png';
                $base64   = base64_encode($response->body());

                return "data:{$mimeType};base64,{$base64}";

            } catch (\Throwable $e) {
                Log::warning("CompanyLogoResolver: exception fetching logo — {$e->getMessage()}", [
                    'url' => $logoUrl,
                ]);
                return null;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Clears the cached logo — call this after uploading a new logo so the
    | next PDF generation re-fetches instead of serving the stale cached one.
    |--------------------------------------------------------------------------
    */
    public static function forget(?string $logoUrl = null): void
    {
        $logoUrl = $logoUrl ?? config('company.logo_url');
        Cache::forget('company_logo_base64_' . md5($logoUrl));
    }
}