<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $payment->reservation->reservation_code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; font-size: 13px; }
        .page { padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #06b6d4; padding-bottom: 20px; margin-bottom: 30px; }
        .brand h1 { font-size: 24px; color: #06b6d4; margin-bottom: 4px; }
        .brand p { color: #64748b; font-size: 12px; line-height: 1.5; }
        .invoice-meta { text-align: right; }
        .invoice-meta h2 { font-size: 28px; color: #0f172a; letter-spacing: 3px; }
        .invoice-meta .code { font-family: monospace; font-size: 14px; color: #06b6d4; font-weight: bold; }
        .invoice-meta .date { color: #64748b; font-size: 12px; margin-top: 4px; }
        .meta-grid { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .meta-box h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 8px; }
        .meta-box p { line-height: 1.7; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead th { background: #f1f5f9; padding: 10px 14px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        tbody td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; }
        .text-right { text-align: right; }
        .totals { width: 320px; margin-left: auto; }
        .totals .row { display: flex; justify-content: space-between; padding: 8px 14px; }
        .totals .row.grand { background: #06b6d4; color: #fff; font-weight: bold; font-size: 15px; border-radius: 6px; margin-top: 6px; padding: 12px 14px; }
        .totals .row.label { color: #64748b; }
        .status-paid { display: inline-block; background: #dcfce7; color: #15803d; padding: 3px 12px; border-radius: 99px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 11px; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="brand">
            <h1>{{ $setting->hotel_name ?? 'Lokanata Hotel' }}</h1>
            <p>
                {{ $setting->address ?? '-' }}<br>
                Telp: {{ $setting->phone ?? '-' }} · Email: {{ $setting->email ?? '-' }}
            </p>
        </div>
        <div class="invoice-meta">
            <h2>INVOICE</h2>
            <div class="code">{{ $reservation->reservation_code }}</div>
            <div class="date">Tanggal: {{ $payment->paid_at?->format('d M Y H:i') ?? now()->format('d M Y') }}</div>
        </div>
    </div>

    <div class="meta-grid">
        <div class="meta-box">
            <h3>Ditagihkan Kepada</h3>
            <p>
                <strong>{{ $reservation->guest->name }}</strong><br>
                {{ $reservation->guest->email ?: '-' }}<br>
                {{ $reservation->guest->phone ?: '-' }}
            </p>
        </div>
        <div class="meta-box" style="text-align: right;">
            <h3>Status Pembayaran</h3>
            <p><span class="status-paid">{{ $payment->status === 'paid' ? 'LUNAS' : 'BELUM LUNAS' }}</span></p>
            <p style="margin-top: 8px; color: #64748b;">Metode: {{ $payment->payment_method ?: '-' }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>Kamar {{ $reservation->room->room_number }} — {{ $reservation->room->roomType->name }}</strong><br>
                    <span style="color: #64748b; font-size: 12px;">
                        {{ $reservation->check_in_date->format('d M Y') }} → {{ $reservation->check_out_date->format('d M Y') }}
                        ({{ max(1, $reservation->check_in_date->diffInDays($reservation->check_out_date)) }} malam)
                    </span>
                </td>
                <td class="text-right">Rp {{ number_format($room_total, 0, ',', '.') }}</td>
            </tr>
            @foreach($reservation->charges as $charge)
            <tr>
                <td>
                    {{ $charge->description }}
                    <span style="color: #94a3b8; font-size: 11px; text-transform: uppercase;">({{ $charge->category }})</span><br>
                    <span style="color: #64748b; font-size: 12px;">{{ $charge->quantity }} × Rp {{ number_format($charge->unit_price, 0, ',', '.') }}</span>
                </td>
                <td class="text-right">Rp {{ number_format($charge->total_price, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="row label"><span>Total Kamar</span><span>Rp {{ number_format($room_total, 0, ',', '.') }}</span></div>
        <div class="row label"><span>Biaya Tambahan</span><span>Rp {{ number_format($charges_total, 0, ',', '.') }}</span></div>
        <div class="row grand"><span>TOTAL</span><span>Rp {{ number_format($grand_total, 0, ',', '.') }}</span></div>
    </div>

    <div class="footer">
        Terima kasih atas kunjungan Anda ke {{ $setting->hotel_name ?? 'Lokanata Hotel' }}.<br>
        Dokumen ini dicetak secara elektronik dan sah tanpa tanda tangan.
    </div>
</div>
</body>
</html>
