<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — {{ $project->name }}</title>
    <style>
        @page { margin: 22mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1c1c1c; }
        .rep-head { border-bottom: 2px solid #c9a227; padding-bottom: 8px; margin-bottom: 14px; }
        .rep-head .company { font-size: 9px; color: #777; text-transform: uppercase; letter-spacing: 0.06em; }
        .rep-head h1 { font-size: 16px; margin: 4px 0 2px; }
        .rep-head .sub { font-size: 10px; color: #555; }
        .rep-h3 { font-size: 11px; margin: 16px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #ddd; }
        .rep-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .rep-table th, .rep-table td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; vertical-align: top; }
        .rep-table thead th, .rep-table tfoot th { background: #f4f4f0; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; }
        .rep-table tbody th { background: #fafaf7; width: 30%; }
        .rep-table .num { text-align: right; }
        .rep-empty { color: #888; font-style: italic; margin: 4px 0 10px; }
        .rep-gallery { width: 100%; border-collapse: separate; border-spacing: 4px; }
        .rep-gallery td { width: 33%; vertical-align: top; }
        .rep-gallery img { width: 100%; height: 90px; object-fit: cover; border: 1px solid #ddd; }
        .rep-gallery .cap { font-size: 8px; color: #777; padding-top: 2px; }
        .rep-foot { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 8px; color: #999; }
    </style>
</head>
<body>
    <div class="rep-head">
        <div class="company">{{ $companyName }}</div>
        <h1>{{ $title }}</h1>
        <div class="sub">{{ $project->name }}@if($project->code) · {{ $project->code }}@endif · Emitido em {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </div>

    @include('admin.reports.partials.'.$type)

    <div class="rep-foot">{{ $companyName }} · {{ $project->name }} · {{ $generatedAt->format('d/m/Y H:i') }}</div>
</body>
</html>
