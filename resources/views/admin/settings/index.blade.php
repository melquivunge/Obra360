<x-admin-layout>
<div class="max-w-2xl space-y-6">
    <h1 style="font-size:1.5rem;font-weight:700;">{{ __('Configurações') }}</h1>

    @if(session('success'))
    <div style="background-color:oklch(0.6 0.18 145 / 0.12);border:1px solid oklch(0.6 0.18 145 / 0.4);color:oklch(0.7 0.15 145);border-radius:0.5rem;padding:0.75rem 1rem;font-size:0.875rem;">
        {{ session('success') }}
    </div>
    @endif

    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.5rem;">
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PUT')

            <x-form-errors />

            <fieldset>
                <legend style="font-size:0.875rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.08em;padding-bottom:0.75rem;border-bottom:1px solid var(--border);width:100%;margin-bottom:1rem;">{{ __('Empresa') }}</legend>
                <div class="space-y-4">
                    <div>
                        <label class="field-label">{{ __('Nome da empresa') }} <span style="color:var(--destructive)">*</span></label>
                        <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name']) }}" required>
                        <x-field-error field="company_name" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="field-label">{{ __('E-mail de contato') }}</label>
                            <input type="email" name="company_email" value="{{ old('company_email', $settings['company_email']) }}">
                            <x-field-error field="company_email" />
                        </div>
                        <div>
                            <label class="field-label">{{ __('Telefone') }}</label>
                            <input type="text" name="company_phone" value="{{ old('company_phone', $settings['company_phone']) }}">
                            <x-field-error field="company_phone" />
                        </div>
                    </div>
                    <div>
                        <label class="field-label">{{ __('Logotipo') }}</label>
                        @if($settings['logo_url'])
                        <div style="margin-bottom:0.5rem;">
                            <img src="{{ asset('storage/'.$settings['logo_url']) }}" alt="{{ __('Logo') }}" style="height:48px;object-fit:contain;border-radius:0.375rem;">
                        </div>
                        @endif
                        <input type="file" name="logo" accept="image/*" style="padding:0.375rem;">
                        <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.25rem;">{{ __('PNG ou SVG recomendado, máx. 4 MB') }}</p>
                        <x-field-error field="logo" />
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend style="font-size:0.875rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.08em;padding-bottom:0.75rem;border-bottom:1px solid var(--border);width:100%;margin-bottom:1rem;">{{ __('Portal do cliente') }}</legend>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-lg" style="background-color:var(--surface);border:1px solid var(--border);">
                        <div>
                            <p style="font-size:0.875rem;font-weight:500;">{{ __('Exibir financeiro ao cliente') }}</p>
                            <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.125rem;">{{ __('Mostra orçamento e despesas no portal do cliente (por padrão). Pode ser sobrescrito em cada obra ou projeto.') }}</p>
                        </div>
                        <label style="display:inline-flex;align-items:center;cursor:pointer;gap:0.5rem;flex-shrink:0;margin-left:1rem;">
                            <input type="hidden" name="show_client_financials" value="0">
                            <input type="checkbox" name="show_client_financials" value="1" {{ old_checked('show_client_financials', $settings['show_client_financials'] === '1') ? 'checked' : '' }} style="width:auto;accent-color:var(--primary);">
                            <span style="font-size:0.75rem;color:var(--muted-foreground);">{{ __('Ativado') }}</span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg" style="background-color:var(--surface);border:1px solid var(--border);">
                        <div>
                            <p style="font-size:0.875rem;font-weight:500;">{{ __('Permitir mensagens do cliente') }}</p>
                            <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.125rem;">{{ __('Clientes podem enviar mensagens pelo portal. Desativar oculta a aba de mensagens.') }}</p>
                        </div>
                        <label style="display:inline-flex;align-items:center;cursor:pointer;gap:0.5rem;flex-shrink:0;margin-left:1rem;">
                            <input type="hidden" name="allow_comments" value="0">
                            <input type="checkbox" name="allow_comments" value="1" {{ old_checked('allow_comments', $settings['allow_comments'] === '1') ? 'checked' : '' }} style="width:auto;accent-color:var(--primary);">
                            <span style="font-size:0.75rem;color:var(--muted-foreground);">{{ __('Ativado') }}</span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg" style="background-color:var(--surface);border:1px solid var(--border);">
                        <div>
                            <p style="font-size:0.875rem;font-weight:500;">{{ __('Permitir envio de arquivos pelo cliente') }}</p>
                            <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.125rem;">{{ __('O cliente pode enviar fotos e documentos pelo portal. O que ele envia fica marcado com o nome dele.') }}</p>
                        </div>
                        <label style="display:inline-flex;align-items:center;cursor:pointer;gap:0.5rem;flex-shrink:0;margin-left:1rem;">
                            <input type="hidden" name="allow_client_uploads" value="0">
                            <input type="checkbox" name="allow_client_uploads" value="1" {{ old_checked('allow_client_uploads', $settings['allow_client_uploads'] === '1') ? 'checked' : '' }} style="width:auto;accent-color:var(--primary);">
                            <span style="font-size:0.75rem;color:var(--muted-foreground);">{{ __('Ativado') }}</span>
                        </label>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend style="font-size:0.875rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.08em;padding-bottom:0.75rem;border-bottom:1px solid var(--border);width:100%;margin-bottom:1rem;">{{ __('Formato') }}</legend>
                <div>
                    <label class="field-label">{{ __('Moeda') }}</label>
                    <select name="currency" style="max-width:12rem;">
                        @foreach(['BRL'=>'R$ — Real Brasileiro','USD'=>'$ — Dólar Americano','EUR'=>'€ — Euro','GBP'=>'£ — Libra Esterlina','AOA'=>'Kz — Kwanza Angolano','MZN'=>'MT — Metical Moçambicano'] as $code=>$label)
                        <option value="{{ $code }}" {{ old('currency', $settings['currency'] ?? 'BRL')===$code?'selected':'' }}>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-top:1rem;">
                    <label class="field-label">{{ __('Progresso da obra ou projeto') }}</label>
                    <select name="progress_mode" style="max-width:20rem;">
                        <option value="derivado" {{ old('progress_mode', $settings['progress_mode'] ?? 'derivado') === 'derivado' ? 'selected' : '' }}>{{ __('Derivado das fases (média)') }}</option>
                        <option value="manual" {{ old('progress_mode', $settings['progress_mode'] ?? 'derivado') === 'manual' ? 'selected' : '' }}>{{ __('Manual (definido em cada obra ou projeto)') }}</option>
                    </select>
                    <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.375rem;">{{ __('No modo derivado a porcentagem é a média das fases e não pode ser editada na obra ou no projeto.') }}</p>
                </div>
            </fieldset>

            <fieldset>
                <legend style="font-size:0.875rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.08em;padding-bottom:0.75rem;border-bottom:1px solid var(--border);width:100%;margin-bottom:1rem;">{{ __('Notificações') }}</legend>
                <p style="font-size:0.75rem;color:var(--muted-foreground);margin-bottom:0.75rem;">
                    {{ __('Escolha que acontecimentos notificam o cliente. Desativar um tipo silencia-o sem afetar o histórico da obra ou do projeto.') }}
                </p>
                <div class="space-y-2">
                    @foreach($settings['notifications'] as $type => $notification)
                    <div class="flex items-center justify-between p-3 rounded-lg" style="background-color:var(--surface);border:1px solid var(--border);">
                        <p style="font-size:0.875rem;font-weight:500;">{{ __($notification['label']) }}</p>
                        <label style="display:inline-flex;align-items:center;cursor:pointer;gap:0.5rem;flex-shrink:0;margin-left:1rem;">
                            <input type="hidden" name="notify_{{ $type }}" value="0">
                            <input type="checkbox" name="notify_{{ $type }}" value="1" {{ old_checked('notify_'.$type, (bool) $notification['enabled']) ? 'checked' : '' }} style="width:auto;accent-color:var(--primary);">
                            <span style="font-size:0.75rem;color:var(--muted-foreground);">{{ __('Ativado') }}</span>
                        </label>
                    </div>
                    @endforeach
                </div>
            </fieldset>

            <div class="pt-2">
                <button type="submit" class="btn-primary">{{ __('Salvar configurações') }}</button>
            </div>
        </form>
    </div>
</div>
</x-admin-layout>
