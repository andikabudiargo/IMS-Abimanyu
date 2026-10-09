<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $hdr->budgeting_number }}</title>
<style type="text/css">
  @page { margin: 12px; }
  body { font-family: sans-serif; font-size: 7px; }
  h3 { margin: 0 0 2px 0; font-size: 12px; }
  table { width: 100%; table-layout: fixed; border-collapse: collapse; margin-top: 8px; }
  th, td { border: 1px solid #999; padding: 2px 3px; word-wrap: break-word; overflow-wrap: break-word; }
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

  @php
    // Lebar kolom tetap (Account s/d Realisasi %) dalam %, sisanya dibagi rata ke kolom bulan
    // supaya tabel tetap muat 1 halaman landscape walau budget period-nya panjang.
    $fixedColsPct = ['unbudget' => 4, 'account' => 6, 'name' => 10, 'debit' => 6, 'average' => 6, 'inflasi' => 3, 'cr' => 3, 'monthly' => 6, 'additional' => 6, 'final' => 7, 'total_real' => 6, 'selisih' => 6, 'pct' => 4];
    $monthPct = count($months) > 0 ? round((100 - array_sum($fixedColsPct)) / count($months), 2) : 0;
  @endphp
  <table>
    <colgroup>
      <col style="width:{{ $fixedColsPct['unbudget'] }}%">
      <col style="width:{{ $fixedColsPct['account'] }}%">
      <col style="width:{{ $fixedColsPct['name'] }}%">
      <col style="width:{{ $fixedColsPct['debit'] }}%">
      <col style="width:{{ $fixedColsPct['average'] }}%">
      <col style="width:{{ $fixedColsPct['inflasi'] }}%">
      <col style="width:{{ $fixedColsPct['cr'] }}%">
      <col style="width:{{ $fixedColsPct['monthly'] }}%">
      <col style="width:{{ $fixedColsPct['additional'] }}%">
      <col style="width:{{ $fixedColsPct['final'] }}%">
      @foreach($months as $m)
        <col style="width:{{ $monthPct }}%">
      @endforeach
      <col style="width:{{ $fixedColsPct['total_real'] }}%">
      <col style="width:{{ $fixedColsPct['selisih'] }}%">
      <col style="width:{{ $fixedColsPct['pct'] }}%">
    </colgroup>
    <thead>
      <tr>
        <th>Unbudget</th><th>Account</th><th>Name</th><th>Debit</th><th>Average</th><th>Inflasi %</th><th>CR %</th>
        <th>Monthly Budget</th><th>Additional Budget</th><th>Final Budget (Total)</th>
        @foreach($months as $m)
          <th>{{ $m }}</th>
        @endforeach
        <th>Total Realisasi</th><th>Selisih</th><th>Realisasi %</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $r)
      <tr>
        <td style="text-align:center">{{ $r['is_unbudget'] ? 'YA' : '' }}</td>
        <td>{{ $r['account'] }}</td>
        <td>{{ $r['nama_akun'] }}</td>
        <td class="num">{{ number_format($r['debit'], 2) }}</td>
        <td class="num">{{ number_format($r['average'], 2) }}</td>
        <td class="num">{{ $r['inflation'] }}</td>
        <td class="num">{{ $r['cost_reduction'] }}</td>
        <td class="num">{{ number_format($r['final_budget_monthly'], 2) }}</td>
        <td class="num">{{ number_format($r['additional_budget'], 2) }}</td>
        <td class="num">{{ number_format($r['final_budget'], 2) }}</td>
        @foreach($months as $m)
          <td class="num">{{ number_format($r['realisasi'][$m] ?? 0, 2) }}</td>
        @endforeach
        <td class="num">{{ $r['is_unbudget'] ? '-' : number_format($r['realisasi_total'], 2) }}</td>
        <td class="num">{{ $r['selisih'] === null ? '-' : number_format($r['selisih'], 2) }}</td>
        <td class="num">{{ $r['realisasi_pct'] === null ? '-' : $r['realisasi_pct'] . '%' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
