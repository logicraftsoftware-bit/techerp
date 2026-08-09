<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #252525; }
        .card { position: relative; width: 54mm; height: 86mm; overflow: hidden; background: #fff; text-align: center; }
        .brand-bar { height: 7mm; background: #ed1c24; }
        .logo-wrap { margin: -4mm auto 0; width: 36mm; height: 12mm; padding: 1.2mm; border-radius: 2mm; background: #fff; }
        .logo { width: 100%; height: 100%; object-fit: contain; }
        .photo, .initials { display: block; margin: 2mm auto 1.5mm; width: 24mm; height: 24mm; border: 1mm solid #ed1c24; border-radius: 50%; object-fit: cover; }
        .initials { line-height: 24mm; background: #fce8e9; color: #ed1c24; font-size: 9mm; font-weight: bold; }
        .name { margin: 0 3mm; height: 9.5mm; overflow: hidden; font-size: 4mm; line-height: 4.7mm; font-weight: bold; color: #252525; }
        .designation { margin-top: .5mm; color: #ed1c24; font-size: 2.4mm; line-height: 3mm; font-weight: bold; text-transform: uppercase; }
        .department { margin-top: .4mm; color: #666; font-size: 2.2mm; line-height: 2.8mm; }
        .bottom { position: absolute; left: 3mm; right: 3mm; bottom: 0; height: 20mm; padding-top: 4mm; border-radius: 11mm 11mm 0 0; background: #ed1c24; color: #fff; }
        .employee-id { font-size: 3.2mm; font-weight: bold; }
        .validity { margin-top: 1.4mm; font-size: 2.4mm; }
        .mobile { margin-top: 1.4mm; padding-top: 1.4mm; border-top: .2mm solid rgba(255,255,255,.45); font-size: 2.4mm; }
    </style>
</head>
<body>
<div class="card">
    <div class="brand-bar"></div>
    <div class="logo-wrap"><img class="logo" src="{{ $logo }}" alt="Company logo"></div>
    @if($photo)
        <img class="photo" src="{{ $photo }}" alt="Technician photo">
    @else
        <div class="initials">{{ str($technician->name)->substr(0, 1)->upper() }}</div>
    @endif
    <div class="name">{{ $technician->name }}</div>
    <div class="designation">{{ $technician->designation ?: 'Technician' }}</div>
    <div class="department">{{ $technician->departmentMaster?->department_name ?? $technician->getAttribute('department') }}</div>
    <div class="bottom">
        <div class="employee-id">ID: {{ $technician->employee_code }}</div>
        <div class="validity">Valid Thru: {{ $technician->joining_date?->copy()->addYears(2)->format('F Y') }}</div>
        <div class="mobile">Mobile: {{ $technician->mobile }}</div>
    </div>
</div>
</body>
</html>
