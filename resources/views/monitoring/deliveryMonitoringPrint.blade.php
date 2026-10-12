<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: sans-serif; font-size: 10px; }
    h2, h4 { margin: 0 0 6px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 18px; page-break-inside: avoid; }
    th, td { border: 1px solid #999; padding: 3px 5px; text-align: center; }
    td:first-child, th:first-child { text-align: left; }
    thead th { background: #eee; }
    tfoot td { font-weight: bold; background: #f5f5f5; }
  </style>
</head>
<body>
  <h2>Delivery Monitoring</h2>
  <p>Periode: {{ $from }} s/d {{ $to }}</p>
  @forelse($groups as $kode => $g)
    <h4>{{ $g['name'] }} @if(!$summary) ({{ $kode }}) @endif</h4>
    <table>
      <thead>
        <tr>
          @if(!empty($g['rowCustomer']))<th>Customer</th>@endif
          <th>Article</th>
          @foreach($g['dates'] as $d)<th>{{ $d }}</th>@endforeach
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach($g['articles'] as $rowKey => $articleName)
          <tr>
            @if(!empty($g['rowCustomer']))<td>{{ $g['rowCustomer'][$rowKey] ?? '' }}</td>@endif
            <td>{{ $articleName }}</td>
            @php($rowTotal = 0)
            @foreach($g['dates'] as $d)
              @php($qty = $g['matrix'][$rowKey][$d] ?? 0)
              @php($rowTotal += $qty)
              <td>{{ $qty ? rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.') : '-' }}</td>
            @endforeach
            <td>{{ rtrim(rtrim(number_format($rowTotal, 2, '.', ''), '0'), '.') }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          @if(!empty($g['rowCustomer']))<td></td>@endif
          <td>Total</td>
          @php($grandTotal = 0)
          @foreach($g['dates'] as $d)
            @php($colTotal = array_sum(array_column($g['matrix'], $d)))
            @php($grandTotal += $colTotal)
            <td>{{ rtrim(rtrim(number_format($colTotal, 2, '.', ''), '0'), '.') }}</td>
          @endforeach
          <td>{{ rtrim(rtrim(number_format($grandTotal, 2, '.', ''), '0'), '.') }}</td>
        </tr>
      </tfoot>
    </table>
  @empty
    <p>Tidak ada delivery pada periode ini</p>
  @endforelse
</body>
</html>
