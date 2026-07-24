@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<section id="movement-filter">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Filter</h4>
        </div>
        <div class="card-body">
          <form class="needs-validation" novalidate>
            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="filterDateRange">Range Date <span class="text-danger">*</span></label>
                <div class="input-group input-group-merge">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i data-feather="calendar"></i></span>
                  </div>
                  <input type="text" class="form-control" id="filterDateRange" placeholder="Pilih rentang tanggal" readonly>
                </div>
              </div>

              <div class="form-group col-md-4">
                <label for="filterArticle">Article</label>
                <input type="text" class="form-control text-uppercase" id="filterArticle" placeholder="">
              </div>

              <div class="form-group col-md-4">
                <label for="filterType">Article Type</label>
                <select class="select2 form-control" id="filterType">
                  <option value="">All</option>
                  @foreach($types as $val)
                    <option value="{{ $val->code }}">{{ $val->code }} - {{ $val->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="filterSupplier">Supplier/Customer</label>
                <select class="select2 form-control" id="filterSupplier">
                  <option value="">All</option>
                  @foreach($supps as $val)
                    <option value="{{ $val->kode }}">{{ $val->kode }} - {{ $val->nama }}</option>
                  @endforeach
                </select>
              </div>

              <div class="form-group col-md-4">
                <label for="filterLoc">Location</label>
                <select class="select2 form-control" id="filterLoc">
                  <option value="">All</option>
                  @foreach($locs as $val)
                    <option value="{{ $val->location_code }}">{{ $val->location_name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="form-group col-md-4">
                <label for="filterInout">Transaction</label>
                <select class="select2 form-control" id="filterInout">
                  <option value="">All</option>
                  <option value="in">IN</option>
                  <option value="out">OUT</option>
                  <option value="transfer">TRANSFER</option>
                  <option value="supply">SUPPLY</option>
                  <option value="adjustment">ADJUSTMENT</option>
                </select>
              </div>
            </div>

            <hr>

          <div class="form-row">
  <div class="col-12 d-flex align-items-center">
    <button type="button" class="btn btn-primary" id="btnSearch" disabled>
      <i data-feather="search" class="mr-50"></i> Search
    </button>

    <div class="btn-group ml-1" role="group">
      <button type="button" class="btn btn-success dropdown-toggle" id="btnExport"
              data-toggle="dropdown" aria-expanded="false" disabled>
        <i data-feather="download" class="mr-50"></i> Report
      </button>
      <div class="dropdown-menu" aria-labelledby="btnExport">
        <a class="dropdown-item" href="#" id="btnExportRaw">
          <i data-feather="file-text" class="mr-50"></i> Detail Report
        </a>
        <a class="dropdown-item" href="#" id="btnExportGrouped">
          <i data-feather="pie-chart" class="mr-50"></i> Summary Report
        </a>
      </div>
    </div>
  </div>
</div>
                <small class="text-muted ml-1" id="dateHint">Pilih Range Date dulu untuk mengaktifkan Search.</small>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="table-movement">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">@yield('title') List</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
          <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <div class="row">
          <div class="col-sm-12">
            <div class="card-datatable table-responsive pt-0">
              <table id="movementTable" class="table">
                <thead class="thead-light"></thead>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@section('styles')
<style>
  #movement-filter .input-group-text { background: #fff; }
  #movementTable td { vertical-align: middle; }
</style>
@endsection

@section('scripts')
<script type="text/javascript">

  const QTY_IN_IDX  = 9;
  const QTY_OUT_IDX = 10;

  let dateRangePicker = null;

  const getFilters = () => {
    const sel = dateRangePicker ? dateRangePicker.selectedDates : [];
    const fmt = (d) => dateRangePicker.formatDate(d, 'd-m-Y');

    return {
      article:  $('#filterArticle').val(),
      type:     $('#filterType').val(),
      supp:     $('#filterSupplier').val(),
      location: $('#filterLoc').val(),
      inout:    $('#filterInout').val(),
      fromDate: sel.length ? fmt(sel[0]) : '',
      toDate:   sel.length === 2 ? fmt(sel[1]) : (sel.length === 1 ? fmt(sel[0]) : '')
    };
  };

  const toggleButtons = () => {
    const enabled = !!getFilters().fromDate;
    $('#btnSearch, #btnExport').prop('disabled', !enabled);
    $('#dateHint').toggleClass('d-none', enabled);
  };

  // Beri tahu user bahwa arti IN/OUT berubah saat lokasi dipilih.
  const toggleInoutHint = () => {
    $('#inoutHint').text(
      $('#filterLoc').val()
        ? 'IN/OUT dihitung relatif terhadap lokasi yang dipilih.'
        : ''
    );
  };

  const warn = (msg) => {
    if (window.toastr) toastr.warning(msg); else alert(msg);
  };

  const showList = (p) => {
    if ($('#movementTable tr').length > 0) {
      $('#movementTable').DataTable().destroy();
      $('#movementTable tbody > tr, #movementTable thead > tr').remove();
    }

    const kolom = {!! $kolom !!};

    showDataTables({
      tableId: 'movementTable',
      route: "{{ route('stockMovement.list') }}",
      kolom: kolom,
      arrColPrint: kolom.map((_, i) => i),
      columnDefs: [
        { className: 'text-right', targets: [QTY_IN_IDX, QTY_OUT_IDX] }
      ],
      dataSearch: p,
      orderColumn: [[ kolom.length - 1, 'desc' ]],
      excelFileName: 'stock_movement',
      drawCallback: function () {
        if (window.feather) feather.replace();
      }
    });
  };

  $(document).ready(function () {
    $.ajaxSetup({
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    dateRangePicker = $('#filterDateRange').flatpickr({
      mode: 'range',
      dateFormat: 'd-m-Y',
      onChange: toggleButtons,
      onClose: toggleButtons
    });

    toggleButtons();
    toggleInoutHint();

    $('#filterLoc').on('change', toggleInoutHint);

    $('#btnSearch').on('click', function (e) {
      e.preventDefault();
      const p = getFilters();
      if (!p.fromDate) return warn('Silahkan pilih Range Date terlebih dahulu.');
      showList(p);
    });

    const doExport = (mode) => {
      const p = getFilters();
      if (!p.fromDate) return warn('Silahkan pilih Range Date terlebih dahulu.');
      window.location.href = "{{ route('stockMovement.export') }}?" + $.param({ ...p, mode });
    };

    $('#btnExportRaw').on('click', function (e) {
      e.preventDefault();
      doExport('detail');
    });

    $('#btnExportGrouped').on('click', function (e) {
      e.preventDefault();
      doExport('grouped');
    });
  });

</script>
@endsection