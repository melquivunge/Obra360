<form method="POST" action="{{ route('locale.update') }}" class="flex items-center gap-1 shrink-0">
    @csrf
    <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
    <label class="sr-only" for="language-choice">{{ __('Idioma') }}</label>
    <select id="language-choice" name="locale" aria-label="{{ __('Idioma') }}" style="width:auto;max-width:7rem;font-size:0.75rem;padding:0.375rem;">
        <option value="pt_BR" @selected(app()->getLocale() === 'pt_BR') lang="pt-BR">Português</option>
        <option value="en" @selected(app()->getLocale() === 'en') lang="en">English</option>
    </select>
    <button type="submit" class="btn-secondary" style="padding:0.375rem;font-size:0.75rem;">{{ __('Aplicar') }}</button>
</form>
