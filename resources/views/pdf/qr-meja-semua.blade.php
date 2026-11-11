<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>QR Code Semua Meja</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; margin: 0; padding: 18px; background: #ffffff; color: #111827; }
        .heading { font-size: 16px; font-weight: bold; color: #15803d; margin: 0 0 4px; }
        .heading-sub { font-size: 9px; color: #6b7280; margin: 0 0 14px; }
        table.grid { width: 100%; border-collapse: separate; border-spacing: 8px; }
        td.cell { width: 33.33%; padding: 0; vertical-align: top; }
        .card { border: 2px solid #15803d; border-radius: 10px; padding: 8px; text-align: center; }
        .title { font-size: 10px; font-weight: bold; color: #15803d; margin: 0 0 1px; letter-spacing: 1px; }
        .subtitle { font-size: 7px; color: #b91c1c; margin: 0 0 6px; }
        .code { font-size: 10px; font-weight: bold; color: #111827; background: #f3f4f6; padding: 4px; border-radius: 5px; margin-bottom: 6px; }
        .qr { margin: 0 0 5px; }
        .qr img { width: 105px; height: 105px; }
        .name { font-size: 9px; font-weight: 600; color: #15803d; margin: 0; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="heading">QR CODE MEJA MAHJONG</div>
    <div class="heading-sub">Total {{ $items->count() }} meja &middot; Dicetak {{ now()->translatedFormat('d F Y H:i') }}</div>

    @foreach ($items->chunk(12) as $chunk)
        <table class="grid">
            @foreach ($chunk->chunk(3) as $row)
                <tr>
                    @foreach ($row as $item)
                        <td class="cell">
                            <div class="card">
                                <div class="title">MEJA MAHJONG</div>
                                <div class="subtitle">Scan QR Untuk Pesan Makanan & Point</div>
                                <div class="code">{{ $item['kode_meja'] }}</div>
                                <div class="qr"><img src="{{ $item['qr'] }}" alt="QR Code {{ $item['kode_meja'] }}"></div>
                                <div class="name">{{ $item['nama_meja'] }}</div>
                            </div>
                        </td>
                    @endforeach

                    @for ($i = $row->count(); $i < 3; $i++)
                        <td class="cell"></td>
                    @endfor
                </tr>
            @endforeach
        </table>

        @if (! $loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
