{{--
    ADV-04: o orçamento por linhas, e o plano confrontado com o gasto.

    As linhas não substituem o total da obra — comparam-se com o que já se
    gastou, pelas mesmas categorias das despesas. É daí que sai a pergunta que
    interessa: «orçámos tanto em material e já vamos em quanto?».

    Aparecem as categorias que têm plano **ou** gasto: uma despesa numa
    categoria que ninguém orçou é precisamente o que se quer ver.
--}}
@props(['project', 'canEdit' => false])

@php
    $linhas = $project->budgetVersusSpent();
    $planeado = $project->budgetPlanned();
    $diferenca = $project->budgetPlanDiff();
@endphp

<div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.25rem;">
    <div style="display:flex;align-items:baseline;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:0.875rem;">
        <h3 style="font-size:0.9375rem;font-weight:700;">{{ __('Orçamento por categoria') }}</h3>
        @if($planeado > 0)
            <span style="font-size:0.8125rem;color:var(--muted-foreground);">
                {{ __('Planejado') }} {{ money($planeado) }}
                @if($project->budget && abs($diferenca) >= 0.01)
                    · {{ $diferenca > 0 ? __('acima') : __('abaixo') }} {{ __('do total contratado em') }} {{ money(abs($diferenca)) }}
                @endif
            </span>
        @endif
    </div>

    @if(empty($linhas))
        <p style="font-size:0.8125rem;color:var(--muted-foreground);">
            {{ __('Nenhuma linha de orçamento ainda. Detalhar por categoria permite comparar o planejado com o gasto.') }}
        </p>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
            <thead>
                <tr style="text-align:left;color:var(--muted-foreground);">
                    <th style="padding:0.375rem 0.5rem 0.375rem 0;">{{ __('Categoria') }}</th>
                    <th style="padding:0.375rem 0.5rem;text-align:right;">{{ __('Planejado') }}</th>
                    <th style="padding:0.375rem 0.5rem;text-align:right;">{{ __('Gasto') }}</th>
                    <th style="padding:0.375rem 0 0.375rem 0.5rem;text-align:right;">{{ __('Diferença') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($linhas as $linha)
                <tr style="border-top:1px solid var(--border);">
                    <td style="padding:0.5rem 0.5rem 0.5rem 0;">{{ __($linha['label']) }}</td>
                    <td style="padding:0.5rem;text-align:right;">{{ $linha['planned'] > 0 ? money($linha['planned']) : '—' }}</td>
                    <td style="padding:0.5rem;text-align:right;">{{ $linha['spent'] > 0 ? money($linha['spent']) : '—' }}</td>
                    <td style="padding:0.5rem 0 0.5rem 0.5rem;text-align:right;color:{{ $linha['diff'] > 0 ? 'var(--destructive)' : 'var(--muted-foreground)' }};">
                        {{ abs($linha['diff']) < 0.01 ? '—' : ($linha['diff'] > 0 ? '+' : '−').money(abs($linha['diff'])) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($canEdit)
    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);">
        @if($project->budgetItems->isNotEmpty())
        <div class="space-y-2" style="margin-bottom:0.875rem;">
            @foreach($project->budgetItems as $item)
            <div style="display:flex;align-items:center;gap:0.75rem;font-size:0.8125rem;">
                <span style="min-width:8rem;">{{ $item->categoryLabel() }}</span>
                <span style="flex:1;min-width:0;color:var(--muted-foreground);" class="truncate">{{ $item->description ?: '—' }}</span>
                <span style="font-weight:600;">{{ money($item->amount) }}</span>
                <form method="POST" action="{{ route('admin.projects.budget-items.destroy', [$project, $item]) }}"
                      data-confirm="{{ __('Remover esta linha do orçamento?') }}">
                    @csrf @method('DELETE')
                    <button type="submit" style="background:none;border:none;padding:0;cursor:pointer;color:var(--destructive);font-size:0.8125rem;">{{ __('Remover') }}</button>
                </form>
            </div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('admin.projects.budget-items.store', $project) }}" class="space-y-2">
            @csrf
            <input type="hidden" name="_form" value="orcamento_linha">
            <x-form-errors form="orcamento_linha" />
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                <select name="category" required style="max-width:12rem;">
                    @foreach(\App\Models\Expense::CATEGORIES as $k => $v)
                        <option value="{{ $k }}" {{ old('category') === $k ? 'selected' : '' }}>{{ __($v) }}</option>
                    @endforeach
                </select>
                <input type="text" name="description" maxlength="200" placeholder="{{ __('Descrição (opcional)') }}" value="{{ old('description') }}" style="flex:1;min-width:10rem;">
                <input type="number" step="0.01" min="0" name="amount" required placeholder="{{ __('Valor') }}" value="{{ old('amount') }}" style="max-width:9rem;">
                <button type="submit" class="btn-secondary" style="white-space:nowrap;">{{ __('Adicionar linha') }}</button>
            </div>
            <x-field-error field="amount" form="orcamento_linha" />
        </form>
    </div>
    @endif
</div>
