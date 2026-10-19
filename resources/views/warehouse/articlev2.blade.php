@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="article-index">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">  
            <h4 class="card-title">Filter</h4>
          </div>
          <div class="card-body">
            <form class="needs-validation" novalidate>
                <div class="form-row">
                    <div class="form-group col-md-3"> 
                      <label for="seachCode">Article Code</label>
                      <input type="text" class="form-control text-uppercase" id="seachCode" name="seachCode" placeholder=""  />
                    </div>
                    <div class="form-group col-md-3"> 
                      <label for="searchName">Name</label>
                      <input type="text" class="form-control text-uppercase" id="searchName" name="searchName" placeholder="" />
                    </div>
                    <div class="form-group col-md-3"> 
                      <label class="form-label" for="searchType">Article Type</label>
                      <select class="select2 form-control" id="searchType" name="searchType">
                          <option value="">All</option>
                          @foreach($types as $val)
                              <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                          @endforeach
                      </select>
                    </div>
                    <div class="form-group col-md-3"> 
                      <label class="form-label" for="searchSupplier">Supplier/Customer</label>
                      <select class="select2 form-control" id="searchSupplier" name="searchSupplier">
                          <option value="">All</option>
                          @foreach($supps as $val)
                              <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                          @endforeach
                      </select>
                    </div>
                     <div class="form-group col-md-3"> 
                      <label class="form-label" for="searchLoc">Location</label>
                      <select class="select2 form-control" id="searchLoc" name="searchLoc">
                          <option value="">All</option>
                          @foreach($locs as $val)
                            <option value="{{$val->location_code}}" >{{$val->location_name}}</option>
                          @endforeach
                      </select>
                    </div>
                    <div class="form-group col-md-3"> 
                      <label class="form-label" for="searchStatus">Status Stock</label>
                      <select class="select2 form-control" id="searchStatus" name="searchStatus">
                          <option value="">All</option>
                          <option value="critical">Critical</option>
                          <option value="save">Save</option>
                           <option value="empty">Empty</option>
                      </select>
                    </div>
                     {{--<div class="form-group col-md-4"> 
                      <label class="form-label" for="searchGroup">Group</label>
                      <select class="select2 form-control" id="searchGroup" name="searchGroup">
                          <option value="">All</option>
                            <option value="cm1" >Chemical</option>
                            <option value="cm2" >Consumable</option>
                            <option value="rm" >Raw Material</option>
                            <option value="wip" >Work in Progress (WIP)</option>
                            <option value="fg" >Finished Goods</option>
                            <option value="ot" >Out Total (OT)</option>
                      </select>
                    </div>--}}
                    <div class="col-md-3 col-12 mb-1">
                      <label for="searchQty">QTY</label>
                      {{-- <fieldset> --}}
                          <div class="input-group">
                              <div class="input-group-prepend">
                                <select class="form-control" id="searchOperator" name="searchOperator">
                                  <option value="">All</option>
                                  <option value=">">></option>
                                  <option value="<"><</option>
                                  <option value="=">=</option>
                                </select>
                              </div>
                              <input type="text" class="form-control numeral-mask-digit text-right" id="searchQty" name="searchQty" />
                          </div>
                      {{-- </fieldset> --}}
                  </div>
                  <div class="form-group col-md-3">
  <label class="form-label" for="searchAsof">Stock Date</label>
  <div class="input-group input-group-merge">
    <div class="input-group-prepend">
      <span class="input-group-text"><i data-feather="calendar"></i></span>
    </div>
    <input type="text" class="form-control" id="searchAsof" name="searchAsof"
           placeholder="Keep empty for current stock..." autocomplete="off">
  </div>
</div>
                </div>
                {{--<div class="form-row">
                    <div class="col-md-12"> 
                      <label for="">Article Type</label>
                      <div class="demo-inline-spacing">
                        @foreach($types as $key => $val)
                           <a href="{{ route('article.create') }}" class="btn btn-success"> {{ $val->name }}</a>
                          <button type="button" class="btn btn-primary btnSearch" id ="btnSearch{{$val->code}}" name="btnSearch{{$val->code}}" data-type="{{$val->code}}">{{ $val->name }}</button>
                        @endforeach
                          <button type="button" class="btn btn-primary btnSearch" id ="btnSearchAll" name="btnSearchAll" data-type="">All Article</button>
                      </div>
                         <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">{{ $val->name }}</button>
                         @can('article-create')
                        <a href="{{ route('article.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                        @endcan
                    </div>
                </div>--}}
                <hr>
                
                <div class="form-row">
  <div class="col-12">
    <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch">Search</button>
    <button type="button" class="btn btn-outline-danger" id="btnCheckAnomaly">
      <i data-feather="alert-triangle"></i> Abnormality
    </button>
    @if(Auth::user()->hasAnyRole(['Superuser', 'accounting']))
    <button type="button" class="btn btn-outline-warning" id="btnRecalculateLedger">
      <i data-feather="refresh-cw"></i> Recalculate
    </button>
    @endif
  </div>
</div>
            </form>
          </div>
        </div>
      </div>
    </div>
</section>

<!-- Anomaly Result Section -->
<section id="anomaly-result" style="display:none;">
  <div class="card border-danger">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="card-title mb-0">
        <i data-feather="alert-triangle" class="text-danger mr-50"></i>
        Hasil Analisa Abnormality Stock
        <span class="badge badge-danger ml-1" id="anomalyCount">0</span>
      </h4>
      <div>
        <small class="text-muted mr-1" id="anomalyLastCheck"></small>
        <button type="button" class="btn btn-sm btn-outline-success" id="btnExportAnomaly">
          <i data-feather="download"></i> Export
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCloseAnomaly">
          <i data-feather="x"></i>
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="anomalyTable" class="table table-sm table-striped">
          <thead class="thead-light">
            <tr>
              <th>Article</th>
              <th>Location</th>
              <th class="text-right">Qty Movement</th>
              <th class="text-right">Qty Stock</th>
              <th class="text-right">Variance</th>
              <th>Detected At</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div id="anomalyEmpty" class="text-center text-muted py-2" style="display:none;">
        Tidak ada abnormality ditemukan. Data stock konsisten.
      </div>
    </div>
  </div>
</section>

