@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="cvr-filter">
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
              <label for="searchCode">Report Number</label>
              <input type="text" class="form-control text-uppercase" id="searchCode" name="searchCode" />
            </div>
            <div class="form-group col-md-3">
              <label for="searchName">Name</label>
              <input type="text" class="form-control" id="searchName" name="searchName" />
            </div>
            <div class="form-group col-md-2">
              <label for="searchYear">Tahun</label>
              <input type="number" class="form-control" id="searchYear" name="searchYear" min="2000" max="2100" />
            </div>
          </div>
          <div class="form-row">
            <div class="col-12">
              <button type="button" class="btn btn-primary" id="btnSearch">Search</button>
              <a href="{{ route('conversionReport.create') }}" class="btn btn-info">
                <i class="fa fa-plus"></i> Create
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="table-cvr">
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
          <table id="cvrTable" class="table">
            <thead class="thead-light"></thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>

@include('partials.delete-modal')
@endsection

@section('scripts')
<script type="text/javascript">
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  let searchCode = document.querySelector('#searchCode');
  let searchName = document.querySelector('#searchName');
  let searchYear = document.querySelector('#searchYear');
  let refresh    = document.querySelector('a[data-action="reload"]');

  const loadTable = () => {
    if ($('#cvrTable tr').length > 0) {
      let table = $('#cvrTable').DataTable();
      table.destroy();
      $('#cvrTable tbody > tr').remove();
      $('#cvrTable thead > tr').remove();
    }
    showDataTables({
      tableId: "cvrTable",
      route: "{{ route('conversionReport.list') }}",
      kolom: {!! $kolom !!},
      arrColPrint: [1, 2, 3, 4, 5, 6, 7],
      columnDefs: [{ width: '5%', targets: 0 }],
      dataSearch: {
        reportCode: searchCode.value,
        reportName: searchName.value,
        tahun: searchYear.value,
      }
    });
  }

  $("#btnSearch").click(function () { loadTable(); });
  refresh.addEventListener("click", function () { loadTable(); });

  function deleteReport(id, code) {
    Swal.fire({
      title: 'Are you sure?',
      html: `Delete <b>${code}</b>? This action can not be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#EA5455',
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        $('<form>', { action: "{{ route('conversionReport.destroy') }}", method: 'POST' })
          .append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }))
          .append($('<input>', { type: 'hidden', name: 'id', value: id }))
          .appendTo('body')
          .submit();
      }
    });
  }

  function cancelReport(id, code) {
    Swal.fire({
      title: 'Cancel this report?',
      html: `Cancel <b>${code}</b>?`,
      input: 'textarea',
      inputPlaceholder: 'Alasan cancel...',
      inputAttributes: { required: true },
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, cancel it',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#EA5455',
      reverseButtons: true,
      focusConfirm: false,
      preConfirm: (reason) => {
        if (!reason || !reason.trim()) {
          Swal.showValidationMessage('Alasan cancel wajib diisi');
          return false;
        }
        return reason;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        $('<form>', { action: "{{ route('conversionReport.cancel') }}", method: 'POST' })
          .append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }))
          .append($('<input>', { type: 'hidden', name: 'id', value: id }))
          .append($('<input>', { type: 'hidden', name: 'reason', value: result.value }))
          .appendTo('body')
          .submit();
      }
    });
  }

  $(function () { loadTable(); });
</script>
@endsection
