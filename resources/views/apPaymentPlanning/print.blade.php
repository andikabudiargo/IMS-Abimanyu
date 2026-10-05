<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>AP Payment Planning</title>
    <style>
        @page { margin: 20px 24px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #2b2f38; margin: 0; }
        h1 { font-size: 15px; text-align: center; margin: 0 0 2px; }
        .period { text-align: center; font-size: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #99a0ad; padding: 4px 5px; }
        thead th { background: #eef2f7; font-weight: bold; text-align: center; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        tr.grand td { font-weight: bold; background: #e9edf3; }
        .appr-label { font-weight: bold; text-align: center; vertical-align: middle; }
        .appr-box { text-align: center; vertical-align: bottom; height: 34px; }
        .appr-name { font-weight: bold; text-align: center; }
        .appr-blank { border: none; }
    </style>
</head>
<body>
    <h1>AP PAYMENT PLANNING</h1>
    <div class="period">Periode: {{ $periodLabel }}</div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Supplier</th>
                <th>Invoice Date</th>
                <th>Invoice Number</th>
                <th>Receive AP</th>
                <th>Due Date</th>
                <th>Voucher Number</th>
                <th>Note</th>
                <th>Nominal</th>
                <th>Biaya Administrasi</th>
                <th>PPH23</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $statusLabels = ['pending' => 'Pending', 'hold' => 'Hold', 'to_be_paid' => 'To Be Paid', 'paid' => 'Paid'];
            @endphp
            @foreach ($rows as $i => $r)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $r['supplier_name'] }}</td>
                    <td class="center">{{ $r['invoice_date'] }}</td>
                    <td>{{ $r['ap_number'] }}</td>
                    <td class="center">{{ $r['receive_ap'] }}</td>
                    <td class="center">{{ $r['due_date'] }}</td>
                    <td>{{ implode(', ', array_column($r['vouchers'], 'number')) ?: '-' }}</td>
                    <td>{{ $r['note'] }}</td>
                    <td class="num">{{ number_format($r['nominal'], 0) }}</td>
                    <td class="num">{{ number_format($r['biaya_administrasi'], 0) }}</td>
                    <td class="num">{{ number_format($r['pph23'], 0) }}</td>
                    <td class="num">{{ number_format($r['total'], 0) }}</td>
                    <td class="center">{{ $statusLabels[$r['status']] ?? $r['status'] }}{{ $r['status'] === 'hold' && $r['hold_reason'] ? ' ('.$r['hold_reason'].')' : '' }}</td>
                </tr>
            @endforeach
            <tr class="grand">
                <td colspan="8" class="num">GRAND TOTAL</td>
                <td class="num">{{ number_format($grand['nominal'], 0) }}</td>
                <td class="num">{{ number_format($grand['biaya_administrasi'], 0) }}</td>
                <td class="num">{{ number_format($grand['pph23'], 0) }}</td>
                <td class="num">{{ number_format($grand['total'], 0) }}</td>
                <td></td>
            </tr>

            <tr>
                <td class="appr-blank" colspan="8" rowspan="6"></td>
                <td class="appr-label">Disetujui</td>
                <td class="appr-label" colspan="2">Diperiksa</td>
                <td class="appr-label">Dibuat</td>
                <td class="appr-blank" rowspan="6"></td>
            </tr>
            <tr>
                <td class="appr-box" rowspan="4"></td>
                <td class="appr-box" rowspan="4"></td>
                <td class="appr-box" rowspan="4"></td>
                <td class="appr-box" rowspan="4"></td>
            </tr>
            <tr></tr>
            <tr></tr>
            <tr></tr>
            <tr>
                <td class="appr-name">Budi Mulyadi</td>
                <td class="appr-name">Yorin Asali</td>
                <td class="appr-name">Nopi Yulianingsih</td>
                <td class="appr-name">Hanna Syifa K.</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
