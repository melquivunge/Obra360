<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in(SetLocale::SUPPORTED)],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $locale = $data['locale'];
        $request->session()->put('locale', $locale);
        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->save();
        }
        app()->setLocale($locale);

        // Only relative paths are accepted, including after URL decoding.
        $target = $data['return_to'] ?? '/';
        $decoded = rawurldecode($target);
        if (! str_starts_with($decoded, '/') || str_starts_with($decoded, '//') || preg_match('/[\\\\\x00-\x20]/', $decoded)) {
            $target = '/';
        }

        return redirect($target)->withCookie(cookie('locale', $locale, 525600, '/', null, $request->isSecure(), true, false, 'lax'));
    }
}
