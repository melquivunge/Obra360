{{--
    Imagem de capa da obra — único sítio da aplicação que decide qual é o URL da
    imagem mostrada. Recebe em `src` o URL da capa própria da obra (ou null,
    quando a obra não tem capa nenhuma) e cai na imagem padrão sempre que falte.
    Acrescentar um novo ponto de renderização ou trocar o ficheiro mexe só aqui.

    O `data-fallback-src` cobre os registos cujo ficheiro não está no disco — a
    rota devolve 404 e a imagem padrão entra no lugar, em vez de se retirar o
    elemento. Quem reage ao erro é resources/js/app.js (num ouvinte em fase de
    captura, porque o evento `error` de uma imagem não sobe na árvore); antes de
    trocar o `src` retira o atributo, por isso se a própria imagem padrão também
    falhar não há ciclo infinito. Era um `onerror=` em linha até a Content
    Security Policy passar a proibir script em atributo.

    O tamanho, a posição e o resto do desenho vêm de quem usa o componente
    (`style`, `class`, `alt`, `loading`, ...) — a capa padrão ocupa exactamente
    o mesmo espaço que a capa real ocuparia.
--}}
@props(['src' => null])

@php($fallback = asset('images/capa-padrao.jpg'))

<img src="{{ $src ?: $fallback }}" {{ $attributes }}
     data-fallback-src="{{ $fallback }}">