<section id="stock-summary">
  <div class="row">
    <div class="col-lg-3 col-6">
      <div class="card card-summary cursor-pointer mb-1" data-status="" id="cardTotal">
        <div class="card-body d-flex justify-content-between align-items-center py-1">
          <div>
            <h2 class="mb-0 font-weight-bolder" id="sumTotal">0</h2>
            <span>Total Article</span>
          </div>
          <div class="avatar bg-light-primary p-50 m-0"><i data-feather="box"></i></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-6">
      <div class="card card-summary cursor-pointer mb-1" data-status="save" id="cardSave">
        <div class="card-body d-flex justify-content-between align-items-center py-1">
          <div>
            <h2 class="mb-0 font-weight-bolder text-success" id="sumSave">0</h2>
            <span>Save</span>
          </div>
          <div class="avatar bg-light-success p-50 m-0"><i data-feather="check-circle"></i></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-6">
      <div class="card card-summary cursor-pointer mb-1" data-status="critical" id="cardCritical">
        <div class="card-body d-flex justify-content-between align-items-center py-1">
          <div>
            <h2 class="mb-0 font-weight-bolder text-danger" id="sumCritical">0</h2>
            <span>Critical</span>
          </div>
          <div class="avatar bg-light-danger p-50 m-0"><i data-feather="alert-triangle"></i></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-6">
      <div class="card card-summary cursor-pointer mb-1" data-status="empty" id="cardEmpty">
        <div class="card-body d-flex justify-content-between align-items-center py-1">
          <div>
            <h2 class="mb-0 font-weight-bolder text-warning" id="sumEmpty">0</h2>
            <span>Empty</span>
          </div>
          <div class="avatar bg-light-warning p-50 m-0"><i data-feather="slash"></i></div>
        </div>
      </div>
    </div>
  </div>
</section>
<section id="table-article">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title"> @yield('title') List</h4>
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
                  <table id="detailedTable" class="table">
                    <thead class="thead-light">
                    </thead>
                  </table>
                </div>
              </div>
          </div>  
        </div>
      </div>
    </div>
</section>
<!-- Modal movement-->
<div class="modal fade text-left bisa-geser" id="mdlmovement" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title d-flex align-items-center">
          <i data-feather="activity" class="mr-50"></i>
          Movement <span class="font-weight-bolder ml-50" id="mdlartikel"></span>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <!-- Filter panel -->
        <div class="mv-filter rounded p-1 mb-1">
          <label class="mb-2">Filter</label>
          <div class="form-row align-items-end">
            <div class="col-md-3 mb-1 mb-md-0">
              <label class="text-muted mb-25"><small>Date</small></label>
              <div class="input-group input-group-merge">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i data-feather="calendar"></i></span>
                </div>
                <input type="text" class="form-control" id="mvDateRange" placeholder="Pilih rentang tanggal">
              </div>
            </div>

            <div class="col-md-3 col-8 mb-1 mb-md-0">
              <label class="text-muted mb-25"><small>Transaction</small></label>
              <select class="form-control" id="mvInout">
                <option value="">Semua</option>
                <option value="in">IN (Masuk)</option>
                <option value="out">OUT (Keluar)</option>
              </select>
            </div>

            <div class="col-md-1 col-4">
              <button type="button" class="btn btn-outline-secondary btn-block" id="mvReset" title="Reset filter">
                <i data-feather="rotate-ccw"></i> Reset
              </button>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0" id="mdlmovetable"></table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Lot per Transaksi Movement (read-only) -->
<div class="modal fade" id="mdlLotByTransaction" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Lot di Transaksi Ini</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0" id="lotByTrxTable">
            <thead class="thead-light">
              <tr><th>Barcode / Lot</th><th class="text-right">Qty</th><th>Expired</th><th>Status</th></tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <div id="lotByTrxEmpty" class="text-center text-muted py-2" style="display:none;"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Detail Lot (chemical per-kaleng) -->
<div class="modal fade text-left" id="mdlChemicalLot" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title d-flex align-items-center">
          <i data-feather="tag" class="mr-50"></i>
          Detail Lot <span class="font-weight-bolder ml-50" id="lotArtikel"></span>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="lotNoLocation" class="alert alert-warning" style="display:none;">
          Pilih lokasi (bukan "All") di filter dulu, baru buka Detail Lot — lot dicatat per lokasi.
        </div>

        <div id="lotContent" style="display:none;">
          <div id="lotExpiredWarning" class="alert alert-danger" style="display:none;"></div>

          <div class="table-responsive mb-1">
            <table class="table table-sm table-striped mb-0" id="lotTable">
              <thead class="thead-light">
                <tr>
                  <th>Barcode / Lot</th>
                  <th class="text-right">Qty Awal</th>
                  <th class="text-right">Sisa</th>
                  <th>Expired</th>
                  <th>Status</th>
                  <th>Asal</th>
                  <th class="text-right">Aksi</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

          <div id="lotUncovered" class="alert alert-secondary d-flex justify-content-between align-items-center" style="display:none;">
            <span id="lotUncoveredText"></span>
            <button type="button" class="btn btn-sm btn-primary" id="btnLotGeneratePreview">
              <i data-feather="plus-circle"></i> Generate Barcode
            </button>
          </div>

          <div id="lotGenerateArea" style="display:none;">
            <hr>
            <h6>Atur Qty per Kaleng &amp; Expired Date (opsional)</h6>
            <div class="table-responsive mb-1">
              <table class="table table-sm mb-0" id="lotGenerateTable">
                <thead class="thead-light">
                  <tr>
                    <th>#</th>
                    <th class="text-right" style="width:140px;">Qty</th>
                    <th>Expired Date (opsional)</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <div class="mb-2">
              Total: <b id="lotGenerateTotal">0</b> / <span id="lotGenerateTarget">0</span>
              <span id="lotGenerateTotalWarn" class="text-danger ml-1" style="display:none;">
                <i data-feather="alert-triangle"></i> Total tidak sama dengan qty yang belum ber-barcode
              </span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLotGenerateCancel">Batal</button>
            <button type="button" class="btn btn-sm btn-success" id="btnLotGenerateSave">Simpan & Cetak Label</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="mdlAnalytics" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Analytics Dashboard</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p class="text-muted">Analytics dashboard akan ditambahkan di sini.</p>
      </div>
    </div>
  </div>
