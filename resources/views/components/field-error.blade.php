@props(['field', 'form' => null])

{{--
    Erro de um campo, junto ao campo. O `form` segue a mesma regra do
    `<x-form-errors>`: sem ele, dois formulários com um campo `name` na mesma
    página acendiam ambos com o erro de um só.

    Nota: o `<x-input-error>` que já existia serve as páginas de autenticação,
    que são as do Breeze e usam classes utilitárias. A área interna desenha-se
    com estilos inline e variáveis CSS, por isso são componentes separados.
--}}
@if ($errors->has($field) && old('_form') === $form)
    <p style="color:var(--destructive);font-size:0.75rem;margin-top:0.25rem;" {{ $attributes }}>{{ $errors->first($field) }}</p>
@endif
