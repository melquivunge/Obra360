<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = ['pt_BR', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?? $request->session()->get('locale') ?? $request->cookie('locale');
        app()->setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : 'pt_BR');

        return $next($request);
    }
}
