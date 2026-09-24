<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.6; }
        .container { max-width: 560px; margin: 0 auto; padding: 24px; }
        .header { background: #06b6d4; color: #fff; padding: 20px 24px; border-radius: 12px 12px 0 0; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { background: #f8fafc; padding: 24px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 12px 12px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-received { background: #fef9c3; color: #a16207; }
        .badge-confirmed { background: #dcfce7; color: #15803d; }
        .badge-rejected, .badge-cancelled { background: #fee2e2; color: #b91c1c; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; background: #fff; border-radius: 8px; overflow: hidden; }
        td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        td.label { color: #64748b; width: 40%; }
        td.value { font-weight: 600; }
        .code { font-family: monospace; font-size: 22px; font-weight: bold; letter-spacing: 2px; color: #06b6d4; }
        .footer { margin-top: 20px; font-size: 12px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Lokanata Hotel</h1>
        </div>
        <div class="body">
            <p>Halo <strong>{{ $reservation->guest->name }}</strong>,</p>

            <p>
                @if($action === 'received')
                    Terima kasih! Pemesanan Anda telah kami terima dan sedang menunggu konfirmasi.
                @elseif($action === 'confirmed')
                    Kabar baik! Pemesanan Anda telah <strong> dikonfirmasi</strong>. Silakan datang sesuai tanggal check-in.
                @elseif($action === 'rejected')
                    Mohon maaf, pemesanan Anda <strong>tidak dapat dikonfirmasi</strong>. Silakan hubungi kami untuk informasi lebih lanjut.
                @else
                    Pemesanan Anda telah <strong>dibatalkan</strong>.
                @endif
            </p>

            <p style="text-align:center; margin: 24px 0;">
                <span class="code">{{ $reservation->reservation_code }}</span><br>
                <span class="badge badge-{{ $action }}">
                    @if($action === 'received') Menunggu Konfirmasi
                    @elseif($action === 'confirmed') Dikonfirmasi
                    @elseif($action === 'rejected') Ditolak
                    @else Dibatalkan
                    @endif
                </span>
            </p>

            <table>
                <tr><td class="label">Tamu</td><td class="value">{{ $reservation->guest->name }}</td></tr>
                <tr><td class="label">Kamar</td><td class="value">{{ $reservation->room->room_number }} — {{ $reservation->room->roomType->name }}</td></tr>
                <tr><td class="label">Check-in</td><td class="value">{{ $reservation->check_in_date->format('d M Y') }}</td></tr>
                <tr><td class="label">Check-out</td><td class="value">{{ $reservation->check_out_date->format('d M Y') }}</td></tr>
                <tr><td class="label">Jumlah Tamu</td><td class="value">{{ $reservation->number_of_guests }} orang</td></tr>
                <tr><td class="label">Total</td><td class="value">Rp {{ number_format($reservation->total_price, 0, ',', '.') }}</td></tr>
            </table>

            <p style="margin-top: 20px; font-size: 13px; color: #64748b;">
                Simpan kode reservasi ini. Anda dapat melacak status pemesanan kapan saja melalui halaman
                <strong>Lacak Reservasi</strong> di website kami.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Lokanata Hotel. Email ini dikirim otomatis, mohon tidak membalas.
        </div>
    </div>
</body>
</html>
