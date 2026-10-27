<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu QR — {{ $student->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 portrait; margin: 8mm; }
        body { font-family: sans-serif; }
        .wrap { width: 50mm; margin: 0 auto; }
        .card {
            height: 50mm;
            border: 0.4mm solid #000;
            padding: 2mm;
            text-align: center;
            overflow: hidden;
        }
        .qr img { width: 30mm; height: 30mm; }
        .name { font-size: 7.5pt; font-weight: bold; color: #000; line-height: 1.2; margin-top: 1mm; height: 6mm; overflow: hidden; word-wrap: break-word; }
        .major { font-size: 6.5pt; color: #333; line-height: 1.2; margin-top: 0.5mm; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="qr">
                <img src="{{ $qrUri }}" alt="QR" width="113" height="113">
            </div>
            <p class="name">{{ $student->name }}</p>
            <p class="major">{{ $student->classroom ? trim(($student->classroom->grade.' '.$student->classroom->major) ?: $student->classroom->name) : '—' }}</p>
        </div>
    </div>
</body>
</html>
