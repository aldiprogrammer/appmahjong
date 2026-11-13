<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>QR Code Meja {{ $meja->kode_meja }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; margin: 0; padding: 10px; background: #ffffff; color: #111827; }
        .card { border: 2px solid #15803d; border-radius: 12px; padding: 14px; text-align: center; }
        .title { font-size: 15px; font-weight: bold; color: #15803d; margin: 0 0 2px; letter-spacing: 1px; }
        .subtitle { font-size: 9px; color: #b91c1c; margin: 0 0 10px; }
        .code { font-size: 14px; font-weight: bold; color: #111827; background: #f3f4f6; padding: 6px; border-radius: 6px; margin-bottom: 10px; }
        .qr { margin: 6px 0 8px; }
        .qr img { width: 170px; height: 170px; }
        .name { font-size: 13px; font-weight: 600; color: #15803d; margin: 0 0 6px; }
        .footer { font-size: 8px; color: #6b7280; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="title">MEJA MAHJONG</div>
        <div class="subtitle">Scan QR Untuk Pesan Makanan & Point</div>
        <div class="code">{{ $meja->kode_meja }}</div>
        <div class="qr"><img src="{{ $qr }}" alt="QR Code {{ $meja->kode_meja }}"></div>
        <div class="name">{{ $meja->nama_meja }}</div>
        <div class="footer">{{ $meja->vanue?->nama ?? 'Mahjong Medan' }}</div>
    </div>
</body>
</html>