</div>

@include('partials.delete-modal')
@endsection
@section('styles')
<style>
  .card-summary { transition: .15s; }
.card-summary:hover { transform: translateY(-2px); }
.card-summary.active-card { box-shadow: 0 0 0 2px #7367f0; }
.cursor-pointer { cursor: pointer; }
.mv-filter {
  background: #f8f8f8;
  border: 1px solid #ebe9f1;
}
.mv-filter label { letter-spacing: .5px; }
.mv-filter .input-group-text { background: #fff; }
#mvReset i { width: 16px; height: 16px; }

#detailedTable_wrapper .dataTables_length { display: flex; align-items: center; }
#detailedTable_wrapper .dataTables_length .custom-switch { margin-bottom: 0; }
#detailedTable_wrapper .dataTables_length .custom-control-label { 
  cursor: pointer; 
  padding-top: 2px;
}

#mdlmovetable tbody tr.mv-summary > td {
    background: #fafbfc !important;
    border-top: 1px solid rgba(47,51,73,.08);
    border-bottom: 1px solid rgba(47,51,73,.08);
    padding-top: .75rem;
    padding-bottom: .75rem;
    color: #2f3349;
    font-weight: 600;
}
#mdlmovetable tbody tr.row-saldo-awal  > td { background: #f6f5ff !important; }
#mdlmovetable tbody tr.row-saldo-akhir > td { background: #f2fbf6 !important; }

/* garis aksen tipis di kiri, pengganti blok warna */
#mdlmovetable tbody tr.mv-summary > td:first-child { border-left: 3px solid transparent; }
#mdlmovetable tbody tr.row-saldo-awal  > td:first-child { border-left-color: #7367f0; }
#mdlmovetable tbody tr.row-saldo-akhir > td:first-child { border-left-color: #28c76f; }

.mv-summary-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    font-size: .72rem; font-weight: 700;
    letter-spacing: .06em; text-transform: uppercase;
    padding: .28rem .6rem; border-radius: 6px;
}
.mv-summary-badge svg { width: 13px; height: 13px; }
.row-saldo-awal  .mv-summary-badge { color:#5e50ee; background: rgba(115,103,240,.12); }
.row-saldo-akhir .mv-summary-badge { color:#1f9d57; background: rgba(40,199,111,.12); }

#mdlmovetable tbody tr.mv-summary .mv-balance { font-size: .95rem; font-weight: 700; }
</style>
@endsection
@section('scripts')
<script type="text/javascript">

  let name = $("#searchName");
  let code = $("#seachCode");
  let group = $("#searchGroup");
  let supp = $("#searchSupplier");
  let type = $("#searchType");
  let opr = $("#searchOperator");
  let qty = $("#searchQty");
  let searchStatus = $("#searchStatus");

  $(document).ready(function(){
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        let href = $(this).data('href');
        $('#modalConfirmation').attr("action", href);
    });
    mask_thousand_digit(numberOfDecimalDigit);

    let asofPicker = $('#searchAsof').flatpickr({
    dateFormat: 'd-m-Y',
    allowInput: true
});

    // ── auto-filter dari query string (mis. link dari halaman STO audit) ──
(function applyUrlFilters() {
    const params = new URLSearchParams(window.location.search);
    const urlCode         = params.get('code');       // alternative code (buat filter list & label)
    const urlRealCode     = params.get('real_code');  // article_code asli (buat movement)
    const urlLocation     = params.get('location');
    const urlOpenMovement = params.get('open_movement');
    const urlDateFrom     = params.get('date_from');
    const urlDateTo       = params.get('date_to');
    const urlDesc         = params.get('desc');

    let hasFilter = false;

    if (urlCode) {
        $('#seachCode').val(urlCode.toUpperCase());
        hasFilter = true;
    }

    if (urlLocation) {
        $('#searchLoc').val(urlLocation).trigger('change');
        hasFilter = true;
    }

    if (!hasFilter) return;

    setTimeout(function () {
        $('#btnSearch').click();

        if (urlOpenMovement === '1' && urlRealCode && urlLocation) {
            setTimeout(function () {
                movement(
                    urlRealCode.toUpperCase(),   // ← article_code ASLI, dipakai buat query movement
                    urlCode ? urlCode.toUpperCase() : urlRealCode.toUpperCase(), // alternative, buat label tampilan
                    urlDesc || '',
                    { from: urlDateFrom || null, to: urlDateTo || null }
                );
            }, 400);
        }
    }, 150);
})();
  });

 let hideEmptyState = false;   // <-- state persisten toggle

$('#detailedTable').on('init.dt', function () {
    var $len = $('#detailedTable_wrapper .dataTables_length');
    if (!$len.length) return;
    if ($len.find('#toggleHideEmpty').length) return; // udah ada, skip

    var toggleHtml =
      '<span class="d-inline-flex align-items-center ml-2">' +
        '<div class="custom-control custom-switch">' +
          '<input type="checkbox" class="custom-control-input" id="toggleHideEmpty">' +
          '<label class="custom-control-label" for="toggleHideEmpty">Hide Qty 0</label>' +
        '</div>' +
      '</span>';
    $len.append(toggleHtml);

    $('#toggleHideEmpty').prop('checked', hideEmptyState);
});

$(document).on('change', '#toggleHideEmpty', function () {
    hideEmptyState = $(this).is(':checked');   // simpan dulu ke variable
    $('#btnSearch').click();
});

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
    // showList(name.val(),code.val(),group.val(),supp.val(),type.val(),opr.val(),qty.val());
  });


  opr.change(function(e){
    qty.focus();
  });

  const getFilters = () => ({
    name: name.val(),
    code: code.val(),
    group: $("#searchGroup").val() || '',
    supp: supp.val(),
    type: type.val(),
    opr: opr.val(),
    qty: qty.val(),
    status: searchStatus.val(),
    location: $("#searchLoc").val(),
    hideEmptyQty: $('#toggleHideEmpty').is(':checked') ? 1 : 0,
    asof: $('#searchAsof').val()          // <-- BARU
});

