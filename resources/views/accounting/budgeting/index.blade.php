@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">{{ $title }}</h4>
    <div class="heading-elements">
      <a href="{{ route('budgeting.create') }}" class="btn btn-primary btn-sm">
        <i data-feather="plus"></i> Add Budgeting
      </a>
    </div>
  </div>
  <div class="card-body">
    <div class="form-row mb-2">
      <div class="form-group col-md-3">
        <label class="form-label" for="fFiscalYear">Fiscal Year</label>
        <select id="fFiscalYear" class="form-control">
          <option value="">Semua</option>
          @foreach($list->pluck('fiscal_year')->unique()->sort()->reverse() as $fy)
            <option value="{{ $fy }}">{{ $fy }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="table-responsive">
      <table id="bgListTable" class="table table-sm table-hover">
        <thead class="thead-light">
          <tr>
            <th>Budgeting Number</th>
            <th>Fiscal Year</th>
            <th>Department</th>
            <th>Previous Period</th>
            <th>Budget Period</th>
            <th class="text-right">Total Budget</th>
            <th class="text-right">Actual</th>
            <th class="text-right">Margin</th>
            <th style="width:120px">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($list as $r)
          <tr>
            <td>{{ $r->budgeting_number }}</td>
            <td>{{ $r->fiscal_year }}</td>
            <td>{{ $r->dept_name ?: $r->dept_code }}</td>
            <td>{{ date('d M Y', strtotime($r->previous_from)) }} - {{ date('d M Y', strtotime($r->previous_to)) }}</td>
            <td>{{ date('d M Y', strtotime($r->budget_from)) }} - {{ date('d M Y', strtotime($r->budget_to)) }}</td>
            <td class="text-right">{{ number_format($r->total_budget, 2) }}</td>
            <td class="text-right">{{ number_format($r->actual, 2) }}</td>
            <td class="text-right {{ $r->margin < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($r->margin, 2) }}</td>
            <td>
              <a href="{{ route('budgeting.show', $r->id) }}" class="btn btn-sm btn-outline-primary" title="Detail"><i data-feather="eye"></i></a>
              <a href="{{ route('budgeting.edit', $r->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i data-feather="edit-2"></i></a>
              <button type="button" class="btn btn-sm btn-outline-danger btnDelete" data-id="{{ $r->id }}" title="Delete"><i data-feather="trash-2"></i></button>
            </td>
          </tr>
          @empty
          <tr><td colspan="9" class="text-center text-muted">Belum ada budgeting.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
  let bgTable;
  $(function () {
    bgTable = $('#bgListTable').DataTable({ order: [[0, 'desc']] });
    if (window.feather) feather.replace({ width: 14, height: 14 });
  });

  $('#fFiscalYear').on('change', function () {
    bgTable.column(1).search(this.value).draw();
  });

  $('#bgListTable').on('click', '.btnDelete', function () {
    const id = $(this).data('id');
    if (!confirm('Hapus budgeting ini? Data tidak dapat dikembalikan.')) return;

    $.ajax({
      url: '{{ url("budgeting") }}/' + id,
      type: 'DELETE',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    }).done(function (res) {
      alert(res.message || 'Berhasil dihapus.');
      location.reload();
    }).fail(function (xhr) {
      alert((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus.');
    });
  });
</script>
@endsection
