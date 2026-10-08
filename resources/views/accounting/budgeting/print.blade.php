<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $hdr->budgeting_number }}</title>
<style type="text/css">
  body { font-family: sans-serif; font-size: 10px; }
  h3 { margin: 0 0 2px 0; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  th, td { border: 1px solid #999; padding: 3px 5px; }
  th { background: #eee; text-align: center; }
  td.num, th.num { text-align: right; }
  tfoot td { font-weight: bold; background: #f3f3f3; }
</style>
</head>
<body>
  <h3>{{ $hdr->budgeting_number }} - {{ $hdr->dept_name ?: $hdr->dept_code }} (FY {{ $hdr->fiscal_year }})</h3>
  @if($hdr->description)<div>{{ $hdr->description }}</div>@endif
  <div>
    Previous Period: {{ date('d M Y', strtotime($hdr->previous_from)) }} - {{ date('d M Y', strtotime($hdr->previous_to)) }}
    &nbsp;|&nbsp;
    Budget Period: {{ date('d M Y', strtotime($hdr->budget_from)) }} - {{ date('d M Y', strtotime($hdr->budget_to)) }}
  </div>
  <div>
    Previous Expenses: {{ number_format($cards['previous_expenses'], 2) }}
    &nbsp;|&nbsp; Total Budget: {{ number_format($cards['total_budget'], 2) }}
    &nbsp;|&nbsp; Actual Expenses: {{ number_format($cards['actual_expenses'], 2) }}
    &nbsp;|&nbsp; Margin: {{ number_format($cards['margin'], 2) }} ({{ $cards['margin_pct'] }}%)
  </div>

  <table>
    <thead>
      <tr>
        <th>Account</th><th>Name</th><th>Debit</th><th>Average</th><th>CR %</th>
        <th>Monthly Budget</th><th>Final Budget (Total)</th>
        @foreach($months as $m)
          <th>{{ $m }}</th>
        @endforeach
        <th>Total Realisasi</th><th>Selisih</th><th>Realisasi %</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $r)
      <tr>
        <td>{{ $r['account'] }}</td>
        <td>{{ $r['nama_akun'] }}</td>
        <td class="num">{{ number_format($r['debit'], 2) }}</td>
        <td class="num">{{ number_format($r['average'], 2) }}</td>
        <td class="num">{{ $r['cost_reduction'] }}</td>
        <td class="num">{{ number_format($r['final_budget_monthly'], 2) }}</td>
        <td class="num">{{ number_format($r['final_budget'], 2) }}</td>
        @foreach($months as $m)
          <td class="num">{{ number_format($r['realisasi'][$m] ?? 0, 2) }}</td>
        @endforeach
        <td class="num">{{ number_format($r['realisasi_total'], 2) }}</td>
        <td class="num">{{ number_format($r['selisih'], 2) }}</td>
        <td class="num">{{ $r['realisasi_pct'] }}%</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
