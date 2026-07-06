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
                </select>
              </div>
            </div>

            <hr>

            <div class="form-row">
              <div class="col-12">
                <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch" disabled>
                  <i data-feather="search"></i> Search
                </button>
                <small class="text-muted ml-1" id="dateHint">Pilih Range Date / Periode dulu untuk mengaktifkan Search.</small>
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

  let dateRangePicker = null;

  const article  = () => $("#filterArticle").val();
  const type     = () => $("#filterType").val();
  const supp     = () => $("#filterSupplier").val();
  const getLoc   = () => $("#filterLoc").val();
  const inout    = () => $("#filterInout").val();

  // ambil dd-mm-yyyy dari flatpickr
  const getDates = () => {
    const sel = dateRangePicker ? dateRangePicker.selectedDates : [];
    const fmt = (d) => dateRangePicker.formatDate(d, 'd-m-Y');
    const fromDate = sel.length ? fmt(sel[0]) : '';
    const toDate   = sel.length === 2 ? fmt(sel[1]) : (sel.length === 1 ? fmt(sel[0]) : '');
    return { fromDate, toDate };
  };

  // aktif/nonaktifkan tombol Search sesuai ada/tidaknya tanggal
  const toggleSearch = () => {
    const { fromDate } = getDates();
    if (fromDate) {
      $('#btnSearch').prop('disabled', false);
      $('#dateHint').addClass('d-none');
    } else {
      $('#btnSearch').prop('disabled', true);
      $('#dateHint').removeClass('d-none');
    }
  };

  $(document).ready(function () {
    dateRangePicker = $('#filterDateRange').flatpickr({
      mode: 'range',
      dateFormat: 'd-m-Y',
      onChange: function () {
        toggleSearch();
      },
      onClose: function () {
        toggleSearch();
      }
    });
    toggleSearch();
  });

  // Periode -> auto isi Range Date
  $('#filterPeriode').on('change', function () {
    const v = $(this).val();
    if (!v) return; // custom: biarkan user pilih manual

    const now   = new Date();
    let start   = new Date();
    let end     = new Date();

    if (v === 'today') {
      // start = end = hari ini
    } else if (v === 'yesterday') {
      start.setDate(now.getDate() - 1);
      end.setDate(now.getDate() - 1);
    } else if (v === 'week') {
      const day = (now.getDay() + 6) % 7; // Senin = 0
      start.setDate(now.getDate() - day);
    } else if (v === 'month') {
      start = new Date(now.getFullYear(), now.getMonth(), 1);
      end   = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    } else if (v === 'lastmonth') {
      start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
      end   = new Date(now.getFullYear(), now.getMonth(), 0);
    } else if (v === 'year') {
      start = new Date(now.getFullYear(), 0, 1);
      end   = new Date(now.getFullYear(), 11, 31);
    }

    dateRangePicker.setDate([start, end], true);
    toggleSearch();
  });

  $('#btnSearch').on('click', function (e) {
    e.preventDefault();
    const { fromDate, toDate } = getDates();

    // guard tambahan: jangan jalan tanpa tanggal
    if (!fromDate) {
      toastr ? toastr.warning('Silahkan pilih Range Date terlebih dahulu.')
             : alert('Silahkan pilih Range Date terlebih dahulu.');
      return;
    }

    showList({
      article:  article(),
      type:     type(),
      supp:     supp(),
      location: getLoc(),
      inout:    inout(),
      fromDate: fromDate,
      toDate:   toDate
    });
  });

  const showList = (p) => {
    if ($('#movementTable tr').length > 0) {
      let table = $('#movementTable').DataTable();
      table.destroy();
      $('#movementTable tbody > tr').remove();
      $('#movementTable thead > tr').remove();
    }

    const kolom = {!! $kolom !!};

    showDataTables({
      tableId: "movementTable",
      route: "{{ route('stockMovement.list') }}",
      kolom: kolom,
      arrColPrint: kolom.map((_, i) => i),
      columnDefs: [
        { className: 'text-right', targets: [9] } // kolom QTY
      ],
      dataSearch: {
        article:  p.article,
        type:     p.type,
        supp:     p.supp,
        location: p.location,
        inout:    p.inout,
        fromDate: p.fromDate,
        toDate:   p.toDate
      },
      orderColumn: [[ kolom.length - 1, 'desc' ]], // urutan -> terbaru di atas
      excelFileName: 'stock_movement',
      drawCallback: function () {
        if (window.feather) feather.replace();
      }
    });
  };

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

</script>
@endsection