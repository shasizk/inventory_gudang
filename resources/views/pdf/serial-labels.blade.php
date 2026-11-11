<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Label serial number</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #111; }
        table.sheet { width: 100%; border-collapse: separate; border-spacing: 3mm; table-layout: fixed; }
        tr { page-break-inside: avoid; }
        td.label { height: 46mm; border: 0.3mm dashed #888; text-align: center; vertical-align: middle; padding: 2mm; }
        .name { font-size: 8.5pt; font-weight: bold; margin-bottom: 1.5mm; }
        .qr { width: 28mm; height: 28mm; }
        .serial { font-family: DejaVu Sans Mono, monospace; font-size: 8pt; margin-top: 1.5mm; word-break: break-all; }
    </style>
</head>
<body>
    <table class="sheet">
        @foreach ($serials->chunk(3) as $row)
            <tr>
                @foreach ($row as $sn)
                    <td class="label">
                        <div class="name">{{ \Illuminate\Support\Str::limit($sn->item_name, 40) }}</div>
                        <img class="qr" src="data:image/png;base64,{{ $qrs[$sn->id] }}" alt="QR {{ $sn->serial }}">
                        <div class="serial">{{ $sn->serial }}</div>
                    </td>
                @endforeach
                @for ($i = $row->count(); $i < 3; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>