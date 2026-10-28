@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="purchase-index">
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
                <label for="searchTso">TSO Code</label>
                <input type="text" class="form-control text-uppercase" id="searchTso" name="searchTso" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchCustomer">Customer</label>
                <select class="select2 form-control" id="searchCustomer" name="searchCustomer">
                  <option value="">All</option>
                    @foreach($customer  as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-3 form-group">
                <label for="tsoDate">Date</label>
                <input type="text" id="tsoDate" name="tsoDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-3">
                <label class="form-label" for="searchStatus">Order Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}">{{ $index }} - {{ $val }}</option>
                    @endforeach
                </select>
              </div>
              <div class="form-group col-md-3">
                <label for="searchPeriode">Periode</label>
                <input type="month" class="form-control" id="searchPeriode" name="searchPeriode" />
              </div>
            </div>
            <div class="form-row">
                <div class="col-12">
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @can('targetSo-create')
                    <a href="{{ route('targetSo.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                    @endcan
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</section>
<section id="table-purchase">
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
        <button type="button" class="btn btn-primary" id ="btnDetail" name="btnDetail" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data detail">Detail</button>
        <button type="button" class="btn btn-primary" id ="btnSummary" name="btnSummary" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data summary">Summary</button>
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
@include('partials.delete-modal')
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">

  let searchTso = document.querySelector("#searchTso");
  let searchCustomer = document.querySelector("#searchCustomer");
  let searchStatus = document.querySelector("#searchStatus");
  let searchPeriode = document.querySelector("#searchPeriode");
  let tsoDate = document.querySelector("#tsoDate");
  let search = document.querySelector('#btnSearch');
  let refresh = document.querySelector('a[data-action="reload"]');
  let rangePickr = document.querySelector('.flatpickr-range');
  let btnSummary = document.querySelector('#btnSummary');
  let btnDetail = document.querySelector('#btnDetail');

  document.addEventListener("DOMContentLoaded", function(event) {
    btnSummary.style.display = "none";
    btnDetail.style.display = "none";
  });

  initDatePicker(rangePickr,{
    minDate: "01/01/2010",
    maxDate: "31/12/2030",
    dateFormat: "d-m-Y",
    mode: "range"
  });

  // if (rangePickr.length) {
  //   rangePickr.flatpickr({
  //     dateFormat: "d-m-Y",
  //     mode: 'range'
  //   });
  // }

  //refresh di cards
  refresh.addEventListener("click",function(){
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchTso.value,searchCustomer.value,searchStatus.value,searchPeriode.value,tsoDate.value);
  })

  search.addEventListener("click", function(){
    btnDetail.style.display = "block";
    btnSummary.style.display = "none";
    showList(searchTso.value,searchCustomer.value,searchStatus.value,searchPeriode.value,tsoDate.value);
  });

  btnSummary.addEventListener("click", function(){
    btnSummary.style.display = "none";
    btnDetail.style.display = "block";
    showList(searchTso.value,searchCustomer.value,searchStatus.value,searchPeriode.value,tsoDate.value);
  });

  btnDetail.addEventListener("click", function(){
    btnSummary.style.display = "block";
    btnDetail.style.display = "none";
    showListDetail(searchTso.value,searchCustomer.value,searchStatus.value,searchPeriode.value,tsoDate.value);
  });

  const showList = (searchTso,searchCustomer,searchStatus,searchPeriode,tsoDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    $('#detailedTable tfoot').remove();
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('targetSo.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        searchTso:searchTso,
        searchCustomer:searchCustomer,
        searchStatus:searchStatus,
        searchPeriode:searchPeriode,
        tsoDate:tsoDate
      },
      orderColumn:[[ 1, 'desc' ]],
      excelFileName:'target_sales_order'
    });
  }

  const showListDetail = (searchTso,searchCustomer,searchStatus,searchPeriode,tsoDate) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    $('#detailedTable tfoot').remove();
    let dataSearch = { searchTso, searchCustomer, searchStatus, searchPeriode, tsoDate };
    // kolom Qty R{n} jumlahnya ikut filter yang aktif, jadi ambil dulu sebelum render tabel
    $.get("{{ route('targetSo.list.detail.columns') }}", dataSearch, function(cols){
      $('#detailedTable').append('<tfoot><tr>' + cols.kolom.map(() => '<th></th>').join('') + '</tr></tfoot>');
      showDataTables({
        excelFooter:true,
        // totals dihitung server atas semua baris hasil filter, bukan cuma halaman aktif
        footerCallback:function(){
          let api = this.api(), totals = (api.ajax.json() || {}).totals || {};
          cols.kolom.forEach((k, i) => {
            let text = i === 0 ? 'TOTAL' : (k.data in totals ? Number(totals[k.data]).toLocaleString('en-US', {maximumFractionDigits: 2}) : '');
            $(api.column(i).footer()).addClass(i ? 'text-right' : '').html(text);
          });
        },
        tableId:"detailedTable",
        route:"{{ route('targetSo.list.detail') }}",
        kolom:cols.kolom,
        arrColPrint:cols.arrColPrint,
        columnDefs :[
          { width: '5%', targets: 0 },
          { className: 'text-right','targets': cols.textRight },
        ],
        dataSearch,
        orderColumn:[[ 2, 'asc' ]],
        excelFileName:'target_sales_order'
      });
    });
  }

  let href;
  $(document).on('click', '#revisionReasonButton', function(event) {
      event.preventDefault();
      href = $(this).data('href');
      $('#modalReasonRevision').attr("action", href);
  });
 
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
