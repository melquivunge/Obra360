{{--
    Mensagem efémera de sucesso ou de erro.

    O desaparecimento ao fim de quatro segundos vivia num bloco <script> aqui
    dentro; passou para resources/js/app.js, que reage ao marcador `data-flash`.
    Um bloco por vista era mais um sítio a precisar de nonce por causa da CSP —
    e o comportamento é o mesmo em todas as páginas, logo pertence ao ficheiro
    comum.
--}}
@if (session('success') || session('error'))
    <div data-flash class="mb-4">
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif
    </div>
@endif