const loadSummary = (f) => {
    $.ajax({
        url: "{{ route('warehouse.article.summary') }}",
        type: "GET",
        data: f,
        success: function (res) {
            $("#sumTotal").text(Number(res.total).toLocaleString());
            $("#sumSave").text(Number(res.save).toLocaleString());
            $("#sumCritical").text(Number(res.critical).toLocaleString());
            $("#sumEmpty").text(Number(res.empty).toLocaleString());
        }
    });
};

$("#btnSearch").click(function (e) {
    e.preventDefault();
    const f = getFilters();

    // guard: as-of butuh minimal 1 filter
    if (f.asof) {
        const hasFilter = f.location || f.code || f.name || f.type || f.supp;
        if (!hasFilter) {
            Swal.fire('Perlu Filter',
                'Untuk lihat stock per tanggal, pilih minimal 1 dari filter berikut: Lokasi / Kode / Nama / Type / Supplier.',
                'warning');
            return;
        }
    }

    showList(f.name, f.code, f.group, f.supp, f.type, f.opr, f.qty, f.status, f.location, f.hideEmptyQty, f.asof);
    loadSummary(f);
});

// klik card -> set status filter -> jalankan search
$('.card-summary').on('click', function () {
    const status = $(this).data('status');           // '', 'save', 'critical', 'empty'
    $('#searchStatus').val(status).trigger('change'); // update select2
    $('.card-summary').removeClass('active-card');
    $(this).addClass('active-card');
    $('#btnSearch').click();
});

$('#btnAnalytics').on('click', function () {
    $('#mdlAnalytics').modal('show');
});

  const showList = (name,code,group,supp,type,opr,qty,status,location,hideEmptyQty,asof) => {
    if ($('#detailedTable tr').length > 0){
        let table = $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
        tableId:"detailedTable",
        route:"{{ route('warehouse.article.listv2') }}",
        kolom:{!! $kolom !!},
        arrColPrint:[1,2,3,4,5,6,7,8,9,10],
        columnDefs :[
            { width: '5%', targets: 0 },
            { className: 'text-right','targets': [6,8,9] },
        ],
        dataSearch:{
            name:name,
            code:code,
            group:group,
            supp:supp,
            type:type,
            opr:opr,
            qty:qty,
            status:status,
            location:location,
            hideEmptyQty: hideEmptyQty ?? (hideEmptyState ? 1 : 0),
            asof: asof || ''          // <-- BARU
        },
        orderColumn:[[ 1, 'asc' ],[ 2, 'asc' ]],
        excelFileName:'article_stock',
        drawCallback: function () {
            if (window.feather) feather.replace();
        }
    });
}

let curArt  = { code:'', altcode:'', desc:'' };
let mvPicker = null;

const movement = (artCode, artikelAlternativeCode, artDesc, dateRange) => {
    curArt = { code: artCode, altcode: artikelAlternativeCode, desc: artDesc };
    $('#mdlartikel').text(' | ' + artikelAlternativeCode + ' - ' + artDesc);

    $('#mvInout').val('');

    // parse string 'dd-mm-yyyy' -> Date object
    const parseDMY = (s) => {
        const [d, m, y] = s.split('-').map(Number);
        return new Date(y, m - 1, d);
    };

    let defaultRange;
    if (dateRange && dateRange.from && dateRange.to) {
        // range dari luar (mis. link dari halaman Audit STO)
        defaultRange = [parseDMY(dateRange.from), parseDMY(dateRange.to)];
    } else {
        // default lama: tgl 1 bulan berjalan s/d hari ini
        const today = new Date();
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        defaultRange = [firstDayOfMonth, today];
    }

    if (mvPicker) {
        mvPicker.setDate(defaultRange, false);
    } else {
        mvPicker = $('#mvDateRange').flatpickr({
            mode: 'range',
            dateFormat: 'd-m-Y',
            defaultDate: defaultRange,
            onClose: function (sel) {
                if (sel.length === 0 || sel.length === 2) loadMovement();
            }
        });
    }
    $('#mvInout').off('change.mv').on('change.mv', loadMovement);
    $('#mvReset').off('click.mv').on('click.mv', function () {
        const t = new Date();
        const f = new Date(t.getFullYear(), t.getMonth(), 1);
        if (mvPicker) mvPicker.setDate([f, t], false);
        $('#mvInout').val('');
        loadMovement();
    });

    $('#mdlmovement').modal('show');
    loadMovement();
};

/**
 * Merge baris SALDO AWAL (OPENING) & SALDO AKHIR (CLOSING):
 * - gabungkan kolom Date..QTY jadi satu cell label (colspan)
 * - angka tetap sejajar di kolom Balance
 * - highlight barisnya karena ini special case
 *
 * Deteksi via teks kolom TYPE (movement_type = 'OPENING'/'CLOSING'),
 * jadi tidak tergantung row().data() yang mungkin tidak tersedia dari helper.
 */
function mergeMovementSummaryRows() {
    var $t = $('#mdlmovetable');
    if (!$.fn.DataTable.isDataTable($t)) return;
    var table = $t.DataTable();
    var stopIdx = table.column('movement_transnno:name').index(); // merge berhenti sebelum kolom Ref
    if (stopIdx === undefined) return;

    $t.find('tbody > tr').each(function () {
        var $tr = $(this);
        if ($tr.hasClass('mv-merged')) return;
        var d = table.row(this).data();
        if (!d || !d.is_summary) return;

       $tr.addClass('mv-merged mv-summary')
   .addClass(d.movement_type === 'OPENING' ? 'row-saldo-awal' : 'row-saldo-akhir');

        var $cells = $tr.children('td').filter(function () {
            var ci = table.cell(this).index();
            return ci && ci.column < stopIdx;
        });
        if ($cells.length) {
            var $first = $cells.first();
            $cells.slice(1).remove();
           $first.attr('colspan', $cells.length)
      .addClass('text-left')
      .html(d.summary_label || '');
        }
    });
    if (window.feather) feather.replace();
}
$('#mdlmovetable').off('draw.dt.mvmerge').on('draw.dt.mvmerge', mergeMovementSummaryRows);

