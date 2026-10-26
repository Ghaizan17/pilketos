<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu QR — {{ $scopeLabel }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 portrait; margin: 8mm; }
        body { font-family: sans-serif; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        table.grid { width: 100%; border-collapse: separate; border-spacing: 3mm; margin: -3mm 0 0 -3mm; }
        td.cell { width: 25%; vertical-align: top; padding: 0; }
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
    @foreach ($students->chunk(20) as $page)
        <div class="page">
            <table class="grid">
                @foreach ($page->chunk(4) as $row)
                    <tr>
                        @foreach ($row as $student)
                            <td class="cell">
                                <div class="card">
                                    <div class="qr">
                                        <img src="{{ $qrUris[$student->id] ?? '' }}" alt="QR" width="113" height="113">
                                    </div>
                                    <p class="name">{{ $student->name }}</p>
                                    <p class="major">{{ $student->classroom?->name ?? '—' }}</p>
                                </div>
                            </td>
                        @endforeach
                        @for ($i = $row->count(); $i < 4; $i++)
                            <td class="cell"></td>
                        @endfor
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach
</body>
</html>
