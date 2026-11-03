<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function public(): JsonResponse
    {
        $settings = \App\Models\Setting::where('is_public', true)->pluck('value', 'key');
        return $this->success($settings);
    }

    /*private function success(mixed $d, string $m = 'Success', int $c = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $m, 'data' => $d], $c);
    }*/
}
