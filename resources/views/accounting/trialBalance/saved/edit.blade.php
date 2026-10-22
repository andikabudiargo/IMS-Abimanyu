@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@php
  $fields = ['open_debit'=>'Debit','open_credit'=>'Credit','mutasi_debit'=>'Debit','mutasi_credit'=>'Credit','saldo_akhir'=>'Balance'];
@endphp
<section>
  <div class="card">
    <div class="card-content">
      <div class="card-body">
        <div class="form-row">
          <div class="form-group col-md-3">
            <label class="form-label">TB Number</label>
            <input type="text" class="form-control" value="{{ $hdr->tb_number }}" disabled>
          </div>
          <div class="form-group col-md-3">
            <label class="form-label">Year</label>
            <input type="text" class="form-control" value="{{ $hdr->year }}" disabled>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label class="form-label">Description *</label>
            <input type="text" id="description" class="form-control" value="{{ $hdr->description }}" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label class="form-label">Note</label>
            <input type="text" id="note" class="form-control" value="{{ $hdr->note }}">
          </div>
        </div>
        <button type="button" id="btnRecalculate" class="btn btn-warning">Recalculate</button>
        <button type="button" id="btnExport" class="btn btn-success">Export Excel</button>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-content">
      <div class="card-body">
        <small class="text-muted">Nilai diambil otomatis dari jurnal saat dibuat. Klik angka untuk lihat detail vouchernya.</small>
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
                @foreach($details->where('group_data', $g->group_data)->values() as $i => $d)
                  @php
                    $monthly = json_decode($d->monthly, true) ?? [];
                    $isKredit = strtoupper($d->debit_credit ?? '') === 'KREDIT';
                    foreach ($monthly as &$_row) {
                      if (!isset($_row['saldo_akhir'])) {
                        $sd = $_row['saldo_debit'] ?? 0;
                        $sc = $_row['saldo_credit'] ?? 0;
                        $_row['saldo_akhir'] = $isKredit ? ($sc - $sd) : ($sd - $sc);
                      }
                    }
                    unset($_row);
                  @endphp
                  <tr class="child-row {{ $i % 2 == 0 ? 'row-even' : 'row-odd' }}" data-group="{{ $g->group_data }}" data-account="{{ $d->account }}" data-det-id="{{ $d->id }}">
                    <td class="sticky-col col1">{{ $d->account }}</td>
                    <td class="sticky-col col2">{{ $d->description }}</td>
                    <td>{{ $g->group_data }} - {{ $g->group_name }}</td>
                    <td>{{ $d->debit_credit }}</td>
                    <td>{{ $d->final_statement }}</td>
                    @foreach($months as $m)
                      @foreach(array_keys($fields) as $field)
                        <td class="cell-value text-right" data-month="{{ $m }}" data-field="{{ $field }}" data-raw="{{ $monthly[$m][$field] ?? 0 }}">{{ number_format($monthly[$m][$field] ?? 0, 2, ',', '.') }}</td>
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
<script type="text/javascript">
  window.tbYear = {{ $hdr->year }};
</script>
@include('accounting.trialBalance.saved._voucherModalScript')
<script type="text/javascript">
  // window.tbYear sudah di-set di atas

  function fmt(n){
    return (n || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function recomputeTotals(){
    const groupTotals = {};
    const grandTotals = {};
    $('tr.child-row').each(function(){
      const group = $(this).data('group');
      $(this).find('.cell-value').each(function(){
        const m = $(this).data('month');
        const field = $(this).data('field');
        const val = parseFloat($(this).attr('data-raw')) || 0;
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
  }

  // Sembunyikan baris akun yang nilainya 0 di semua bulan & semua field sepanjang tahun.
  function applyHideZero(){
    const hide = $('#chkHideZero').is(':checked');
    $('tr.child-row').each(function(){
      if(!hide){ $(this).show(); return; }
      let allZero = true;
      $(this).find('.cell-value').each(function(){
        if((parseFloat($(this).attr('data-raw')) || 0) !== 0){ allZero = false; return false; }
      });
      $(this).toggle(!allZero);
    });
  }

  $(document).ready(function(){
    recomputeTotals();

    $('#chkHideZero').on('change', applyHideZero);

    $('#btnExport').on('click', function(){
      window.location.href = "{{ route('trialBalance.saved.exportExcel') }}?year=" + encodeURIComponent(window.tbYear);
    });

    $('#btnRecalculate').on('click', function(){
      $(".loading-spinner-container").addClass("-show");
      $.get("{{ route('trialBalance.saved.generateData') }}", { year: window.tbYear })
        .done(function(res){
          const data = res.data || {};
          $('tr.child-row').each(function(){
            const account = $(this).attr('data-account');
            const normal = ($(this).find('td').eq(3).text() || '').toUpperCase();
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
            });
          });
          recomputeTotals();
          applyHideZero();
          swal.fire('Success', 'Data berhasil di-recalculate dari jurnal. Klik Save untuk menyimpan.', 'success');
        })
        .fail(function(){
          swal.fire('Error', 'Gagal mengambil ulang data dari jurnal', 'error');
        })
        .always(function(){
          $(".loading-spinner-container").removeClass("-show");
        });
    });

    $('#btnSave').on('click', function(){
      if(!$('#description').val().trim()){
        swal.fire('Warning', 'Description wajib diisi', 'warning');
        return;
      }
      const id = "{{ $id }}";
      const rows = [];
      $('tr.child-row').each(function(){
        const monthly = {};
        $(this).find('.cell-value').each(function(){
          const m = $(this).data('month');
          const field = $(this).data('field');
          if(!monthly[m]) monthly[m] = {open_debit:0,open_credit:0,mutasi_debit:0,mutasi_credit:0,saldo_akhir:0};
          monthly[m][field] = parseFloat($(this).attr('data-raw')) || 0;
        });
        rows.push({ id: $(this).data('det-id'), monthly: monthly });
      });

      $.ajax({
        url: "{{ route('trialBalance.saved.update') }}?id=" + encodeURIComponent(id),
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ description: $('#description').val(), note: $('#note').val(), rows: rows }),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(res){
          swal.fire(res.alert === 'success' ? 'Success' : 'Warning', res.message, res.alert);
        },
        error: function(){
          swal.fire('Error', 'Failed to save', 'error');
        }
      });
    });
  });
</script>
@endsection
