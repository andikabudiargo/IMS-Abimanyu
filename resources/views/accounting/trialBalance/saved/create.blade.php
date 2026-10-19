@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@php
  $fields = ['open_debit'=>'Debit','open_credit'=>'Credit','mutasi_debit'=>'Debit','mutasi_credit'=>'Credit','saldo_debit'=>'Debit','saldo_credit'=>'Credit'];
@endphp
<section>
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Create Trial Balance</h4>
    </div>
    <div class="card-content">
      <div class="card-body">
        <div class="form-row">
          <div class="form-group col-md-2">
            <label class="form-label">Nomor</label>
            <input type="text" id="tbNumber" class="form-control" value="{{ $nextCode }}" disabled>
          </div>
          <div class="form-group col-md-2">
            <label class="form-label">Year *</label>
            <input type="number" id="year" class="form-control" value="{{ date('Y') }}" min="2000" max="2100" required>
          </div>
          <div class="form-group col-md-4">
            <label class="form-label">Description</label>
            <input type="text" id="description" class="form-control">
          </div>
          <div class="form-group col-md-4">
            <label class="form-label">Note</label>
            <input type="text" id="note" class="form-control">
          </div>
        </div>
        <button type="button" id="btnGenerate" class="btn btn-primary">Generate</button>
      </div>
    </div>
  </div>

  <div id="tbTableWrap" class="card" style="display:none;">
    <div class="card-content">
      <div class="card-body">
        <small class="text-muted">Nilai diambil otomatis dari jurnal (kas/bank). Klik angka untuk lihat detail vouchernya.</small>
        <div class="tbFixHead">
          <table class="table table-sm table-bordered tbGrid">
            <thead>
              <tr>
                <th rowspan="3" class="sticky-col col1">Account</th>
                <th rowspan="3" class="sticky-col col2">Description</th>
                <th rowspan="3">Normal Balance</th>
                <th rowspan="3">Final Statement</th>
                @foreach($months as $m)
                  <th colspan="6" class="text-center">{{ \DateTime::createFromFormat('n',$m)->format('F') }}</th>
                @endforeach
              </tr>
              <tr>
                @foreach($months as $m)
                  <th colspan="2" class="text-center">Saldo Awal</th>
                  <th colspan="2" class="text-center">Pergerakan</th>
                  <th colspan="2" class="text-center">Saldo Akhir</th>
                @endforeach
              </tr>
              <tr>
                @foreach($months as $m)
                  @foreach($fields as $label)
                    <th class="text-center">{{ $label }}</th>
                  @endforeach
                @endforeach
              </tr>
            </thead>
            <tbody>
              @foreach($groups as $g)
                <tr class="group-row" data-group="{{ $g->group_data }}">
                  <td colspan="{{ 4 + count($months)*6 }}"><span class="toggle-sign">+</span> {{ $g->group_name }}</td>
                </tr>
                @foreach($accounts->where('group_data', $g->group_data)->values() as $i => $acc)
                  <tr class="child-row {{ $i % 2 == 0 ? 'row-even' : 'row-odd' }}" data-group="{{ $g->group_data }}" data-account="{{ $acc->account }}" style="display:none;">
                    <td class="sticky-col col1">{{ $acc->account }}</td>
                    <td class="sticky-col col2">{{ $acc->description }}</td>
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
                  <td colspan="4">Subtotal {{ $g->group_name }}</td>
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
                <td colspan="4">Grand Total</td>
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
        <a href="{{ route('trialBalance.saved.list') }}" class="btn btn-secondary mt-2">Back</a>
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
  .group-row td { background: #d8d8d8; font-weight: bold; cursor: pointer; }
  .subtotal-row td { background: #f1f1f1; font-weight: 600; }
  .grandtotal-row td { background: #cfd8dc; font-weight: bold; }
  tr.row-even td { background: #ffffff; }
  tr.row-odd td { background: #f7f7f7; }
  .cell-value {
    min-width: 95px; padding: 2px 6px; cursor: pointer;
  }
  .cell-value:hover { background: #fff3cd !important; text-decoration: underline; }
  .text-right { text-align: right; }
</style>
@endsection
@section('scripts')
@include('accounting.trialBalance.saved._voucherModalScript')
<script type="text/javascript">
  function fmt(n){
    return (n || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  $(document).ready(function(){
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
            const account = $(this).data('account');
            const group = $(this).data('group');
            const monthly = data[account] || {};
            $(this).find('.cell-value').each(function(){
              const m = $(this).data('month');
              const field = $(this).data('field');
              const val = (monthly[m] && monthly[m][field]) ? monthly[m][field] : 0;
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
        })
        .fail(function(){
          swal.fire('Error', 'Gagal generate data dari jurnal', 'error');
        })
        .always(function(){
          $(".loading-spinner-container").removeClass("-show");
        });
    });

    $(document).on('click', 'tr.group-row', function(){
      const g = $(this).data('group');
      $(`tr.child-row[data-group="${g}"]`).toggle();
      $(this).find('.toggle-sign').text(function(i, old){ return old === '+' ? '-' : '+'; });
    });

    $('#btnSave').on('click', function(){
      const rows = [];
      $('#tbTableWrap tr.child-row').each(function(){
        const account = $(this).data('account');
        const monthly = {};
        $(this).find('.cell-value').each(function(){
          const m = $(this).data('month');
          const field = $(this).data('field');
          if(!monthly[m]) monthly[m] = {open_debit:0,open_credit:0,mutasi_debit:0,mutasi_credit:0,saldo_debit:0,saldo_credit:0};
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
