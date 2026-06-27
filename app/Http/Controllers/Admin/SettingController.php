<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'company_name' => Setting::get('company_name', 'Obra360'),
            'company_email' => Setting::get('company_email', ''),
            'company_phone' => Setting::get('company_phone', ''),
            'allow_comments' => Setting::get('allow_comments', '1'),
            'show_client_financials' => Setting::get('show_client_financials', '1'),
            'currency' => Setting::get('currency', 'BRL'),
            'logo_url' => Setting::get('logo_url'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:200',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:40',
            'currency' => 'nullable|string|max:10',
            'logo' => 'nullable|image|max:4096',
        ]);

        Setting::set('company_name', $request->company_name);
        Setting::set('company_email', $request->company_email ?? '');
        Setting::set('company_phone', $request->company_phone ?? '');
        Setting::set('allow_comments', $request->boolean('allow_comments') ? '1' : '0');
        Setting::set('show_client_financials', $request->boolean('show_client_financials') ? '1' : '0');
        Setting::set('currency', $request->currency ?? 'BRL');

        if ($request->hasFile('logo')) {
            $old = Setting::get('logo_url');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('logo')->store('branding', 'public');
            Setting::set('logo_url', $path);
        }

        return back()->with('success', 'Configurações salvas.');
    }
}
