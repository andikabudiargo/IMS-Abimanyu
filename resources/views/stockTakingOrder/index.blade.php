@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
  $statusSto = ['1'=>'SCHEDULED','2'=>'ONGOING','3'=>'COMPLETED','5'=>'CANCELED'];
@endphp

{{-- ════════════════════════════════════════════════
     BANNER STATUS STO AKTIF
     Ambil dari sto_config (header), bukan mapping.
     STO Date dicek per-baris mapping (bisa beda-beda tanggal).
════════════════════════════════════════════════ --}}
@php
    $today = date('d-m-Y');

    $activeSto = DB::table('sto_config')
        ->whereIn('status', [1, 2])
        ->orderByDesc('config_id')
        ->first();

    // ambil ringkasan tanggal dari mapping (bisa beda2 per baris)
    $activeMappingDates = [];
    $isTodaySto = false;
    if ($activeSto) {
        $activeMappingDates = DB::table('sto_config_mapping')
            ->where('config_id', $activeSto->config_id)
            ->pluck('sto_date')
            ->unique()
            ->values();
        $isTodaySto = $activeMappingDates->contains($today);
    }
@endphp

@if($activeSto)
  @if($activeSto->status == 2)
  <div class="alert alert-primary d-flex align-items-center mb-1" style="border-left:4px solid #5a6acf;border-radius:6px;">
      <i data-feather="activity" class="mr-75" style="width:20px;height:20px;flex-shrink:0;"></i>
      <div>
          <strong>STO Berjalan:</strong>
          {{ $activeSto->sto_code }} &mdash; Periode <strong>{{ $activeSto->periode }}</strong>,
          Tanggal: <strong>{{ $activeMappingDates->implode(', ') ?: '-' }}</strong>.
          Target Plan <strong>{{ number_format($activeSto->target_plan, 2) }}%</strong>,
          Realisasi <strong>{{ number_format($activeSto->target_act, 2) }}%</strong>.
          @unless($isTodaySto)
          <span class="text-danger" style="font-size:.8rem;">&nbsp;(Hari ini bukan tanggal STO untuk target manapun — hanya Accounting yang bisa input.)</span>
          @endunless
      </div>
  </div>
  @else
  <div class="alert alert-info d-flex align-items-center mb-1" style="border-left:4px solid #1382a5;border-radius:6px;">
      <i data-feather="calendar" class="mr-75" style="width:20px;height:20px;flex-shrink:0;"></i>
      <div>
          <strong>STO Terjadwal:</strong>
          {{ $activeSto->sto_code }} &mdash; periode {{ $activeSto->periode }},
          tanggal: <strong>{{ $activeMappingDates->implode(', ') ?: '-' }}</strong>.
      </div>
  </div>
  @endif
@else
  <div class="alert alert-warning d-flex align-items-center mb-1" style="border-left:4px solid #d98a0b;border-radius:6px;">
      <i data-feather="alert-triangle" class="mr-75" style="width:20px;height:20px;flex-shrink:0;"></i>
      <div>
          <strong>Tidak ada STO aktif.</strong>
          Buat STO baru untuk menjadwalkan stock count.
      </div>
  </div>
@endif

