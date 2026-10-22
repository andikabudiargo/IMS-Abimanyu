@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@php
  $fields = ['open_debit'=>'Debit','open_credit'=>'Credit','mutasi_debit'=>'Debit','mutasi_credit'=>'Credit','saldo_akhir'=>'Balance'];
@endphp
<section>
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Create Trial Balance</h4>
    </div>
    <div class="card-content">
      <div class="card-body">
        <div class="form-row">
          <div class="form-group col-md-3">
            <label class="form-label">TB Number</label>
            <input type="text" id="tbNumber" class="form-control" value="{{ $nextCode }}" disabled>
          </div>
          <div class="form-group col-md-3">
            <label class="form-label">Year *</label>
            <select id="year" class="form-control" required>
              @for($y = date('Y'); $y >= 2023; $y--)
                <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
              @endfor
            </select>
          </div>
          </div>
          <div class="form-row">
          <div class="form-group col-md-6">
            <label class="form-label">Description *</label>
            <input type="text" id="description" class="form-control" required>
          </div>
        </div>
          <div class="form-row">
          <div class="form-group col-md-6">
            <label class="form-label">Note</label>
            <input type="text" id="note" class="form-control">
          </div>
        </div>
        <button type="button" id="btnGenerate" class="btn btn-primary">Generate</button>
        <button type="button" id="btnExport" class="btn btn-success" disabled>Export Excel</button>
      </div>
    </div>
  </div>

  <div id="tbTableWrap" class="card" style="display:none;">
    <div class="card-content">
      <div class="card-body">
        <small class="text-muted">Nilai diambil otomatis dari jurnal (kas/bank). Klik angka untuk lihat detail vouchernya.</small>
        <div class="custom-control custom-checkbox mb-2">
          <input type="checkbox" class="custom-control-input" id="chkHideZero">
          <label class="custom-control-label" for="chkHideZero">Hide akun yang nilainya 0 sepanjang tahun</label>
        </div>
        <div class="tbFixHead">
          <table class="table table-sm table-bordered tbGrid">
            <thead>
              <tr>
                <th rowspan="3" class="sticky-col col1">Account</th>
                <th rowspan="3" class="sticky-col col2">Description</th>
                <th rowspan="3">Account Type</th>
                <th rowspan="3">Normal Balance</th>
                <th rowspan="3">Final Statement</th>
                @foreach($months as $m)
                  <th colspan="5" class="text-center month-divider">{{ \DateTime::createFromFormat('n',$m)->format('F') }}</th>
                @endforeach
              </tr>
              <tr>
                @foreach($months as $m)
                  <th colspan="2" class="text-center month-divider">Saldo Awal</th>
                  <th colspan="2" class="text-center">Pergerakan</th>
                  <th class="text-center">Saldo Akhir</th>
                @endforeach
              </tr>
              <tr>
                @foreach($months as $m)
                  @foreach($fields as $field => $label)
                    <th class="text-center {{ $field === 'open_debit' ? 'month-divider' : '' }}">{{ $label }}</th>
                  @endforeach
                @endforeach
              </tr>
            </thead>
            <tbody>
              @foreach($groups as $g)
                @foreach($accounts->where('group_data', $g->group_data)->values() as $i => $acc)
                  <tr class="child-row {{ $i % 2 == 0 ? 'row-even' : 'row-odd' }}" data-group="{{ $g->group_data }}" data-account="{{ $acc->account }}" data-normal="{{ $acc->debit_credit }}">
                    <td class="sticky-col col1">{{ $acc->account }}</td>
                    <td class="sticky-col col2">{{ $acc->description }}</td>
                    <td>{{ $acc->group_data }} - {{ $acc->group_name }}</td>
                    <td>{{ $acc->debit_credit }}</td>
                    <td>{{ $acc->final_statement }}</td>
                    @foreach($months as $m)
                      @foreach(array_keys($fields) as $field)
                        <td class="cell-value text-right" data-month="{{ $m }}" data-field="{{ $field }}" data-raw="0">0</td>
                      @endforeach
                    @endforeach
                  </tr>
                @endforeach
                <tr class="subtotal-row" data-subtotal-group="{{ $g->group_data }}">
                  <td colspan="5">Subtotal {{ $g->group_name }}</td>
                  @foreach($months as $m)
                    @foreach(array_keys($fields) as $field)
                      <td class="subtotal-cell text-right" data-subtotal-group="{{ $g->group_data }}" data-month="{{ $m }}" data-field="{{ $field }}">0</td>
                    @endforeach
                  @endforeach
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr class="grandtotal-row">
                <td colspan="5">Grand Total</td>
                @foreach($months as $m)
                  @foreach(array_keys($fields) as $field)
                    <td class="grandtotal-cell text-right" data-month="{{ $m }}" data-field="{{ $field }}">0</td>
                  @endforeach
                @endforeach
              </tr>
            </tfoot>
          </table>
        </div>
        <button type="button" id="btnSave" class="btn btn-primary mt-2">Save</button>
        <a href="{{ route('trialBalance.index') }}" class="btn btn-secondary mt-2">Back</a>
      </div>
    </div>
  </div>
