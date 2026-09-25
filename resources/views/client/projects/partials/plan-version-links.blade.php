{{--
    As acções de uma versão de planta no portal (PORT-02/PORT-03).

    - Uma versão com endereço externo liga directamente ao endereço: a rota de
      download não a serve, para a aplicação não se tornar um redireccionador
      aberto.
    - «Ver» só aparece nos formatos que o browser mostra mesmo
      (PlanVersion::PREVIEWABLE_FORMATS). Os pesados — dwg, skp, rvt, ifc — e o
      GLB só se descarregam: oferecer «ver» seria prometer o que não há.
--}}
@if($version->isExternal())
    <a href="{{ $version->external_url }}" target="_blank" rel="noopener noreferrer"
       style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border:1px solid var(--color-border);border-radius:0.375rem;font-size:0.8125rem;color:var(--color-primary);text-decoration:none;">
        {{ __('Abrir endereço ↗') }}
    </a>
@else
    @if(in_array($version->format, \App\Models\PlanVersion::PREVIEWABLE_FORMATS, true))
    <a href="{{ route('portal.projects.plans.versions.preview', [$project, $plan, $version]) }}" target="_blank" rel="noopener"
       style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border:1px solid var(--color-border);border-radius:0.375rem;font-size:0.8125rem;color:var(--color-primary);text-decoration:none;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
        </svg>
        {{ __('Ver') }}
    </a>
    @endif
    <a href="{{ route('portal.projects.plans.versions.download', [$project, $plan, $version]) }}"
       style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border:1px solid var(--color-border);border-radius:0.375rem;font-size:0.8125rem;color:var(--color-primary);text-decoration:none;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        {{ __('Baixar') }}
    </a>
@endif
