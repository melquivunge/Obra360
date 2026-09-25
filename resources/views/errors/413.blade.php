{{--
    LIM-02: o envio era maior do que o `post_max_size` do PHP.

    O Laravel trava-o em `ValidatePostSize`, antes de a sessão sequer arrancar —
    não há como devolver o erro ao formulário. O que resta é esta página, e o que
    ela não pode ser é o «413 Payload Too Large» genérico: quem acabou de enviar
    um Revit de 180 MB precisa de saber qual é o tecto e que há uma alternativa.

    Sem @vite de propósito: uma página de erro não pode depender do manifesto de
    assets, que é precisamente uma das coisas que podem estar em falta.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="copy-success" content="{{ __('Copiado!') }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Envio demasiado grande') }} · {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 1.5rem; background: #f6f6f5; color: #1c1c1a;
            font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            line-height: 1.6;
        }
        @media (prefers-color-scheme: dark) { body { background: #121211; color: #eceae5; } .card { background: #1b1b19; border-color: #2e2e2b; } }
        .card { max-width: 34rem; background: #fff; border: 1px solid #e4e3df; border-radius: 0.875rem; padding: 2rem; }
        h1 { font-size: 1.375rem; margin: 0 0 0.75rem; }
        p { margin: 0 0 0.875rem; font-size: 0.9375rem; }
        ul { margin: 0 0 1rem; padding-left: 1.25rem; font-size: 0.9375rem; }
        .muted { opacity: 0.7; font-size: 0.8125rem; }
        a { color: #a8760f; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('O envio era demasiado grande') }}</h1>

        <p>
            {{ __('O arquivo que você tentou enviar excede o limite deste servidor
            (') }}<strong>{{ \App\Models\PlanVersion::maxUploadLabel() }}</strong> {{ __('por pedido) e
            foi recusado antes de chegar à aplicação. Nada foi salvo e nada se perdeu do
            que já estava registrado.') }}
        </p>

        <p>{{ __('Há duas saídas:') }}</p>
        <ul>
            <li>{{ __('Enviar um arquivo menor — um PDF exportado em vez do arquivo nativo, por exemplo.') }}</li>
            <li>{{ __('Cadastrar a versão com um') }} <strong>{{ __('endereço externo') }}</strong> {{ __('(Google Drive, WeTransfer, OneDrive) em vez do arquivo. A planta, a numeração das versões e o histórico ficam registrados do mesmo jeito; só o arquivo é que vive fora daqui.') }}</li>
        </ul>

        <p><a href="{{ url()->previous() }}">{{ __('← Voltar') }}</a></p>

        <p class="muted">
            {{ __('O limite é uma configuração da hospedagem (') }}<code>{{ __('upload_max_filesize') }}</code> {{ __('e') }}
            <code>{{ __('post_max_size') }}</code>{{ __('), não desta aplicação.') }}
        </p>
    </div>
</body>
</html>
