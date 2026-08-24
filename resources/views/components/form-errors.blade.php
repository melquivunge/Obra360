@props(['form' => null, 'title' => 'Não foi possível salvar. Corrija o seguinte:'])

{{--
    Resumo dos erros de validação de UM formulário.

    A página pode ter vários formulários e o Laravel devolve todos os erros no
    mesmo saco. Quem separa é o marcador `_form`: cada formulário leva um
    `<input type="hidden" name="_form" value="…">` e o `back()->withInput()` da
    validação devolve-o em `old('_form')`. Comparar o marcador com o do
    formulário que está a ser desenhado chega para o resumo aparecer só onde
    pertence — e um formulário sem marcador (`form` a null) só mostra erros de
    submissões que também não trouxeram marcador.
--}}
@if ($errors->any() && old('_form') === $form)
    <div role="alert" aria-live="assertive"
         style="background-color:color-mix(in oklab, var(--destructive) 12%, var(--card));border:1px solid color-mix(in oklab, var(--destructive) 35%, var(--border));border-radius:0.75rem;padding:0.875rem 1rem;margin-bottom:1rem;"
         {{ $attributes }}>
        <p style="font-size:0.875rem;font-weight:600;color:var(--destructive);margin:0 0 0.375rem;">{{ $title }}</p>
        <ul style="margin:0;padding-left:1.1rem;font-size:0.875rem;color:var(--destructive);">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
