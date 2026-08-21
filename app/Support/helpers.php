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
     *
     * ponytail: pt-BR digit grouping for every currency — the app ships one
     * locale. Swap for NumberFormatter if a second locale is ever needed.
     */
    function money(mixed $amount, ?string $code = null): string
    {
        return currency_symbol($code).' '.number_format((float) $amount, 2, ',', '.');
    }
}
