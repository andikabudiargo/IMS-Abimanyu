@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
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
        <div class="tableFixHead" style="max-height:65vh;overflow:auto;">
          <table class="table table-sm table-bordered" id="tbTable">
            <thead></thead>
            <tbody></tbody>
          </table>
        </div>
        <button type="button" id="btnSave" class="btn btn-primary mt-2">Save</button>
        <a href="{{ route('trialBalance.saved.list') }}" class="btn btn-secondary mt-2">Back</a>
      </div>
    </div>
  </div>
</section>
@endsection
@section('styles')
<style>
  #tbTable th, #tbTable td { white-space: nowrap; font-size: 11px; }
  #tbTable input { width: 90px; padding: 2px 4px; font-size: 11px; }
  .tableFixHead thead th { position: sticky; top: 0; background: #eee; z-index: 2; }
  #tbTable td:first-child, #tbTable td:nth-child(2), #tbTable th:first-child, #tbTable th:nth-child(2) {
    position: sticky; left: 0; background: #fff; z-index: 1;
  }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  const accounts = {!! $accounts->toJson() !!};
  const months = {!! json_encode($months) !!};
  const monthNames = {!! json_encode(array_map(fn($m) => \DateTime::createFromFormat('n', $m)->format('M'), $months)) !!};

  function emptyMonthly(){
    const o = {};
    months.forEach(m => o[m] = {open_debit:0,open_credit:0,mutasi_debit:0,mutasi_credit:0,saldo_debit:0,saldo_credit:0});
    return o;
  }

  function buildTable(){
    let thead = '<tr><th rowspan="2">Account</th><th rowspan="2">Description</th><th rowspan="2">Normal Balance</th><th rowspan="2">Final Statement</th>';
    months.forEach((m,i) => thead += `<th colspan="6" class="text-center">${monthNames[i]}</th>`);
    thead += '</tr><tr>';
    months.forEach(() => thead += '<th>SA-D</th><th>SA-K</th><th>MUT-D</th><th>MUT-K</th><th>SK-D</th><th>SK-K</th>');
    thead += '</tr>';
    $('#tbTable thead').html(thead);

    let tbody = '';
    accounts.forEach(acc => {
      tbody += `<tr data-account="${acc.account}"><td>${acc.account}</td><td>${acc.description ?? ''}</td><td>${acc.debit_credit ?? ''}</td><td>${acc.final_statement ?? ''}</td>`;
      months.forEach(m => {
        ['open_debit','open_credit','mutasi_debit','mutasi_credit','saldo_debit','saldo_credit'].forEach(field => {
          tbody += `<td><input type="number" step="0.01" class="form-control form-control-sm" data-month="${m}" data-field="${field}" value="0"></td>`;
        });
      });
      tbody += '</tr>';
    });
    $('#tbTable tbody').html(tbody);
  }

  $(document).ready(function(){
    $('#btnGenerate').on('click', function(){
      if(!$('#year').val()){
        swal.fire('Warning', 'Isi Year dulu', 'warning');
        return;
      }
      buildTable();
      $('#tbTableWrap').show();
    });

    $('#btnSave').on('click', function(){
      const rows = [];
      $('#tbTable tbody tr').each(function(){
        const account = $(this).data('account');
        const monthly = emptyMonthly();
        $(this).find('input').each(function(){
          const m = $(this).data('month');
          const field = $(this).data('field');
          monthly[m][field] = parseFloat($(this).val()) || 0;
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
