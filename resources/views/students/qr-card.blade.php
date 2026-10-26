<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu QR — {{ $student->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; display: flex; justify-content: center; padding: 24px; background: #EEEEEE; }
        .card { border: 2px solid #041562; border-top: 8px solid #041562; border-radius: 12px; padding: 16px 24px 20px; text-align: center; width: 320px; background: #fff; }
        .card .school { font-size: 11px; font-weight: bold; letter-spacing: 0.12em; color: #11468F; margin-bottom: 6px; }
        .card h1 { font-size: 20px; margin-bottom: 4px; color: #041562; }
        .card .kelas { font-size: 14px; color: #11468F; margin-bottom: 2px; }
        .card .nipd { font-size: 13px; color: #666; margin-bottom: 12px; }
        .card svg { width: 220px; height: 220px; }
        .actions { margin-top: 16px; }
        .actions button { background: #041562; color: #fff; border: none; border-radius: 8px; padding: 10px 24px; font-size: 14px; cursor: pointer; }
        @media print {
            body { padding: 0; background: #fff; }
            .actions { display: none; }
            .card { border-width: 2px; page-break-inside: avoid; }
        }
    </style>
<body>
    <div class="card">
        <p class="school">SMKN 2 BANJARMASIN</p>
        <h1>{{ $student->name }}</h1>
        <p class="kelas">{{ $student->classroom?->name ?? '—' }}</p>
        <p class="nipd">NIPD: {{ $student->nipd }}</p>
        {!! QrCode::size(220)->generate($student->uuid) !!}
        <div class="actions">
            <button onclick="window.print()">Cetak</button>
        </div>
    </div>
</body>
</html>
