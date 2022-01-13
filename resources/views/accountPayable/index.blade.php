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
              <div class="form-group col-md-3 d-none"> 
                <label for="searchRec">Rec Number</label>
                <input type="text" class="form-control text-uppercase" id="searchRec" name="searchRec" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchPo">PO Number</label>
                <input type="text" class="form-control text-uppercase" id="searchPo" name="searchPo" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchInv">Invoice Number</label>
                <input type="text" class="form-control text-uppercase" id="searchInv" name="searchInv" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchSupplier">Supplier</label>
                <select class="select2 form-control" id="searchSupplier" name="searchSupplier">
                    <option value="">All</option>
                    @foreach($supps as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-3 form-group">
                <label for="recDate">Date</label>
                <input type="text" id="recDate" name="recDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-2"> 
                <label class="form-label" for="searchStatus">Invoice Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}">{{ $index }} - {{ $val }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @can('receiving-create')
                    <a href="{{ route('ap.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                    @endcan
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
  $(document).ready(function(){    
    let href;
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        href = $(this).data('href');
        console.log(href);
        $('#modalConfirmationCancel').attr("action", href);
    });
  });

  let showAlert = "{{ Session::get('alert') }}";

  if ( showAlert ){
    showList();
    $("#alert-message-alert").fadeTo(5000, 500).slideUp(500, function(){
      $("#alert-message-alert").slideUp(500);
    });
  }

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
      showList();
  });

  rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

  $("#btnSearch").click(function(e){
    let searchRec = $("#searchRec").val();
    let searchPo = $("#searchPo").val();
    let searchInv = $("#searchInv").val();
    let searchSupplier = $("#searchSupplier").val(); 
    let searchStatus = $("#searchStatus").val();
    let recDate = $("#recDate").val();
    showList(searchRec,searchPo,searchInv,searchSupplier,searchStatus,recDate);

  });

  function showList(searchRec,searchPo,searchInv,searchSupplier,searchStatus,recDate){
    let dtdom ='<"d-flex justify-content-between align-items-center header-actions mx-1 row mt-75"<"col-lg-12 col-xl-6" l><"col-lg-12 col-xl-6 pl-xl-75 pl-0"<"dt-action-buttons text-xl-right text-lg-left text-md-right text-left d-flex align-items-center justify-content-lg-end align-items-center flex-sm-nowrap flex-wrap mr-1"<"mr-1"f>B>>>t<"d-flex justify-content-between mx-2 row mb-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>';
    let arr_col_print =[2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18]; 
    $(function(){
      let oTable =$("#detailedTable").DataTable({
        ajax:
        {
          url:'{{ route("ap.list")}}',
          data:{
              searchRec:searchRec,
              searchPo:searchPo,
              searchInv:searchInv,
              searchSupplier:searchSupplier,
              searchStatus:searchStatus,
              recDate:recDate
          }
        },
        processing: true,
        serverSide: true,
        buttons: true,
        dom:dtdom,
        lengthMenu: [
          [ 10, 25, 50, -1 ],
          [ '10', '25', '50', 'all' ]
        ],
        buttons: [
          {
            extend: 'collection',
            className: 'btn btn-outline-secondary dropdown-toggle mr-2 mt-07',
            text: feather.icons['share'].toSvg({ class: 'font-small-4 mr-50' }) + 'Export',
            buttons: [
              {
                extend: 'print',
                text: feather.icons['printer'].toSvg({ class: 'font-small-4 mr-50' }) + 'Print',
                className: 'dropdown-item',
                exportOptions: { columns: arr_col_print }
              },
              {
                extend: 'csv',
                text: feather.icons['file-text'].toSvg({ class: 'font-small-4 mr-50' }) + 'Csv',
                className: 'dropdown-item',
                exportOptions: { columns: arr_col_print }
              },
              {
                extend: 'excel',
                text: feather.icons['file'].toSvg({ class: 'font-small-4 mr-50' }) + 'Excel',
                className: 'dropdown-item',
                exportOptions: { columns: arr_col_print }
              },
              {
                extend: 'pdf',
                text: feather.icons['clipboard'].toSvg({ class: 'font-small-4 mr-50' }) + 'Pdf',
                className: 'dropdown-item',
                exportOptions: { columns: arr_col_print }
              },
              {
                extend: 'copy',
                text: feather.icons['copy'].toSvg({ class: 'font-small-4 mr-50' }) + 'Copy',
                className: 'dropdown-item',
                exportOptions: { columns: arr_col_print }
              }
            ],
            init: function (api, node, config) {
              $(node).removeClass('btn-secondary');
              $(node).parent().removeClass('btn-group');
              setTimeout(function () {
                $(node).closest('.dt-buttons').removeClass('btn-group').addClass('d-inline-flex');
              }, 50);
            }
          },
        ],
        language: {
          paginate: {
            // remove previous & next text from pagination
            previous: '&nbsp;',
            next: '&nbsp;'
          }
        },
        columnDefs: [
          { width: '10%', targets: 0 },
          { className: 'text-right','targets': [ 10,11,12,13,14 ] },
        ],
        drawCallback: function( settings ) {
          feather.replace({
                width: 14,
                height: 14
          });
        },
        order: [[ 1, 'asc' ]],
        bDestroy: true, //pakai ini supaya bisa di load berulang2
        // scrollX: true, //pakai ini supaya waktu responsive  bisa di scroll horizontal
        columns: [
            { data: 'action', name: 'action',title:'action', orderable: false, searchable: false },
            { data: 'ap_number', name: 'ap_number',title:'AP Number' },
            { data: 'num_revision', name: 'num_revision',title:'Rev.' },
            { data: 'inv_number', name: 'inv_number',title:'Invoice Number' },
            { data: 'proforma_inv_number', name: 'proforma_inv_number',title:'Proforma' },
            { data: 'tax_inv_number', name: 'tax_inv_number',title:'Tax Number' },
            { data: 'inv_date', name: 'inv_date',title:'Inv Date' },
            { data: 'supplier_id', name: 'supplier_id',title:'Supplier' },
            // { data: 'supp_name', name: 'supp_name',title:'Supplier' },
            { data: 'po_number', name: 'po_number',title:'PO Number' },
            { data: 'rec_number', name: 'rec_number',title:'Rec Number' },
            { data: 'rec_date', name: 'rec_date',title:'Rec Date' },
            { data: 'basis_amount', name: 'basis_amount',title:'Basis Amount',render: $.fn.dataTable.render.number(',','.') },
            { data: 'vat', name: 'vat',title:'VAT',render: $.fn.dataTable.render.number(',','.') },
            { data: 'pph23', name: 'pph23',title:'PPH23',render: $.fn.dataTable.render.number(',','.') },
            { data: 'other_deduction', name: 'other_deduction',title:'Other Deduction',render: $.fn.dataTable.render.number(',','.') },
            { data: 'total', name: 'total',title:'Total',render: $.fn.dataTable.render.number(',','.') },
            { data: 'prepared_by', name: 'prepared_by',title:'Prepared By' },
            { data: 'authorized_by', name: 'authorized_by',title:'Authorized By' },
            { data: 'status', name: 'status',title:'Status' },
        ],
      });
    });
    //$('div.head-label').html('<h6 class="mb-0">Data Users</h6>');    
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
