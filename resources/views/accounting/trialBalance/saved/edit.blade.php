@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section>
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">{{ $hdr->tb_number }} ({{ $hdr->year }})</h4>
    </div>
    <div class="card-content">
      <div class="card-body">
        <div class="form-row">
          <div class="form-group col-md-5">
            <label class="form-label">Description</label>
            <input type="text" id="description" class="form-control" value="{{ $hdr->description }}">
          </div>
          <div class="form-group col-md-5">
            <label class="form-label">Note</label>
            <input type="text" id="note" class="form-control" value="{{ $hdr->note }}">
          </div>
          <div class="form-group col-md-2">
            <label class="form-label">Month</label>
            <select id="monthSelect" class="form-control">
              @foreach($months as $m)
                <option value="{{ $m }}">{{ \DateTime::createFromFormat('n', $m)->format('F') }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="tableFixHead" style="max-height:65vh;overflow:auto;">
          <table class="table table-sm table-bordered">
            <thead>
              <tr>
                <th>Account</th>
                <th>Description</th>
                <th>Normal Balance</th>
                <th>Final Statement</th>
                <th>Saldo Awal Debit</th>
                <th>Saldo Awal Credit</th>
                <th>Mutasi Debit</th>
                <th>Mutasi Credit</th>
                <th>Saldo Akhir Debit</th>
                <th>Saldo Akhir Credit</th>
              </tr>
            </thead>
            <tbody>
              @foreach($details as $d)
                <tr data-det-id="{{ $d->id }}">
                  <td>{{ $d->account }}</td>
                  <td>{{ $d->description }}</td>
                  <td>{{ $d->debit_credit }}</td>
                  <td>{{ $d->final_statement }}</td>
                  <td><input type="number" step="0.01" class="form-control form-control-sm m-open-debit" value="0"></td>
                  <td><input type="number" step="0.01" class="form-control form-control-sm m-open-credit" value="0"></td>
                  <td><input type="number" step="0.01" class="form-control form-control-sm m-mutasi-debit" value="0"></td>
                  <td><input type="number" step="0.01" class="form-control form-control-sm m-mutasi-credit" value="0"></td>
                  <td><input type="number" step="0.01" class="form-control form-control-sm m-saldo-debit" value="0"></td>
                  <td><input type="number" step="0.01" class="form-control form-control-sm m-saldo-credit" value="0"></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <button type="button" id="btnSave" class="btn btn-primary mt-2">Save</button>
        <a href="{{ route('trialBalance.saved.list') }}" class="btn btn-secondary mt-2">Back</a>
      </div>
    </div>
  </div>
</section>
@endsection
@section('scripts')
<script type="text/javascript">
  // data per det id: { 1: {1:{open_debit:0,...}, 2:{...}, ...}, 2: {...} }
  const tbData = {!! $details->mapWithKeys(function($d){ return [$d->id => json_decode($d->monthly, true)]; })->toJson() !!};
  let currentMonth = $('#monthSelect').val();

  function loadMonth(month){
    $('tbody tr[data-det-id]').each(function(){
      const detId = $(this).data('det-id');
      const m = (tbData[detId] && tbData[detId][month]) ? tbData[detId][month] : {open_debit:0,open_credit:0,mutasi_debit:0,mutasi_credit:0,saldo_debit:0,saldo_credit:0};
      $(this).find('.m-open-debit').val(m.open_debit);
      $(this).find('.m-open-credit').val(m.open_credit);
      $(this).find('.m-mutasi-debit').val(m.mutasi_debit);
      $(this).find('.m-mutasi-credit').val(m.mutasi_credit);
      $(this).find('.m-saldo-debit').val(m.saldo_debit);
      $(this).find('.m-saldo-credit').val(m.saldo_credit);
    });
  }

  function storeMonth(month){
    $('tbody tr[data-det-id]').each(function(){
      const detId = $(this).data('det-id');
      if(!tbData[detId]) tbData[detId] = {};
      tbData[detId][month] = {
        open_debit: parseFloat($(this).find('.m-open-debit').val()) || 0,
        open_credit: parseFloat($(this).find('.m-open-credit').val()) || 0,
        mutasi_debit: parseFloat($(this).find('.m-mutasi-debit').val()) || 0,
        mutasi_credit: parseFloat($(this).find('.m-mutasi-credit').val()) || 0,
        saldo_debit: parseFloat($(this).find('.m-saldo-debit').val()) || 0,
        saldo_credit: parseFloat($(this).find('.m-saldo-credit').val()) || 0,
      };
    });
  }

  $(document).ready(function(){
    loadMonth(currentMonth);

    $('#monthSelect').on('change', function(){
      storeMonth(currentMonth);
      currentMonth = $(this).val();
      loadMonth(currentMonth);
    });

    $('#btnSave').on('click', function(){
      storeMonth(currentMonth);
      const rows = Object.keys(tbData).map(id => ({ id: parseInt(id), monthly: tbData[id] }));
      const id = "{{ $id }}";
      $.ajax({
        url: "{{ route('trialBalance.saved.update') }}?id=" + encodeURIComponent(id),
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ rows: rows, description: $('#description').val(), note: $('#note').val() }),
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
