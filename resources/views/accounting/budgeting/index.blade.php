@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<section id="bg-filter">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Filter</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
          <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <form class="needs-validation" novalidate>
          <div class="form-row">
            <div class="form-group col-md-3">
              <label for="searchNumber">Budgeting Number</label>
              <input type="text" class="form-control text-uppercase" id="searchNumber" name="searchNumber" />
            </div>
            <div class="form-group col-md-3">
              <label for="searchDept">Department</label>
              <select class="select2 form-control" id="searchDept" name="searchDept" data-placeholder="Semua department">
                <option value=""></option>
                @foreach($depts as $val)
                  <option value="{{ $val->code }}">{{ $val->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-3">
              <label for="searchStatus">Status</label>
              <select class="select2 form-control" id="searchStatus" name="searchStatus" data-placeholder="Semua status">
                <option value=""></option>
                <option value="Overbudget">Overbudget</option>
                <option value="Underbudget">Underbudget</option>
                <option value="Sesuai Budget">Sesuai Budget</option>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label for="searchFiscalYear">Fiscal Year</label>
              <select class="select2 form-control" id="searchFiscalYear" name="searchFiscalYear" data-placeholder="Semua fiscal year">
                <option value=""></option>
                @foreach($fiscalYears as $fy)
                  <option value="{{ $fy }}">{{ $fy }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="col-12">
              <button type="button" class="btn btn-primary" id="btnSearch">Search</button>
              <a href="{{ route('budgeting.create') }}" class="btn btn-info">
                <i data-feather="plus"></i> Add Budgeting
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="table-bg">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">@yield('title') List</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
          <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
          <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <div class="card-datatable table-responsive pt-0">
          <table id="bgListTable" class="table">
            <thead class="thead-light"></thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@section('scripts')
<script type="text/javascript">
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  let searchNumber = document.querySelector('#searchNumber');
  let searchDept = document.querySelector('#searchDept');
  let searchStatus = document.querySelector('#searchStatus');
  let searchFiscalYear = document.querySelector('#searchFiscalYear');
  let refresh = document.querySelector('a[data-action="reload"]');

  const loadTable = () => {
    if ($('#bgListTable tr').length > 0) {
      let table = $('#bgListTable').DataTable();
      table.destroy();
      $('#bgListTable tbody > tr').remove();
      $('#bgListTable thead > tr').remove();
    }
    showDataTables({
      tableId: "bgListTable",
      route: "{{ route('budgeting.list') }}",
      kolom: {!! $kolom !!},
      arrColPrint: [1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 14],
      columnDefs: [{ width: '5%', targets: 0 }],
      dataSearch: {
        number: searchNumber.value,
        dept: searchDept.value,
        status: searchStatus.value,
        fiscalYear: searchFiscalYear.value,
      }
    });
  }

  $("#btnSearch").click(function () { loadTable(); });
  refresh.addEventListener("click", function () { loadTable(); });

  function deleteBudgeting(id, number) {
    Swal.fire({
      title: 'Are you sure?',
      html: `Delete <b>${number}</b>? This action can not be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#EA5455',
      reverseButtons: true,
    }).then((result) => {
      if (!result.isConfirmed) return;
      $.ajax({
        url: '{{ url("budgeting") }}/' + id,
        type: 'DELETE'
      }).done(function (res) {
        Swal.fire('Deleted!', res.message || 'Berhasil dihapus.', 'success').then(() => loadTable());
      }).fail(function (xhr) {
        Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus.', 'error');
      });
    });
  }

  $(function () { loadTable(); });
</script>
@endsection
