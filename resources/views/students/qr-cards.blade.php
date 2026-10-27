<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu QR Murid</title>
    <link rel="icon" href="{{ asset('images/logo-smkn2.png') }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; padding: 16px; background: #EEEEEE; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .card { border: 2px solid #041562; border-top: 6px solid #041562; border-radius: 12px; padding: 12px 16px 16px; text-align: center; page-break-inside: avoid; background: #fff; }
        .card .school { font-size: 10px; font-weight: bold; letter-spacing: 0.12em; color: #11468F; margin-bottom: 4px; }
        .card h1 { font-size: 18px; margin-bottom: 4px; color: #041562; }
        .card .kelas { font-size: 13px; color: #11468F; margin-bottom: 2px; }
        .card .nipd { font-size: 12px; color: #666; margin-bottom: 10px; }
        .card svg { width: 180px; height: 180px; }
        .actions { margin-bottom: 16px; }
        .actions button { background: #041562; color: #fff; border: none; border-radius: 8px; padding: 10px 24px; font-size: 14px; cursor: pointer; }
        @media print {
            body { padding: 0; background: #fff; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Cetak Semua ({{ $students->count() }} kartu)</button>
    </div>
    <div class="grid">
        @foreach ($students as $student)
            <div class="card">
                <p class="school">SMKN 2 BANJARMASIN</p>
                <h1>{{ $student->name }}</h1>
                <p class="kelas">{{ $student->classroom?->name ?? '—' }}</p>
                <p class="nipd">NIPD: {{ $student->nipd }}</p>
                {!! QrCode::size(180)->generate($student->uuid) !!}
            </div>
        @endforeach
    </div>
</body>
</html>
