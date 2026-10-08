@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="bankReconciliation-filter">
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
              <label for="searchReconNumber">Recon Number</label>
              <input type="text" class="form-control text-uppercase" id="searchReconNumber" name="searchReconNumber" />
            </div>
            <div class="form-group col-md-3">
              <label class="form-label" for="searchType">Type</label>
              <select class="select2 form-control" id="searchType" name="searchType">
                <option value="">All</option>
                <option value="KAS">Kas</option>
                <option value="BANK">Bank</option>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label class="form-label" for="searchPeriode">Periode</label>
              <select class="select2 form-control" id="searchPeriode" name="searchPeriode">
                <option value="">All</option>
                @for ($i = 1; $i <= 12; $i++)
                  <option value="{{ $i }}">{{ $i }}</option>
                @endfor
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="col-12">
              <button type="button" class="btn btn-primary" id="btnSearch">Search</button>
              <a href="{{ route('bankReconciliation.create') }}" class="btn btn-info">
                <i class="fa fa-plus"></i> Create
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="table-bankReconciliation">
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
        <div class="row mt-1">
          <div class="col-sm-12">
            <div class="card-datatable table-responsive pt-0">
              <table id="bankReconciliationTable" class="table">
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

  let searchReconNumber = document.querySelector('#searchReconNumber');
  let searchType         = document.querySelector('#searchType');
  let searchPeriode       = document.querySelector('#searchPeriode');
  let refresh             = document.querySelector('a[data-action="reload"]');

  function getSearchParams() {
    return {
      searchReconNumber: searchReconNumber.value,
      searchType: searchType.value,
      searchPeriode: searchPeriode.value,
    };
  }

  function destroyTable() {
    if ($('#bankReconciliationTable tr').length > 0) {
      let table = $('#bankReconciliationTable').DataTable();
      table.destroy();
      $('#bankReconciliationTable tbody > tr').remove();
      $('#bankReconciliationTable thead > tr').remove();
    }
  }

  const showList = () => {
    destroyTable();
    $(".loading-spinner-container").addClass("-show");
    showDataTables({
      tableId: "bankReconciliationTable",
      route: "{{ route('bankReconciliation.list') }}",
      kolom: {!! $kolom !!},
      arrColPrint: [1, 2, 3, 4, 5, 6, 7, 8],
      columnDefs: [
        { width: '5%', targets: 0 },
      ],
      dataSearch: getSearchParams(),
      initComplete: function () {
        $(".loading-spinner-container").removeClass("-show");
      },
      orderColumn: [[1, 'desc']],
      excelFileName: 'bank_reconciliation'
    });
  };

  refresh.addEventListener("click", showList);
  $("#btnSearch").click(showList);

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  $(document).ready(function () {
    showList();
  });

</script>
@endsection
