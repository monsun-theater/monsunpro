<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DynamicToken extends Controller
{
    /**
     * Get refreshed CSRF token.
     */
    public function getRefresh(Request $request): JsonResponse
    {
        // Determine if the request is actually coming from our own website on non local enviroments.
        if (config('app.env') != 'local') {
            $referer = request()->headers->get('referer');
            $contains = str_contains($referer, request()->getHttpHost());
            if (empty($referer) || ! $contains) {
                abort(404);
            }
        }

        return response()->json([
            'csrf_token' => csrf_token(),
        ]);
    }
}