const loadMovement = () => {
    if ($('#mdlmovetable tr').length > 0){
        let table = $('#mdlmovetable').DataTable();
        table.destroy();
        $('#mdlmovetable tbody > tr').remove();
        $("#mdlmovetable thead > tr").remove();
    }

    // baca rentang tanggal dari flatpickr
    const sel = mvPicker ? mvPicker.selectedDates : [];
    const fmt = (d) => mvPicker.formatDate(d, 'd-m-Y');
    const fromDate = sel.length ? fmt(sel[0]) : '';
    const toDate   = sel.length === 2 ? fmt(sel[1]) : (sel.length === 1 ? fmt(sel[0]) : '');

    const isGlobal = !$("#searchLoc").val();
    const kolomMovement = isGlobal
        ? {!! $kolomMovementGlobal !!}                 // tanpa location_number / from / to
        : {!! $kolomMovement !!};
    const rightCols = ['qty','balanceqty','last_qty'];
    const rightTargets = kolomMovement
        .map((c, i) => rightCols.includes(c.data) ? i : -1)
        .filter(i => i >= 0);

    // index kolom untuk merge baris SALDO AWAL/AKHIR
    const typeIdx = kolomMovement.findIndex(c => c.data === 'movement_type');
    const qtyIdx  = kolomMovement.findIndex(c => c.data === 'qty');
    const balIdx  = kolomMovement.findIndex(c => c.data === 'balanceqty');

    showDataTables({
        tableId:"mdlmovetable",
        route:"{{ route('article.movement2') }}",
        kolom: kolomMovement,
        arrColPrint: kolomMovement.map((_, i) => i),
        columnDefs :[
            { width: '5%', targets: 0 },
            { className: 'text-right', targets: rightTargets },
        ],
        dataSearch: {
            articleCode: curArt.code,
            location:    $("#searchLoc").val(),
            fromDate:    fromDate,
            toDate:      toDate,
            inout:       $('#mvInout').val()
        },
        orderColumn: [[ kolomMovement.length - 1, 'desc' ]],
        scrollY:300,
        excelFileName: 'Movement_' + curArt.altcode + '_' + curArt.desc,
        lengthMenu: [
            [ -1, 10, 25, 50 ],
            [ 'all', '10', '25', '50']
        ],
        drawCallback: function () {                     // ikon IN/OUT (feather) muncul
            if (window.feather) feather.replace();
        },
        initComplete: function(settings, json) {
            var api = this.api();
            $(api.settings()[0].nScrollBody).scrollTop(api.settings()[0].nScrollBody.scrollHeight);
        }
    });

    // Pasang merge lewat event draw.dt langsung ke tabel (independen dari showDataTables).
    // Namespace .mvmerge supaya bisa di-rebind tiap loadMovement tanpa dobel.
    $('#mdlmovetable')
        .off('draw.dt.mvmerge')
        .on('draw.dt.mvmerge', function () {
            mergeMovementSummaryRows(typeIdx, qtyIdx, balIdx);
        });

    // jaga-jaga kalau draw pertama sudah terjadi sebelum handler terpasang
    setTimeout(function () {
        mergeMovementSummaryRows(typeIdx, qtyIdx, balIdx);
    }, 300);
};

$('#btnCheckAnomaly').on('click', function () {
    let btn = $(this);
    const f = getFilters(); // sudah ada di file kamu — name, code, type, supp, location, dll

    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');

    $.ajax({
        url: "{{ route('stock.anomaly.check') }}",
        method: "POST",
        data: {
            threshold: 0.01,
            location: f.location,
            code: f.code,
            name: f.name,
            type: f.type,
            supp: f.supp
        },
        success: function (res) {
            renderAnomalyResult(res);
        },
        error: function () {
            Swal.fire('Error', 'Gagal menjalankan pengecekan abnormality.', 'error');
        },
        complete: function () {
            btn.prop('disabled', false).html('<i data-feather="alert-triangle"></i> Abnormality');
            if (window.feather) feather.replace();
        }
    });
});

$('#btnRecalculateLedger').on('click', function () {
    const locOptions = $('#searchLoc').html(); // reuse daftar lokasi yang sudah ada di filter

    Swal.fire({
        title: 'Recalculate Ledger',
        html:
            '<label class="d-block text-left mb-1">Location</label>' +
            '<select id="swalRecalcLocation" class="form-control mb-2" style="width:100%">' + locOptions + '</select>' +
            '<label class="d-block text-left mb-1">Article</label>' +
            '<select id="swalRecalcArticle" class="form-control" style="width:100%"><option value="">All</option></select>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Jalankan',
        cancelButtonText: 'Batal',
        didOpen: function () {
            const $popup = $(Swal.getPopup());
            const $loc = $popup.find('#swalRecalcLocation');
            const $art = $popup.find('#swalRecalcArticle');

            $loc.select2({ dropdownParent: $popup, width: '100%' });
            $art.select2({ dropdownParent: $popup, width: '100%' }).select2('enable', false);

            $loc.on('change', function () {
                const loc = $(this).val();
                $art.html('<option value="">All</option>').trigger('change');
                $art.select2('enable', false);
                if (!loc) return;

                $.get("{{ route('stock.ledger.articlesByLocation') }}", { location: loc }, function (rows) {
                    rows.forEach(function (r) {
                        $art.append('<option value="' + r.article_code + '">' + (r.article_alternative_code || r.article_code) + ' - ' + (r.article_desc || '') + '</option>');
                    });
                    $art.select2('enable', true);
                });
            });
        },
        preConfirm: function () {
            return {
                article: $('#swalRecalcArticle').val(),
                location: $('#swalRecalcLocation').val()
            };
        }
    }).then(function (result) {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Memproses...',
            html: '<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width:100%"></div></div>',
            allowOutsideClick: false,
            showConfirmButton: false
        });

        $.ajax({
            url: "{{ route('stock.ledger.recalculate') }}",
            method: "POST",
            data: {
                article: result.value.article,
                location: result.value.location,
                _token: "{{ csrf_token() }}"
            },
            success: function (res) {
                Swal.fire('Selesai', '<pre style="text-align:left;max-height:300px;overflow:auto">' + (res.output || '') + '</pre>', res.success ? 'success' : 'error');
            },
            error: function () {
                Swal.fire('Error', 'Gagal menjalankan recalculate.', 'error');
            }
        });
    });
});

