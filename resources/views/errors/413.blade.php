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
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Envio demasiado grande · {{ config('app.name') }}</title>
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
        <h1>O envio era demasiado grande</h1>

        <p>
            O ficheiro que tentou enviar excede o limite deste servidor
            (<strong>{{ \App\Models\PlanVersion::maxUploadLabel() }}</strong> por pedido) e
            foi recusado antes de chegar à aplicação. Nada foi guardado e nada se perdeu do
            que já estava registado.
        </p>

        <p>Há duas saídas:</p>
        <ul>
            <li>Enviar um ficheiro mais pequeno — um PDF exportado em vez do ficheiro nativo, por exemplo.</li>
            <li>Registar a versão com um <strong>endereço externo</strong> (Google Drive, WeTransfer, OneDrive) em vez do ficheiro. A planta, a numeração das versões e o histórico ficam registados na mesma; só o ficheiro é que vive fora daqui.</li>
        </ul>

        <p><a href="{{ url()->previous() }}">← Voltar</a></p>

        <p class="muted">
            O limite é uma configuração do alojamento (<code>upload_max_filesize</code> e
            <code>post_max_size</code>), não desta aplicação.
        </p>
    </div>
</body>
</html>
