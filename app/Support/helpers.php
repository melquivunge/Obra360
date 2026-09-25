<?php

use App\Models\Setting;

if (! function_exists('currency_symbol')) {
    /**
     * Symbol for the currency configured in system settings.
     */
    function currency_symbol(?string $code = null): string
    {
        $code ??= Setting::get('currency', 'BRL');

        return [
            'BRL' => 'R$',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AOA' => 'Kz',
            'MZN' => 'MT',
        ][$code] ?? $code;
    }
}

if (! function_exists('money')) {
    /**
     * Format a value using the currency configured in system settings.
     */
    function money(mixed $amount, ?string $code = null): string
    {
        return currency_symbol($code).' '.number_format((float) $amount, 2, app()->getLocale() === 'en' ? '.' : ',', app()->getLocale() === 'en' ? ',' : '.');
    }
}

if (! function_exists('old_checked')) {
    /**
     * Whether a checkbox should come back checked after a failed submission.
     *
     * `old('x', $default)` não serve para caixas: uma caixa desmarcada não é
     * submetida, e o valor por omissão voltava a marcá-la — a pessoa desmarcava,
     * o formulário falhava noutro campo e a escolha dela desaparecia. Enquanto
     * houver input antigo manda o input antigo; sem ele manda o valor guardado.
     */
    function old_checked(string $key, bool $default = false): bool
    {
        if (! session()->hasOldInput()) {
            return $default;
        }

        return (bool) old($key);
    }
}
