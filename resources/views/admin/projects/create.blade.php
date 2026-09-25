<x-admin-layout>
<div class="p-6 md:p-8 max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('admin.projects.index') }}" style="font-size:0.875rem;color:var(--muted-foreground)">{{ __('← Obras e Projetos') }}</a>
        <h1 style="font-size:1.5rem;font-weight:700;margin-top:0.5rem;">{{ __('Nova obra ou projeto') }}</h1>
    </div>
    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.5rem;">
        {{--
            OBRA-01: o campo da origem depende do cliente escolhido, e os dois
            vivem no mesmo formulário. O mapa cliente → projectos viaja com a
            página e o Alpine mostra só o ramo do cliente escolhido: sem pedido
            extra, sem rota nova, e o formulário continua a ser um formulário
            normal. Mudar de cliente limpa a origem, para nunca sobrar a escolha
            do cliente anterior — e o servidor recusa-a de qualquer forma.
        --}}
        <form method="POST" action="{{ route('admin.projects.store') }}" enctype="multipart/form-data" class="space-y-4"
              x-data="{
                  client: @js(old('client_id', '')),
                  source: '',
                  sources: @js($sourceProjects),
              }"
              x-init="$nextTick(() => source = @js(old('source_project_id', '')))">
            @csrf

            <x-form-errors />
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label">{{ __('Cliente') }} <span style="color:var(--destructive)">*</span></label>
                    <select name="client_id" required x-model="client" @change="source = ''">
                        <option value="">{{ __('Selecione…') }}</option>
                        @foreach($clients as $c)<option value="{{ $c->id }}" {{ old('client_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
                    </select>
                    <x-field-error field="client_id" />
                </div>
                <div>
                    <label class="field-label">{{ __('Código interno') }}</label>
                    <input type="text" name="code" value="{{ old('code') }}">
                </div>
            </div>
            <div x-show="(sources[client] ?? []).length" x-cloak>
                <label class="field-label">{{ __('Projeto de origem') }}</label>
                <select name="source_project_id" x-model="source">
                    <option value="">{{ __('Nenhum') }}</option>
                    <template x-for="source_option in (sources[client] ?? [])" :key="source_option.id">
                        <option :value="source_option.id" x-text="source_option.label"></option>
                    </template>
                </select>
                <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.25rem;">
                    {{ __('Este cliente já tem projetos com a gente. Vincule ao que lhe deu origem: as plantas aprovadas desse projeto passam a aparecer aqui, sem cópias — se forem corrigidas lá, aparece logo aqui a versão certa.') }}
                </p>
                <x-field-error field="source_project_id" />
            </div>
            <div>
                <label class="field-label">{{ __('Nome') }} <span style="color:var(--destructive)">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required>
                <x-field-error field="name" />
            </div>
            <div>
                <label class="field-label">{{ __('Localização') }}</label>
                <input type="text" name="location" value="{{ old('location') }}">
            </div>
            <div>
                <label class="field-label">{{ __('Descrição') }}</label>
                <textarea name="description" rows="3" style="resize:vertical;">{{ old('description') }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="field-label">{{ __('Data de início') }}</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}">
                </div>
                <div>
                    <label class="field-label">{{ __('Previsão de término') }}</label>
                    <input type="date" name="expected_end_date" value="{{ old('expected_end_date') }}">
                </div>
                <div>
                    <label class="field-label">{{ __('Orçamento total (') }}{{ currency_symbol() }})</label>
                    <input type="number" step="0.01" min="0" name="budget" value="{{ old('budget', 0) }}">
                </div>
                <div>
                    <label class="field-label">{{ __('Revisões incluídas no contrato') }}</label>
                    <input type="number" min="0" max="999" name="revisions_included" value="{{ old('revisions_included') }}" placeholder="{{ __('Sem limite') }}">
                    <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.25rem;">{{ __('Deixe vazio se o contrato não fixa limite. Vazio não é o mesmo que zero.') }}</p>
                    <x-field-error field="revisions_included" :form="$formulario ?? null" />
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label">{{ __('Tipo') }} <span style="color:var(--destructive)">*</span></label>
                    <select name="type" required>
                        @foreach(\App\Models\Project::TYPES as $k => $v)
                        <option value="{{ $k }}" {{ old('type','construcao')===$k?'selected':'' }}>{{ __($v) }}</option>
                        @endforeach
                    </select>
                    <x-field-error field="type" />
                </div>
                <div>
                    <label class="field-label">{{ __('Status') }}</label>
                    <select name="status">
                        @foreach(\App\Models\Project::STATUSES as $k => $v)
                        <option value="{{ $k }}" {{ old('status','planejada')===$k?'selected':'' }}>{{ __($v) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">{{ __('Gestor responsável') }}</label>
                    <select name="manager_id">
                        <option value="">{{ __('Nenhum') }}</option>
                        @foreach($managers as $m)<option value="{{ $m->id }}" {{ old('manager_id')==$m->id?'selected':'' }}>{{ $m->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="show_financials" value="1" id="sf" {{ old_checked('show_financials', true)?'checked':'' }} style="width:auto;">
                <label for="sf" style="font-size:0.875rem;color:var(--foreground)">{{ __('Exibir financeiro para o cliente') }}</label>
            </div>
            <div>
                <label class="field-label">{{ __('Imagem de capa') }}</label>
                <input type="file" name="cover_image" accept="image/*" style="padding:0.375rem;">
            </div>
            <div>
                <label class="field-label">{{ __('Notas internas') }}</label>
                <textarea name="notes" rows="2" style="resize:vertical;">{{ old('notes') }}</textarea>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn-primary">{{ __('Criar') }}</button>
                <a href="{{ route('admin.projects.index') }}" class="btn-secondary">{{ __('Cancelar') }}</a>
            </div>
        </form>
    </div>
</div>
</x-admin-layout>