function renderAnomalyResult(res) {
    const data = res.data || [];
    const $tbody = $('#anomalyTable tbody');
    $tbody.empty();

    $('#anomalyCount').text(data.length);
    $('#anomalyLastCheck').text('Terakhir dicek: ' + (res.checked_at || '-'));

    if (data.length === 0) {
        $('#anomalyTable').hide();
        $('#anomalyEmpty').show();
    } else {
        $('#anomalyTable').show();
        $('#anomalyEmpty').hide();
       data.forEach(function (row) {
    const diffClass = row.diff > 0 ? 'text-success' : 'text-danger';
    const articleLabel = row.article_alternative_code
        ? `${row.article_alternative_code} - ${row.article_desc ?? ''}`
        : row.article_id;
    const locationLabel = row.location_name || row.location_number;

    $tbody.append(`
        <tr>
            <td>${articleLabel}</td>
            <td>${locationLabel}</td>
            <td class="text-right">${Number(row.qty_ledger).toLocaleString()}</td>
            <td class="text-right">${Number(row.qty_snapshot).toLocaleString()}</td>
            <td class="text-right ${diffClass} font-weight-bold">${Number(row.diff).toLocaleString()}</td>
            <td>${row.detected_at}</td>
        </tr>
    `);
});
    }

    $('#anomaly-result').slideDown();
    if (window.feather) feather.replace();
}

$('#btnCloseAnomaly').on('click', function () {
    $('#anomaly-result').slideUp();
});

$('#btnExportAnomaly').on('click', function () {
    window.location.href = "{{ route('stock.anomaly.export') }}";
});

// ================= Lot per transaksi (dari modal Movement) =================
const lotByTransaction = (movementType, movementTransnno, articleCode) => {
    $('#mdlLotByTransaction').modal('show');
    const $tbody = $('#lotByTrxTable tbody');
    $tbody.empty();
    $('#lotByTrxEmpty').hide();

    $.ajax({
        url: "{{ route('stock.chemicalLot.byTransaction') }}",
        type: 'POST',
        data: { movement_type: movementType, movement_transnno: movementTransnno, article_code: articleCode },
        success: function (res) {
            if (res.status !== 1) {
                $('#lotByTrxEmpty').text(res.message).show();
                return;
            }
            res.lots.forEach(function (l) {
                $tbody.append(
                    '<tr>' +
                        '<td>' + l.barcode_code + '</td>' +
                        '<td class="text-right">' + Number(l.remaining_qty ?? l.qty).toLocaleString() + '</td>' +
                        '<td>' + (l.expired_date || '-') + '</td>' +
                        '<td>' + l.status + '</td>' +
                    '</tr>'
                );
            });
        },
        error: function () {
            $('#lotByTrxEmpty').text('Gagal memuat data lot.').show();
        }
    });
};

// ================= Detail Lot (chemical per-kaleng) =================
let curLot = { articleCode: '', location: '' };

const chemicalLotDetail = (artCode, artikelAlternativeCode, artDesc) => {
    const location = $('#searchLoc').val();
    $('#lotArtikel').text(' | ' + artikelAlternativeCode + ' - ' + artDesc);
    $('#lotGenerateArea').hide();
    $('#mdlChemicalLot').modal('show');

    if (!location) {
        $('#lotNoLocation').show();
        $('#lotContent').hide();
        return;
    }
    $('#lotNoLocation').hide();
    $('#lotContent').show();

    curLot = { articleCode: artCode, location: location };
    loadChemicalLotDetail();
};

const loadChemicalLotDetail = () => {
    $.ajax({
        url: "{{ route('stock.chemicalLot.detail') }}",
        type: 'POST',
        data: { article_code: curLot.articleCode, location_number: curLot.location },
        success: function (res) {
            if (res.status !== 1) {
                Swal.fire('Info', res.message, 'info');
                return;
            }
            renderLotTable(res.lots);

            if (res.expired_lots && res.expired_lots.length) {
                $('#lotExpiredWarning')
                    .html('<i data-feather="alert-triangle"></i> ' + res.expired_lots.length +
                        ' lot sudah EXPIRED tapi masih di lokasi ini — perlu ditransfer ke Chemical Expired.')
                    .show();
            } else {
                $('#lotExpiredWarning').hide();
            }

            if (res.uncovered_qty > 0) {
                $('#lotUncoveredText').text(
                    Number(res.uncovered_qty).toLocaleString() + ' qty di lokasi ini belum ada barcode-nya.'
                );
                $('#lotUncovered').show();
            } else {
                $('#lotUncovered').hide();
            }

            if (window.feather) feather.replace();
        },
        error: function () {
            Swal.fire('Error', 'Gagal memuat detail lot.', 'error');
        }
    });
};

function renderLotTable(lots) {
    const $tbody = $('#lotTable tbody');
    $tbody.empty();

    if (!lots.length) {
        $tbody.append('<tr><td colspan="7" class="text-center text-muted">Belum ada kaleng tercatat.</td></tr>');
        return;
    }

    const statusBadge = {
        active: '<span class="badge badge-light-success">Active</span>',
        out_at_workstation: '<span class="badge badge-light-warning">Di Workstation</span>',
        empty: '<span class="badge badge-light-secondary">Habis</span>',
    };

    lots.forEach(function (l) {
        const remaining = l.remaining_qty ?? l.qty;
        const canMarkEmpty = l.status === 'active' || l.status === 'out_at_workstation';
        let actionBtn = '<button type="button" class="btn btn-sm btn-outline-secondary btn-lot-reprint" data-id="' + l.id + '" title="Cetak ulang label">'
            + '<i data-feather="printer"></i></button>';
        if (canMarkEmpty) {
            actionBtn += ' <button type="button" class="btn btn-sm btn-outline-danger btn-lot-empty" data-id="' + l.id + '">Tandai Habis</button>';
        }

        $tbody.append(
            '<tr>' +
                '<td>' + l.barcode_code + '</td>' +
                '<td class="text-right">' + Number(l.qty).toLocaleString() + '</td>' +
                '<td class="text-right">' + Number(remaining).toLocaleString() + '</td>' +
                '<td>' + (l.expired_date || '-') + '</td>' +
                '<td>' + (statusBadge[l.status] || l.status) + '</td>' +
                '<td>' + (l.source === 'stock_adjustment' ? 'Stock' : 'Receiving') + '</td>' +
                '<td class="text-right">' + actionBtn + '</td>' +
            '</tr>'
        );
    });
    if (window.feather) feather.replace();
}

