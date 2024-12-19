@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="article-index">
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
            <div class="form-group col-md-2">
              <label for="conversionNumber">Conversion number</label>
              <input type="text" id="conversionNumber" name="conversionNumber" class="form-control"  required/>
            </div>
            <div class="form-group col-md-3">
                <label for="conversionName">Forecasting Name</label>
                <input type="text" id="conversionName" name="conversionName" class="form-control"  required/>
            </div>
            <div class="form-group col-md-2">
              <label class="form-label" for="deliveryDate">Delivery Date</label>
              <input type="text" id="deliveryDate" name="deliveryDate" class="form-control flatpickr" placeholder="DD-MM-YYYY" />
            </div>
            <div class="form-group col-md-3">
                <label for="customerCode">Customer</label>
                <select class="select2 form-control" id="customerCode" name="customerCode">
                    <option value="">Choose Customer</option>
                    @foreach($customers as $val)
                    <option value="{{ $val->kode }}">{{ $val->nama }}</option>
                    @endforeach
                </select>
            </div>
          </div>
          <div class="form-row">
              <div class="col-12"> 
                  <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                  {{-- @can('conversion-create') --}}
                  <a href="{{ route('conversion.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                  {{-- @endcan --}}
              </div>
          </div>
        </form>
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
        <button type="button" class="btn btn-primary" id ="btnDetail" name="btnDetail" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data detail">Detail</button>
        <button type="button" class="btn btn-primary" id ="btnSummary" name="btnSummary" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data summary">Summary</button>
        <div class="row">
            <div class="col-sm-12">
              <div class="card-datatable table-responsive pt-0">
                <table id="detailedTable" >
                  <thead class="thead-light">
                  </thead>
                  <tbody>
                  </tbody>
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

td.wrapok {
    white-space:normal
}
</style>
@endsection
@section('scripts')
<script type="text/javascript">

  let deliveryDate = $('#deliveryDate');
  let btnSummary = document.querySelector('#btnSummary');
  let btnDetail = document.querySelector('#btnDetail');

  $(document).ready(function(){    
    btnSummary.style.display = "none";
    btnDetail.style.display = "none";
    let href;
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        href = $(this).data('href');
        $('#modalConfirmation').attr("action", href);
    });
  });

  $("#btnSearch").click(function(e){
      btnSummary.style.display = "none";
      btnDetail.style.display = "none";
      let customer = $('#customerCode').val();
      let conversionName = $('#conversionName').val();
      let deliveryDate1 = deliveryDate.val();
      let conversionNumber = $('#conversionNumber').val();
      showList(customer,conversionName,deliveryDate1,conversionNumber);
  });

  btnSummary.addEventListener("click", function(){
      btnSummary.style.display = "none";
      btnDetail.style.display = "none";
      let customer = $('#customerCode').val();
      let conversionName = $('#conversionName').val();
      let deliveryDate1 = deliveryDate.val();
      let conversionNumber = $('#conversionNumber').val();
      showList(customer,conversionName,deliveryDate1,conversionNumber);
  });

  btnDetail.addEventListener("click", function(){
      btnSummary.style.display = "none";
      btnDetail.style.display = "none";
      let customer = $('#customerCode').val();
      let conversionName = $('#conversionName').val();
      let deliveryDate1 = deliveryDate.val();
      let conversionNumber = $('#conversionNumber').val();
      showListDetail(customer,conversionName,deliveryDate1,conversionNumber);
  });

  if (deliveryDate.length) {
    deliveryDate.flatpickr({
          dateFormat: "d-m-Y",
          // maxDate: "today"
      });
  }

  const showList = (customer,conversionName,deliveryDate1,conversionNumber) => {  
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('conversion.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6],
      columnDefs :[
        { width: '5%', targets: 0 },
        // {
        //   targets: [ 8,9,19,20,21,22,23,24,25 ],
        //   render: $.fn.dataTable.render.number(',', '.', 2, ''),
        //   className: "text-right"
        // },
        // {
        //   targets: [ 11 ],
        //   render: function ( data, type, full, meta ) {
        //     return '\u200C'+data;
        //   }
        // }
      ],
      type:"POST",
      // excelCustomize:function(xlsx) {
      //   let sheet = xlsx.xl.worksheets['sheet1.xml'];
      //   $('row:last c', sheet).attr('s','50');
      // },
      // excelMessageBottom:function () { return "Tanggal export : "+currentDate },
      dataSearch:  {
        customer:customer,
        conversionName:conversionName,
        deliveryDate:deliveryDate1,
        conversionNumber:conversionNumber
      },
      initComplete: function(settings, json) {
        btnDetail.style.display = "block";
        btnSummary.style.display = "none";
      },
      orderColumn:[[ 6, 'desc' ],[ 1, 'asc' ]],
      excelFileName:'conversion_sales',
    });
  }

  const showListDetail = (customer,conversionName,deliveryDate1,conversionNumber) => {  
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('conversion.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[1,2,3,4,5,6,7],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
          targets: [ 6 ],
          render: $.fn.dataTable.render.number(',', '.', 10, ''),
          className: "text-right"
        },
        // {
        //   targets: [ 11 ],
        //   render: function ( data, type, full, meta ) {
        //     return '\u200C'+data;
        //   }
        // }
      ],
      type:"POST",
      // excelCustomize:function(xlsx) {
      //   let sheet = xlsx.xl.worksheets['sheet1.xml'];
      //   $('row:last c', sheet).attr('s','50');
      // },
      // excelMessageBottom:function () { return "Tanggal export : "+currentDate },
      dataSearch:  {
        customer:customer,
        conversionName:conversionName,
        deliveryDate:deliveryDate1,
        conversionNumber:conversionNumber
      },
      initComplete: function(settings, json) {
        btnDetail.style.display = "none";
        btnSummary.style.display = "block";
      },
      orderColumn:[[ 8, 'desc' ],[ 0, 'asc' ]],
      excelFileName:'conversion_sales_detail',
    });
  }

  $('body').tooltip({
    selector: '[data-toggle="tooltip"]'
  });
  
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
