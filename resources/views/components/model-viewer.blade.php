{{--
    Visualizador 3D de uma versão de planta em GLB/GLTF.

    Só estes dois formatos: são os únicos que um navegador desenha sem
    conversão. Um Revit ou um SketchUp teria de ser convertido no servidor, o
    que num alojamento partilhado não é viável — esses continuam a descarregar-se
    e a abrir no programa de origem.

    O componente `<model-viewer>` pesa cerca de 1 MB e é carregado à parte, só
    quando existe um modelo na página (ver resources/js/app.js). Enquanto não
    chega, o que se vê é a mensagem de fundo — não uma caixa vazia.
--}}
@props(['src', 'label' => 'Modelo 3D'])

<div style="margin-top:0.75rem;width:100%;">
    <model-viewer
        src="{{ $src }}"
        alt="{{ $label }}"
        camera-controls
        touch-action="pan-y"
        shadow-intensity="1"
        style="width:100%;height:22rem;background-color:var(--surface, #1a1a1a);border:1px solid var(--border, #333);border-radius:0.5rem;">
        <p style="padding:1rem;font-size:0.8125rem;color:var(--muted-foreground);">
            A carregar o modelo 3D… Se não aparecer, o seu navegador pode não suportar WebGL — o ficheiro continua disponível para baixar.
        </p>
    </model-viewer>
    <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.375rem;">
        Arraste para girar, use a roda do mouse para aproximar.
    </p>
</div>
