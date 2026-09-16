@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="fcr-filter">
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
              <label for="searchCr">Ticket Number</label>
              <input type="text" class="form-control text-uppercase" id="searchCr" name="searchCr" placeholder="CR-ASN-..." />
            </div>
            <div class="form-group col-md-3">
              <label class="form-label" for="searchModul">Modul</label>
              <select class="select2 form-control" id="searchModul" name="searchModul">
                <option value="">All</option>
                @foreach($modules as $val)
                  <option value="{{ $val }}">{{ $val }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-3">
              <label class="form-label" for="searchType">Type</label>
              <select class="select2 form-control" id="searchType" name="searchType">
                <option value="">All</option>
                @foreach($types as $val)
                  <option value="{{ $val }}">{{ $val }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-3">
              <label class="form-label" for="searchStatus">Status</label>
              <select class="select2 form-control" id="searchStatus" name="searchStatus">
                <option value="">All</option>
                @foreach($statusList as $index => $val)
                  <option value="{{ $index }}">{{ $index }} - {{ $val }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="searchDate">Created Date</label>
              <input type="text" id="searchDate" name="searchDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
            </div>
          </div>
          <div class="form-row">
            <div class="col-12">
              <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch">Search</button>
              <a href="{{ route('formChangeRequest.create') }}" class="btn btn-info">
                <i class="fa fa-plus"></i> Create
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="table-fcr">
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
        <div class="row">
          <div class="col-sm-12">
            <div class="card-datatable table-responsive pt-0">
              <table id="fcrTable" class="table">
                <thead class="thead-light"></thead>
              </table>
            </div>
          </div>
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

  let searchCr     = document.querySelector('#searchCr');
  let searchModul  = document.querySelector('#searchModul');
  let searchType   = document.querySelector('#searchType');
  let searchStatus = document.querySelector('#searchStatus');
  let searchDate   = document.querySelector('#searchDate');
  let refresh      = document.querySelector('a[data-action="reload"]');
  let rangePickr   = document.querySelector('.flatpickr-range');

  initDatePicker(rangePickr, {
    minDate: "01/01/2020",
    maxDate: "31/12/2035",
    dateFormat: "Y-m-d",
    mode: "range"
  });

  const loadTable = () => {
    if ($('#fcrTable tr').length > 0) {
      let table = $('#fcrTable').DataTable();
      table.destroy();
      $('#fcrTable tbody > tr').remove();
      $('#fcrTable thead > tr').remove();
    }
    showDataTables({
      tableId: "fcrTable",
      route: "{{ route('formChangeRequest.list') }}",
      kolom: {!! $kolom !!},
      arrColPrint: [1, 2, 3, 4, 5, 6, 7],
      columnDefs: [
        { width: '5%', targets: 0 },
      ],
      dataSearch: {
        crNumber: searchCr.value,
        modul: searchModul.value,
        type: searchType.value,
        status: searchStatus.value,
        date: searchDate.value,
      }
    });
  }

  $("#btnSearch").click(function () { loadTable(); });
  refresh.addEventListener("click", function () { loadTable(); });

  function deleteCr(id, crNumber) {
    Swal.fire({
      title: 'Are you sure?',
      html: `Delete <b>${crNumber}</b>? This action can not be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#EA5455',
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        $('<form>', {
          action: "{{ route('formChangeRequest.destroy') }}",
          method: 'POST'
        }).append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }))
          .append($('<input>', { type: 'hidden', name: 'id', value: id }))
          .appendTo('body')
          .submit();
      }
    });
  }

  $(function () { loadTable(); });
</script>
@endsection
