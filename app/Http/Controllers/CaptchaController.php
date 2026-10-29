<?php

namespace App\Http\Controllers;

use App\Support\Captcha;
use Illuminate\Http\JsonResponse;

class CaptchaController extends Controller
{
    public function math(): JsonResponse
    {
        return response()->json(Captcha::challenge());
    }
}