{{-- ════════════════════════════════════════════════
     FILTER
════════════════════════════════════════════════ --}}
<section id="sto-index">
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
              <div class="form-group col-md-4">
                <label for="searchCode">STO Code</label>
                <input type="text" class="form-control text-uppercase" id="searchCode" name="searchCode" placeholder="STO-2026-VII-..." />
              </div>
              <div class="col-md-4 form-group">
                <label for="searchDate">STO Date</label>
                <input type="text" id="searchDate" name="searchDate" class="form-control flatpickr-range" placeholder="DD-MM-YYYY to DD-MM-YYYY" />
              </div>
              <div class="form-group col-md-4">
                <label class="form-label" for="searchStatus">Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index => $val)
                        <option value="{{ $index }}">{{ $index }} - {{ $val }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-4">
                <label class="form-label" for="searchPeriode">Periode</label>
                <input type="month" class="form-control" id="searchPeriode" name="searchPeriode" />
              </div>
            </div>
            <div class="form-row">
                <div class="col-12">
                    <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch">
                        <i data-feather="search" class="align-middle mr-sm-25 mr-0"></i>
                        <span class="align-middle d-sm-inline-block d-none">Search</span>
                    </button>
                   
                    <a href="{{ route('stockTakingOrder.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                   
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</section>

{{-- ════════════════════════════════════════════════
     TABLE
════════════════════════════════════════════════ --}}
<section id="table-sto">
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
        <button type="button" class="btn btn-primary d-none" id="btnDetail" name="btnDetail" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data detail per lokasi">Detail</button>
        <button type="button" class="btn btn-primary d-none" id="btnSummary" name="btnSummary" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data summary">Summary</button>
        <div class="row">
            <div class="col-sm-12">
              <div class="card-datatable table-responsive pt-0">
                <table id="detailedTable" class="table">
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

@section('styles')
<style>
</style>
@endsection

@section('scripts')
<script type="text/javascript">

  let searchCode    = document.querySelector("#searchCode");
  let searchStatus  = document.querySelector("#searchStatus");
  let searchPeriode = document.querySelector("#searchPeriode");
  let searchDate    = document.querySelector("#searchDate");
  let search        = document.querySelector('#btnSearch');
  let refresh       = document.querySelector('a[data-action="reload"]');
  let rangePickr    = document.querySelector('.flatpickr-range');
  let btnSummary    = $('#btnSummary');
  let btnDetail     = $('#btnDetail');

  initDatePicker(rangePickr, {
    minDate    : "01/01/2010",
    maxDate    : "31/12/2030",
    dateFormat : "d-m-Y",
    mode       : "range"
  });

  function dataSearch($type){
    btnSummary.addClass('d-none');
    btnDetail.addClass('d-none');
    $(".loading-spinner-container").addClass("-show");

    if ($type == 'detail'){
      showListDetail(searchCode.value, searchStatus.value, searchPeriode.value, searchDate.value);
    }
    if ($type == 'summary'){
      showList(searchCode.value, searchStatus.value, searchPeriode.value, searchDate.value);
    }
  }

  refresh.addEventListener("click", function(){ dataSearch('summary'); });
  btnDetail.click(function(){ dataSearch('detail'); });
  btnSummary.click(function(){ dataSearch('summary'); });

  $("#btnSearch").click(function(e){
    btnSummary.addClass('d-none');
    btnDetail.addClass('d-none');
    dataSearch('summary');
  });

  // ══════════ SUMMARY (header per config) ══════════
  const showList = (searchCode, searchStatus, searchPeriode, searchDate) => {
    if ($('#detailedTable tr').length > 0){
        let table = $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId       : "detailedTable",
      route         : "{{ route('stockTakingOrder.list') }}",
      kolom         : {!! $kolom !!},
      arrColPrint   : [1,2,3,4,5,6,7,8,9,10,11,12],
      columnDefs    : [
        { width: '5%', targets: 0 },
      ],
      dataSearch    : {
        searchCode    : searchCode,
        searchStatus  : searchStatus,
        searchPeriode : searchPeriode,
        searchDate    : searchDate,
      },
      initComplete  : function() {
        let api = this.api();
        if (api.data().length > 0) {
          btnDetail.removeClass('d-none');
          btnSummary.addClass('d-none');
        }
        $(".loading-spinner-container").removeClass("-show");
        if (typeof feather !== 'undefined') feather.replace();
      },
      orderColumn   : [[1, 'desc']],
      excelFileName : 'stock_taking_order'
    });
  }

  // ══════════ DETAIL (mapping per lokasi) ══════════
  const showListDetail = (searchCode, searchStatus, searchPeriode, searchDate) => {
    if ($('#detailedTable tr').length > 0){
        let table = $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId       : "detailedTable",
      route         : "{{ route('stockTakingOrder.list.detail') }}",
      kolom         : {!! $kolomDetail !!},
      arrColPrint   : [0,1,2,3,4,5,6,7,8,9],
      columnDefs    : [
        { className: 'text-right', targets: [5,6] },
      ],
      dataSearch    : {
        searchCode    : searchCode,
        searchStatus  : searchStatus,
        searchPeriode : searchPeriode,
        searchDate    : searchDate,
      },
      initComplete  : function() {
        let api = this.api();
        if (api.data().length > 0) {
          btnSummary.removeClass('d-none');
          btnDetail.addClass('d-none');
        }
        $(".loading-spinner-container").removeClass("-show");
        if (typeof feather !== 'undefined') feather.replace();
      },
      orderColumn   : [[0, 'asc'], [2, 'asc']],
      excelFileName : 'stock_taking_order_detail'
    });
  }

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

</script>
@endsection