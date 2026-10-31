<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tiket Booking</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; margin: 0; padding: 16px; background: #ffffff; color: #111827; }
        .ticket { width: 340px; margin: 0 auto; border: 2px solid #15803d; border-radius: 10px; padding: 14px; }
        .title { text-align: center; font-size: 16px; font-weight: bold; color: #15803d; margin: 0 0 2px; }
        .subtitle { text-align: center; font-size: 10px; color: #b91c1c; margin: 0 0 10px; }
        .code { text-align: center; font-size: 14px; font-weight: bold; color: #111827; background: #f3f4f6; padding: 6px; border-radius: 6px; margin-bottom: 10px; }
        .qr { text-align: center; margin: 8px 0; }
        .qr img { width: 110px; height: 110px; }
        .details { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .details td { font-size: 11px; padding: 4px 0; }
        .details td:first-child { color: #6b7280; }
        .details td:last-child { text-align: right; font-weight: 600; color: #15803d; }
        .status { text-align: center; margin-top: 10px; }
        .badge { display: inline-block; background: #15803d; color: #ffffff; padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: bold; }
        .footer { text-align: center; font-size: 9px; color: #6b7280; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="title">TIKET BOOKING</div>
        <div class="subtitle">Mahjong Booking</div>
        <div class="code">{{ $booking->kode_booking }}</div>
        <div class="qr"><img src="{{ $qr }}" alt="QR Code"></div>
        <table class="details">
            <tr><td>Nama</td><td>{{ $pengguna->nama_depan }} {{ $pengguna->nama_balakang }}</td></tr>
            <tr><td>Meja</td><td>Meja {{ $booking->id_meja }}</td></tr>
            <tr><td>Tanggal</td><td>{{ \Carbon\Carbon::parse($booking->tgl_main)->translatedFormat('d F Y') }}</td></tr>
            <tr><td>Jam</td><td>{{ substr($booking->jam_mulai,0,5) }} - {{ substr($booking->jam_selesai,0,5) }}</td></tr>
            <tr><td>Total</td><td>Rp {{ number_format((int) $booking->total_harga, 0, ',', '.') }}</td></tr>
            <tr><td>Pembayaran</td><td>{{ $booking->kode_pembayaran ?: $booking->kode_booking }}</td></tr>
        </table>
        <div class="status"><span class="badge">DISETUJUI</span></div>
        <div class="footer">Tunjukkan tiket ini saat bermain.</div>
    </div>
</body>
</html>
