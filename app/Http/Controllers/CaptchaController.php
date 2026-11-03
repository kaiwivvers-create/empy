<?php

namespace App\Http\Controllers;

use App\Support\Captcha;
use Illuminate\Http\JsonResponse;

class CaptchaController extends Controller
{
    public function math(): JsonResponse
    {
        return response()->json(Captcha::challenge('math'));
    }

    public function physics(): JsonResponse
    {
        return response()->json(Captcha::challenge('physics'));
    }
}