$(document).on('click', '.btn-lot-reprint', function () {
    const id = $(this).data('id');
    const $btn = $(this).prop('disabled', true);
    $.ajax({
        url: "{{ route('receiving.reprintChemicalUnitLabel') }}",
        type: 'POST',
        data: { unit_ids: [id] },
        success: function (res) {
            $btn.prop('disabled', false);
            if (res.status === 1) {
                promptChemicalPrintMethod(res);
            } else {
                Swal.fire('Gagal Print', res.message, 'warning');
            }
        },
        error: function () {
            $btn.prop('disabled', false);
            Swal.fire('Error Print', 'Gagal generate label.', 'error');
        }
    });
});

$(document).on('click', '.btn-lot-empty', function () {
    const id = $(this).data('id');
    Swal.fire({
        title: 'Tandai kaleng ini habis?',
        text: 'Barcode-nya tidak bisa dipakai lagi setelah ini.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Tandai Habis',
        cancelButtonText: 'Batal'
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ route('stock.chemicalLot.markEmpty') }}",
            type: 'POST',
            data: { ids: [id] },
            success: function (res) {
                if (res.status === 1) {
                    loadChemicalLotDetail();
                } else {
                    Swal.fire('Gagal', res.message, 'warning');
                }
            }
        });
    });
});

let lotGenTarget = 0;

const recalcLotGenerateTotal = () => {
    let total = 0;
    $('.lot-gen-qty').each(function () { total += Number($(this).val()) || 0; });
    total = Math.round(total * 1000) / 1000;
    $('#lotGenerateTotal').text(total.toLocaleString());

    // qty boleh diisi sebagian (kaleng sisa biar nanti digenerate belakangan),
    // yang tidak boleh cuma kalau total melebihi qty yang belum ber-barcode.
    const over = total - lotGenTarget > 0.001;
    $('#lotGenerateTotalWarn')
        .toggleClass('text-danger', over)
        .toggleClass('text-muted', !over)
        .html(over
            ? '<i data-feather="alert-triangle"></i> Total melebihi qty yang belum ber-barcode'
            : (total < lotGenTarget - 0.001
                ? '<i data-feather="info"></i> Sisa ' + (lotGenTarget - total).toLocaleString() + ' tetap belum ber-barcode, bisa digenerate lagi nanti'
                : ''))
        .toggle(true);
    if (window.feather) feather.replace();
    $('#btnLotGenerateSave').prop('disabled', over);
};

$('#btnLotGeneratePreview').on('click', function () {
    $.ajax({
        url: "{{ route('stock.chemicalLot.generatePreview') }}",
        type: 'POST',
        data: { article_code: curLot.articleCode, location_number: curLot.location },
        success: function (res) {
            if (res.status !== 1) {
                Swal.fire('Info', res.message, 'warning');
                return;
            }
            lotGenTarget = Number(res.uncovered_qty) || 0;
            $('#lotGenerateTarget').text(lotGenTarget.toLocaleString());
            const $tbody = $('#lotGenerateTable tbody');
            $tbody.empty();
            res.rows.forEach(function (r) {
                $tbody.append(
                    '<tr>' +
                        '<td>' + r.unit_sequence + '</td>' +
                        '<td class="text-right"><input type="number" step="0.001" min="0.001" class="form-control form-control-sm text-right lot-gen-qty" data-seq="' + r.unit_sequence + '" value="' + r.qty + '"></td>' +
                        '<td><input type="date" class="form-control form-control-sm lot-gen-exp" data-seq="' + r.unit_sequence + '"></td>' +
                    '</tr>'
                );
            });
            recalcLotGenerateTotal();
            $('#lotGenerateArea').show();
        }
    });
});

$(document).on('input', '.lot-gen-qty', recalcLotGenerateTotal);

$('#btnLotGenerateCancel').on('click', function () {
    $('#lotGenerateArea').hide();
});

$('#btnLotGenerateSave').on('click', function () {
    // Expired date opsional di sini (stock lama sering sudah tidak diketahui
    // expired-nya) -- kaleng tanpa expired date tetap bisa di-extend belakangan.
    // Baris yang qty-nya dikosongkan/0 dianggap belum mau digenerate sekarang
    // (sisa-nya tetap "belum ber-barcode", bisa digenerate lagi belakangan).
    const rows = [];
    $('.lot-gen-qty').each(function () {
        const qty = Number($(this).val()) || 0;
        if (qty <= 0) return;
        const seq = $(this).data('seq');
        rows.push({
            unit_sequence: seq,
            qty: qty,
            expired_date: $('.lot-gen-exp[data-seq="' + seq + '"]').val() || null
        });
    });

    if (!rows.length) {
        Swal.fire('Info', 'Isi qty minimal untuk satu kaleng.', 'info');
        return;
    }

    const total = Math.round(rows.reduce((s, r) => s + r.qty, 0) * 1000) / 1000;
    if (total - lotGenTarget > 0.001) {
        Swal.fire('Total Melebihi', 'Total qty kaleng tidak boleh melebihi qty yang belum ber-barcode (' + lotGenTarget.toLocaleString() + ').', 'warning');
        return;
    }

    const $btn = $(this).prop('disabled', true);
    $.ajax({
        url: "{{ route('stock.chemicalLot.generateStore') }}",
        type: 'POST',
        data: {
            article_code: curLot.articleCode,
            location_number: curLot.location,
            rows: JSON.stringify(rows)
        },
        success: function (res) {
            $btn.prop('disabled', false);
            if (res.status !== 1) {
                Swal.fire('Gagal', res.message, 'warning');
                return;
            }
            $('#lotGenerateArea').hide();
            loadChemicalLotDetail();

            const unitIds = (res.units || []).map(u => u.id);
            if (!unitIds.length) return;

            $.ajax({
                url: "{{ route('receiving.printChemicalUnitLabel') }}",
                type: 'POST',
                data: { unit_ids: unitIds },
                success: function (printRes) {
                    if (printRes.status === 1) promptChemicalPrintMethod(printRes);
                }
            });
        },
        error: function () {
            $btn.prop('disabled', false);
            Swal.fire('Error', 'Gagal menyimpan.', 'error');
        }
    });
});

