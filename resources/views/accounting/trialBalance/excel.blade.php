<table>
  <thead>
    <tr>
      <th rowspan="3">Account</th>
      <th rowspan="3">Description</th>
      <th rowspan="3">Normal Balance</th>
      <th rowspan="3">Final Statement</th>
      @foreach($months as $m)
        <th colspan="6">{{ \DateTime::createFromFormat('n',$m)->format('F') }} {{ $year }}</th>
      @endforeach
    </tr>
    <tr>
      @foreach($months as $m)
        <th colspan="2">Saldo Awal</th>
        <th colspan="2">Pergerakan</th>
        <th colspan="2">Saldo Akhir</th>
      @endforeach
    </tr>
    <tr>
      @foreach($months as $m)
        <th>Debit</th><th>Credit</th><th>Debit</th><th>Credit</th><th>Debit</th><th>Credit</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @php
      $fieldKeys = ['open_debit','open_credit','mutasi_debit','mutasi_credit','saldo_debit','saldo_credit'];
      $grandTotals = [];
    @endphp
    @foreach($groups as $g)
      @php
        $groupAccounts = $accounts->where('group_data', $g->group_data)->values();
        $groupTotals = [];
      @endphp
      <tr>
        <td colspan="{{ 4 + count($months)*6 }}"><strong>{{ $g->group_name }}</strong></td>
      </tr>
      @foreach($groupAccounts as $acc)
        <tr>
          <td>{{ $acc->account }}</td>
          <td>{{ $acc->description }}</td>
          <td>{{ $acc->debit_credit }}</td>
          <td>{{ $acc->final_statement }}</td>
          @foreach($months as $m)
            @foreach($fieldKeys as $field)
              @php
                $val = $monthlyData[$acc->account][$m][$field] ?? 0;
                $groupTotals["$m|$field"] = ($groupTotals["$m|$field"] ?? 0) + $val;
                $grandTotals["$m|$field"] = ($grandTotals["$m|$field"] ?? 0) + $val;
              @endphp
              <td>{{ number_format($val, 2, '.', '') }}</td>
            @endforeach
          @endforeach
        </tr>
      @endforeach
      <tr>
        <td colspan="4"><strong>Subtotal {{ $g->group_name }}</strong></td>
        @foreach($months as $m)
          @foreach($fieldKeys as $field)
            <td><strong>{{ number_format($groupTotals["$m|$field"] ?? 0, 2, '.', '') }}</strong></td>
          @endforeach
        @endforeach
      </tr>
    @endforeach
    <tr>
      <td colspan="4"><strong>Grand Total</strong></td>
      @foreach($months as $m)
        @foreach($fieldKeys as $field)
          <td><strong>{{ number_format($grandTotals["$m|$field"] ?? 0, 2, '.', '') }}</strong></td>
        @endforeach
      @endforeach
    </tr>
  </tbody>
</table>
