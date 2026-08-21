<x-admin-layout>
<div class="max-w-2xl space-y-6">
    <h1 style="font-size:1.5rem;font-weight:700;">Configurações</h1>

    @if(session('success'))
    <div style="background-color:oklch(0.6 0.18 145 / 0.12);border:1px solid oklch(0.6 0.18 145 / 0.4);color:oklch(0.7 0.15 145);border-radius:0.5rem;padding:0.75rem 1rem;font-size:0.875rem;">
        {{ session('success') }}
    </div>
    @endif

    <div style="background-color:var(--card);border:1px solid var(--border);border-radius:0.75rem;padding:1.5rem;">
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PUT')

            <fieldset>
                <legend style="font-size:0.875rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.08em;padding-bottom:0.75rem;border-bottom:1px solid var(--border);width:100%;margin-bottom:1rem;">Empresa</legend>
                <div class="space-y-4">
                    <div>
                        <label class="field-label">Nome da empresa <span style="color:var(--destructive)">*</span></label>
                        <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name']) }}" required>
                        @error('company_name')<p style="color:var(--destructive);font-size:0.75rem;margin-top:0.25rem;">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="field-label">E-mail de contacto</label>
                            <input type="email" name="company_email" value="{{ old('company_email', $settings['company_email']) }}">
                        </div>
                        <div>
                            <label class="field-label">Telefone</label>
                            <input type="text" name="company_phone" value="{{ old('company_phone', $settings['company_phone']) }}">
                        </div>
                    </div>
                    <div>
                        <label class="field-label">Logótipo</label>
                        @if($settings['logo_url'])
                        <div style="margin-bottom:0.5rem;">
                            <img src="{{ asset('storage/'.$settings['logo_url']) }}" alt="Logo" style="height:48px;object-fit:contain;border-radius:0.375rem;">
                        </div>
                        @endif
                        <input type="file" name="logo" accept="image/*" style="padding:0.375rem;">
                        <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.25rem;">PNG ou SVG recomendado, máx. 4 MB</p>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend style="font-size:0.875rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.08em;padding-bottom:0.75rem;border-bottom:1px solid var(--border);width:100%;margin-bottom:1rem;">Portal do cliente</legend>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-lg" style="background-color:var(--surface);border:1px solid var(--border);">
                        <div>
                            <p style="font-size:0.875rem;font-weight:500;">Exibir financeiro ao cliente</p>
                            <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.125rem;">Mostra orçamento e despesas no portal do cliente (por padrão). Pode ser sobrescrito por obra.</p>
                        </div>
                        <label style="display:inline-flex;align-items:center;cursor:pointer;gap:0.5rem;flex-shrink:0;margin-left:1rem;">
                            <input type="hidden" name="show_client_financials" value="0">
                            <input type="checkbox" name="show_client_financials" value="1" {{ $settings['show_client_financials'] === '1' ? 'checked' : '' }} style="width:auto;accent-color:var(--primary);">
                            <span style="font-size:0.75rem;color:var(--muted-foreground);">Ativado</span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg" style="background-color:var(--surface);border:1px solid var(--border);">
                        <div>
                            <p style="font-size:0.875rem;font-weight:500;">Permitir mensagens do cliente</p>
                            <p style="font-size:0.75rem;color:var(--muted-foreground);margin-top:0.125rem;">Clientes podem enviar mensagens pelo portal. Desativar oculta a aba de mensagens.</p>
                        </div>
                        <label style="display:inline-flex;align-items:center;cursor:pointer;gap:0.5rem;flex-shrink:0;margin-left:1rem;">
                            <input type="hidden" name="allow_comments" value="0">
                            <input type="checkbox" name="allow_comments" value="1" {{ $settings['allow_comments'] === '1' ? 'checked' : '' }} style="width:auto;accent-color:var(--primary);">
                            <span style="font-size:0.75rem;color:var(--muted-foreground);">Ativado</span>
                        </label>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend style="font-size:0.875rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.08em;padding-bottom:0.75rem;border-bottom:1px solid var(--border);width:100%;margin-bottom:1rem;">Formato</legend>
                <div>
                    <label class="field-label">Moeda</label>
                    <select name="currency" style="max-width:12rem;">
                        @foreach(['BRL'=>'R$ — Real Brasileiro','USD'=>'$ — Dólar Americano','EUR'=>'€ — Euro','GBP'=>'£ — Libra Esterlina','AOA'=>'Kz — Kwanza Angolano','MZN'=>'MT — Metical Moçambicano'] as $code=>$label)
                        <option value="{{ $code }}" {{ ($settings['currency']??'BRL')===$code?'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </fieldset>

            <fieldset>
                <legend style="font-size:0.875rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.08em;padding-bottom:0.75rem;border-bottom:1px solid var(--border);width:100%;margin-bottom:1rem;">Notificações</legend>
                <p style="font-size:0.75rem;color:var(--muted-foreground);margin-bottom:0.75rem;">
                    Escolha que acontecimentos notificam o cliente. Desativar um tipo silencia-o sem afetar o histórico da obra.
                </p>
                <div class="space-y-2">
                    @foreach($settings['notifications'] as $type => $notification)
                    <div class="flex items-center justify-between p-3 rounded-lg" style="background-color:var(--surface);border:1px solid var(--border);">
                        <p style="font-size:0.875rem;font-weight:500;">{{ $notification['label'] }}</p>
                        <label style="display:inline-flex;align-items:center;cursor:pointer;gap:0.5rem;flex-shrink:0;margin-left:1rem;">
                            <input type="hidden" name="notify_{{ $type }}" value="0">
                            <input type="checkbox" name="notify_{{ $type }}" value="1" {{ $notification['enabled'] ? 'checked' : '' }} style="width:auto;accent-color:var(--primary);">
                            <span style="font-size:0.75rem;color:var(--muted-foreground);">Ativado</span>
                        </label>
                    </div>
                    @endforeach
                </div>
            </fieldset>

            <div class="pt-2">
                <button type="submit" class="btn-primary">Salvar configurações</button>
            </div>
        </form>
    </div>
</div>
</x-admin-layout>