// Pilih metode cetak: Browser (default, jalur yang sudah ada & selalu jalan)
// atau Network IP (kirim ZPL native langsung ke printer -- lebih tajam, tapi
// opsional: kalau gagal/IP salah, browser print tetap tersedia sebagai fallback).
function promptChemicalPrintMethod(data) {
    var savedIp = localStorage.getItem('zebra_ip') || '';
    Swal.fire({
        title: 'Pilih Metode Cetak Label',
        html:
            '<div style="text-align:left;">' +
            '<button type="button" id="pmBrowserBtn" class="btn btn-outline-primary btn-block mb-2">🖨️ Browser Print (default)</button>' +
            '<button type="button" id="pmNetworkToggleBtn" class="btn btn-outline-secondary btn-block">🖧 Network IP (ZPL, lebih tajam di printer kecil)</button>' +
            '<div id="pmNetworkArea" style="display:none;margin-top:8px;">' +
            '<input type="text" id="pmIpInput" class="form-control form-control-sm mb-2" placeholder="192.168.1.xxx" value="' + savedIp + '">' +
            '<button type="button" id="pmSendNetworkBtn" class="btn btn-sm btn-success btn-block">Kirim ke Printer</button>' +
            '</div>' +
            '</div>',
        showConfirmButton: false,
        showCloseButton: true,
        didOpen: function () {
            $('#pmBrowserBtn').on('click', function () {
                Swal.close();
                doBrowserPrintChemical(data);
            });
            $('#pmNetworkToggleBtn').on('click', function () {
                $('#pmNetworkArea').slideToggle(150);
            });
            $('#pmSendNetworkBtn').on('click', function () {
                var ip = $('#pmIpInput').val().trim();
                if (!ip) return;
                Swal.close();
                doNetworkZplChemical(data, ip);
            });
        }
    });
}

function doNetworkZplChemical(data, ip) {
    var zpl = (data.labels || []).map(function (l) { return l.zpl; }).join('\n');
    if (!zpl) { Swal.fire('Error', 'ZPL tidak tersedia untuk label ini.', 'error'); return; }
    localStorage.setItem('zebra_ip', ip);
    $.ajax({
        url: "{{ route('article.printLabelNetwork') }}",
        type: 'POST',
        data: { _token: '{{ csrf_token() }}', zpl: zpl, ip: ip, port: 9100 },
        success: function (res) {
            if (res.status === 1) Swal.fire('Berhasil', res.message, 'success');
            else Swal.fire('Gagal Kirim', res.message, 'warning');
        },
        error: function () {
            Swal.fire('Error', 'Gagal mengirim ke printer.', 'error');
        }
    });
}

function doBrowserPrintChemical(data) {
    var labels    = data.labels;
    var printedBy = data.printed_by;
    var total     = labels.length;

    var labelsHtml = '';
    labels.forEach(function (lbl, i) {
        labelsHtml +=
            '<div class="label-card">' +
            '<div class="label-top">' +
            '<img class="label-qr" src="' + lbl.qr_url + '">' +
            '<div class="label-text">' +
            '<div class="label-altcode">' + lbl.alt_code + '</div>' +
            '<div class="label-lot">' + lbl.barcode_code + '</div>' +
            '<div class="label-desc">' + lbl.article_desc + '</div>' +
            '<div class="label-exp">EXP: ' + lbl.expired_date + '</div>' +
            '</div></div>' +
            '<div class="label-footer">Dicetak: ' + printedBy + ' &bull; ' + (i+1) + '/' + total + '</div>' +
            '</div>';
    });

    var html = '<!DOCTYPE html><html><head><meta charset="utf-8">' +
        '<title>Label Chemical Unit</title>' +
        '<style>' +
        '@page{margin:0;size:30mm 20mm;}' +
        '*{box-sizing:border-box;margin:0;padding:0;font-family:Arial,sans-serif;text-shadow:none;' +
            '-webkit-font-smoothing:none;font-smooth:never;text-rendering:optimizeSpeed;}' +
        'body{background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
        '.label-sheet{display:flex;flex-wrap:wrap;}' +
        '.label-card{width:30mm;height:20mm;border:0.3mm solid #000;padding:1mm 1.3mm;' +
            'display:flex;flex-direction:column;justify-content:space-between;' +
            'overflow:hidden;page-break-inside:avoid;}' +
        '.label-top{display:flex;align-items:center;gap:1.2mm;flex:1;min-height:0;overflow:hidden;}' +
        '.label-qr{width:13mm;height:13mm;flex-shrink:0;object-fit:contain;}' +
        '.label-text{overflow:visible;min-width:0;flex:1;display:flex;flex-direction:column;gap:0.3mm;}' +
        '.label-altcode{font-size:4.2pt;font-weight:900;color:#000;white-space:nowrap;overflow:visible;letter-spacing:-0.1pt;}' +
        '.label-lot{font-size:2.6pt;font-weight:700;color:#000;font-family:"Courier New",monospace;white-space:nowrap;overflow:visible;letter-spacing:-0.1pt;}' +
        '.label-desc{font-size:2.3pt;color:#000;font-weight:700;letter-spacing:-0.1pt;line-height:1.15;word-break:break-word;}' +
        '.label-exp{font-size:2.6pt;font-weight:700;font-style:italic;color:#000;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}' +
        '.label-footer{font-size:3.5pt;color:#555;border-top:0.2mm solid #999;padding-top:0.5mm;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}' +
        '</style></head><body>' +
        '<div class="label-sheet">' + labelsHtml + '</div>' +
        '<script>window.onload=function(){' +
            'var imgs=document.querySelectorAll("img"),loaded=0;' +
            'if(!imgs.length){window.print();return;}' +
            'imgs.forEach(function(img){' +
                'if(img.complete){loaded++;if(loaded===imgs.length)window.print();}' +
                'else{img.onload=img.onerror=function(){loaded++;if(loaded===imgs.length)window.print();};}' +
            '});' +
        '};' +
        '<\/script></body></html>';

    var w = window.open('', '_blank', 'width=600,height=400');
    if (!w) { Swal.fire('Popup Diblokir', 'Izinkan popup di browser Anda.', 'warning'); return; }
    w.document.write(html);
    w.document.close();
}

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

</script>
@endsection
