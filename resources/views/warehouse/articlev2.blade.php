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
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
    {{--<button type="button" class="btn btn-outline-primary" id="btnAnalytics">
      <i data-feather="bar-chart-2"></i> Analytics
    </button>--}}
                      {{--<a href="#" class="btn btn-info"><i class="fa fa-download"></i> Downlod  All Stock</a>--}}
                  </div>
              </div>
            </form>
          </div>
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
    location: $("#searchLoc").val()
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
    showList(f.name, f.code, f.group, f.supp, f.type, f.opr, f.qty, f.status, f.location);
    loadSummary(f);   // <-- card ikut filter yang sama
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

  const showList = (name,code,group,supp,type,opr,qty,status,location) => {
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
            location:location      // <-- ikut dikirim ke controller
        },
        orderColumn:[[ 1, 'asc' ],[ 2, 'asc' ]],
        excelFileName:'article_stock'
    });
}

  let curArt  = { code:'', altcode:'', desc:'' };
let mvPicker = null;

const movement = (artCode, artikelAlternativeCode, artDesc) => {
    curArt = { code: artCode, altcode: artikelAlternativeCode, desc: artDesc };
    $('#mdlartikel').text(' | ' + artikelAlternativeCode + ' - ' + artDesc);

    // reset filter tiap modal dibuka
    $('#mvInout').val('');
    if (mvPicker) {
        mvPicker.clear();
    } else {
        mvPicker = $('#mvDateRange').flatpickr({
            mode: 'range',
            dateFormat: 'd-m-Y',                       // cocok dgn movement_date 'dd-mm-yyyy'
            onClose: function (sel) {
                if (sel.length === 0 || sel.length === 2) loadMovement();
            }
        });
    }
    $('#mvInout').off('change.mv').on('change.mv', loadMovement);
    $('#mvReset').off('click.mv').on('click.mv', function () {
        if (mvPicker) mvPicker.clear();
        $('#mvInout').val('');
        loadMovement();
    });

    $('#mdlmovement').modal('show');
    loadMovement();
};

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
        excelFileName:'movement' + curArt.desc,
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
};

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