</section>
@include('accounting.trialBalance.saved._voucherModal')
@endsection
@section('styles')
<style>
  .tbFixHead { overflow: auto; max-height: 70vh; }
  .tbGrid { font-size: 11px; }
  .tbGrid th, .tbGrid td { white-space: nowrap; vertical-align: middle; }
  .tbGrid thead th { position: sticky; top: 0; background: #eee; z-index: 3; }
  .tbGrid thead tr:nth-child(2) th { top: 24px; }
  .tbGrid thead tr:nth-child(3) th { top: 48px; }
  .sticky-col { position: sticky; z-index: 2; background: inherit; }
  .col1 { left: 0; min-width: 90px; }
  .col2 { left: 90px; min-width: 150px; }
  .tbGrid thead th.sticky-col { z-index: 4; background: #eee; }
  .subtotal-row td { background: #f1f1f1; font-weight: 600; }
  .grandtotal-row td { background: #cfd8dc; font-weight: bold; }
  tr.row-even td { background: #ffffff; }
  tr.row-odd td { background: #f7f7f7; }
  .cell-value {
    min-width: 95px; padding: 2px 6px;
  }
  .cell-value[data-field^="mutasi"] { cursor: pointer; }
  .cell-value[data-field^="mutasi"]:hover { background: #fff3cd !important; text-decoration: underline; }
  .text-right { text-align: right; }
  .month-divider, [data-field="open_debit"] { border-left: 3px solid #6c757d !important; }
</style>
@endsection
@section('scripts')
@include('accounting.trialBalance.saved._voucherModalScript')
<script type="text/javascript">
  function fmt(n){
    return (n || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Sembunyikan baris akun yang nilainya 0 di semua bulan & semua field sepanjang tahun.
  function applyHideZero(){
    const hide = $('#chkHideZero').is(':checked');
    $('#tbTableWrap tr.child-row').each(function(){
      if(!hide){ $(this).show(); return; }
      let allZero = true;
      $(this).find('.cell-value').each(function(){
        if((parseFloat($(this).attr('data-raw')) || 0) !== 0){ allZero = false; return false; }
      });
      $(this).toggle(!allZero);
    });
  }

  $(document).ready(function(){
    $('#chkHideZero').on('change', applyHideZero);

    $('#btnGenerate').on('click', function(){
      const year = $('#year').val();
      if(!year){
        swal.fire('Warning', 'Isi Year dulu', 'warning');
        return;
      }
      window.tbYear = year;
      $(".loading-spinner-container").addClass("-show");
      $.get("{{ route('trialBalance.saved.generateData') }}", { year: year })
        .done(function(res){
          const data = res.data || {};
          const groupTotals = {};
          const grandTotals = {};

          $('#tbTableWrap tr.child-row').each(function(){
            const account = $(this).attr('data-account');
            const group = $(this).data('group');
            const normal = ($(this).attr('data-normal') || '').toUpperCase();
            const monthly = data[account] || {};
            $(this).find('.cell-value').each(function(){
              const m = $(this).data('month');
              const field = $(this).data('field');
              let val;
              if (field === 'saldo_akhir') {
                const sd = (monthly[m] && monthly[m].saldo_debit) || 0;
                const sc = (monthly[m] && monthly[m].saldo_credit) || 0;
                val = normal.indexOf('KREDIT') !== -1 ? (sc - sd) : (sd - sc);
              } else {
                val = (monthly[m] && monthly[m][field]) ? monthly[m][field] : 0;
              }
              $(this).attr('data-raw', val).text(fmt(val));

              const key = `${group}|${m}|${field}`;
              groupTotals[key] = (groupTotals[key] || 0) + val;
              const gkey = `${m}|${field}`;
              grandTotals[gkey] = (grandTotals[gkey] || 0) + val;
            });
          });

          $('.subtotal-cell').each(function(){
            const key = `${$(this).data('subtotal-group')}|${$(this).data('month')}|${$(this).data('field')}`;
            $(this).text(fmt(groupTotals[key] || 0));
          });
          $('.grandtotal-cell').each(function(){
            const key = `${$(this).data('month')}|${$(this).data('field')}`;
            $(this).text(fmt(grandTotals[key] || 0));
          });

          $('#tbTableWrap').show();
          $('#btnExport').prop('disabled', false).data('year', year);
          applyHideZero();
        })
        .fail(function(){
          swal.fire('Error', 'Gagal generate data dari jurnal', 'error');
        })
        .always(function(){
          $(".loading-spinner-container").removeClass("-show");
        });
    });

    $('#btnExport').on('click', function(){
      const year = $(this).data('year');
      window.location.href = "{{ route('trialBalance.saved.exportExcel') }}?year=" + encodeURIComponent(year);
    });

    $('#btnSave').on('click', function(){
      if(!$('#description').val().trim()){
        swal.fire('Warning', 'Description wajib diisi', 'warning');
        return;
      }
      const rows = [];
      $('#tbTableWrap tr.child-row').each(function(){
        const account = $(this).attr('data-account');
        const monthly = {};
        $(this).find('.cell-value').each(function(){
          const m = $(this).data('month');
          const field = $(this).data('field');
          if(!monthly[m]) monthly[m] = {open_debit:0,open_credit:0,mutasi_debit:0,mutasi_credit:0,saldo_akhir:0};
          monthly[m][field] = parseFloat($(this).attr('data-raw')) || 0;
        });
        rows.push({ account: account, monthly: monthly });
      });

      $.ajax({
        url: "{{ route('trialBalance.saved.store') }}",
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          year: $('#year').val(),
          description: $('#description').val(),
          note: $('#note').val(),
          rows: rows
        }),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(res){
          if(res.status == 1){
            swal.fire('Success', res.message, 'success').then(() => {
              window.location.href = res.redirect;
            });
          }else{
            swal.fire('Warning', res.message, 'warning');
          }
        },
        error: function(xhr){
          swal.fire('Error', xhr.responseJSON?.message || 'Failed to save', 'error');
        }
      });
    });
  });
</script>
@endsection
