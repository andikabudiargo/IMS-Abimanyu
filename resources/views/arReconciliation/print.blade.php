<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>AR Reconciliation</title>
    <style>
        @page { margin: 20px 24px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #2b2f38; margin: 0; }
        h1 { font-size: 15px; text-align: center; margin: 0 0 2px; }
        .sub { text-align: center; font-size: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #99a0ad; padding: 4px 5px; }
        thead th { background: #eef2f7; font-weight: bold; text-align: center; }
        td.num { text-align: right; }
        tr.opening td { background: #f4f7fb; font-weight: bold; }
        tr.grand td { font-weight: bold; background: #e9edf3; }
        .neg { color: #c0392b; }
        .pos { color: #1e8449; }
    </style>
</head>
<body>
    <h1>AR RECONCILIATION</h1>
    <div class="sub">Tahun: {{ $year }} | Basis: {{ $basis === 'delivery' ? 'Delivery Note' : 'AR Invoice' }}</div>

    <table>
        <thead>
            <tr>
                <th>Periode</th>
                <th>DPP</th>
                <th>{{ $basis === 'delivery' ? 'Delivery Note' : 'AR Invoice' }}</th>
                @if ($basis !== 'delivery')
                <th>Debit Note</th>
                @endif
                <th>Pengurang (Bank/Kas)</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr class="opening">
                <td>Saldo Awal</td>
                <td class="num">-</td>
                <td class="num">-</td>
                @if ($basis !== 'delivery')
                <td class="num">-</td>
                @endif
                <td class="num">-</td>
                <td class="num {{ $openingBalance < 0 ? 'neg' : 'pos' }}">{{ number_format($openingBalance, 2) }}</td>
            </tr>
            @foreach ($rows as $r)
                <tr>
                    <td>{{ $r['label'] }}</td>
                    <td class="num">{{ number_format($r['dpp'], 2) }}</td>
                    <td class="num">{{ number_format($r['addition'], 2) }}</td>
                    @if ($basis !== 'delivery')
                    <td class="num">{{ number_format($r['debitNote'], 2) }}</td>
                    @endif
                    <td class="num">{{ number_format($r['payment'], 2) }}</td>
                    <td class="num {{ $r['balance'] < 0 ? 'neg' : 'pos' }}">{{ number_format($r['balance'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="grand">
                <td>TOTAL</td>
                <td class="num">{{ number_format($totals['dpp'], 2) }}</td>
                <td class="num">{{ number_format($totals['addition'], 2) }}</td>
                @if ($basis !== 'delivery')
                <td class="num">{{ number_format($totals['debitNote'], 2) }}</td>
                @endif
                <td class="num">{{ number_format($totals['payment'], 2) }}</td>
                <td class="num {{ $closingBalance < 0 ? 'neg' : 'pos' }}">{{ number_format($closingBalance, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
