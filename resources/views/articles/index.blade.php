@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<section id="article-index">
    <div class="row">
      <div class="col-12">
        <div class="card">
          {{-- <div class="card-header">  
            <div class="card-title">@yield('title')
            </div>
          </div> --}}
          <div class="card-body">
            <form class="needs-validation" novalidate>
                <div class="form-row">
                    <div class="form-group col-md-4"> 
                      <label for="seachCode">Kode</label>
                      <input type="text" class="form-control text-uppercase" id="seachCode" name="seachCode" placeholder=""  />
                    </div>
                    <div class="form-group col-md-4"> 
                      <label for="searchName">Name</label>
                      <input type="text" class="form-control text-uppercase" id="searchName" name="searchName" placeholder="" />
                    </div>
                    <div class="form-group col-md-4"> 
                      <label class="form-label" for="searchGroup">Group</label>
                      <select class="select2 form-control" id="searchGroup" name="searchGroup">
                          <option value="">All</option>
                          @foreach($groups as $val)
                              <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                          @endforeach
                      </select>
                    </div>
                    <div class="form-group col-md-4"> 
                      <label class="form-label" for="searchCustomer">Customer</label>
                      <select class="select2 form-control" id="searchCustomer" name="searchCustomer">
                          <option value="">All</option>
                          @foreach($custs as $val)
                              <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                          @endforeach
                      </select>
                    </div>
                    <div class="form-group col-md-4"> 
                      <label class="form-label" for="searchSupplier">Supplier</label>
                      <select class="select2 form-control" id="searchSupplier" name="searchSupplier">
                          <option value="">All</option>
                          @foreach($supps as $val)
                              <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                          @endforeach
                      </select>
                    </div>
                    <div class="form-group col-md-4"> 
                      <label class="form-label" for="searchType">Article Type</label>
                      <select class="select2 form-control" id="searchType" name="searchType">
                          <option value="">All</option>
                          @foreach($types as $val)
                            <option value="{{$val->code}}" >{{$val->code}} - {{$val->name}}</option>
                          @endforeach
                      </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12"> 
                        <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                        @can('article-create')
                        <a href="{{ route('article.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                        @endcan
                    </div>
                </div>
            </form>
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
              <h5>Movement <span class="bold" id="mdlartikel"></span></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
          </div>
          <div class="modal-body text-center">
            <div class="table-responsive">
              <table class="table table-striped" id="mdlmovetable">
                <thead>
                  <tr>
                    <th style="width:5%">Kode</th>
                    <th style="width:10%">Tanggal</th>
                    <th style="width:10%">Type</th>
                    <th style="width:10%">K.Trans</th>
                    <th style="width:10%">Harga</th>
                    <th>QTY -</th>
                    <th>QTY +</th>
                    <th>QTY</th>
                    <th>Keterangan</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
      </div>
  </div>
</div>

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
        $('#modalConfirmation').attr("action", href);
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
      let name = $("#searchName").val();
      let code = $("#seachCode").val();
      let group = $("#searchGroup").val();
      let cust = $("#searchCustomer").val();
      let supp = $("#searchSupplier").val();
      let type = $("#searchType").val();
      showList(name,code,group,cust,supp,type);
  });

  $("#btnSearch").click(function(e){
      let name = $("#searchName").val();
      let code = $("#seachCode").val();
      let group = $("#searchGroup").val();
      let cust = $("#searchCustomer").val();
      let supp = $("#searchSupplier").val();
      let type = $("#searchType").val();
      showList(name,code,group,cust,supp,type);
  });

  function showList(name,code,group,cust,supp,type){
    let dtdom ='<"d-flex justify-content-between align-items-center header-actions mx-1 row mt-75" <"col-lg-12 col-xl-6" l><"col-lg-12 col-xl-6 pl-xl-75 pl-0"<"dt-action-buttons text-xl-right text-lg-left text-md-right text-left d-flex align-items-center justify-content-lg-end align-items-center flex-sm-nowrap flex-wrap mr-1"<"mr-1"f>B>>>t<"d-flex justify-content-between mx-2 row mb-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>';
    let arr_col_print =[1,2,3,4,5,6,7]; 
    $(function(){
      let oTable =$("#detailedTable").DataTable({
        ajax:
        {
          url:'{{ route("article.list")}}',
          data:{
              name:name,
              code:code,
              group:group,
              cust:cust,
              supp:supp,
              type:type
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
                exportOptions: { columns: arr_col_print },
                action: newExportAction,
                title:null,
                filename:'articles'
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
          { width: '5%', targets: 0 },
          { className: 'text-right','targets': [4,5] },
        ],
        drawCallback: function( settings ) {
          feather.replace({
                width: 14,
                height: 14
          });
        },
        order: [[ 2, 'asc' ]],
        bDestroy: true, //pakai ini supaya bisa di load berulang2
        // scrollX: true, //pakai ini supaya waktu responsive  bisa di scroll horizontal
        columns: [
            { data: 'action', name: 'action',title:'action', orderable: false, searchable: false },
            { data: 'code', name: 'article_alternative_code',title:'Code' },
            { data: 'desc', name: 'article_desc',title:'Name' },
            { data: 'cust', name: 'third_party.nama',title:'Custs/Supp' },
            { data: 'costprice', name: 'costprice',title:'Price',render: $.fn.dataTable.render.number(',','.') },
            // { data: 'article_qty', name: 'article_qty',title:'Qty',render: $.fn.dataTable.render.number(',','.',3) },
            { data: "article_qty", name: 'article_stock.article_qty',title:'Qty',
              render: function (data, type, row) {
                return data ? humanizeNumber(data*1) : 0;
              }
            },
            { data: 'uom', name: 'uom',title:'UOM' },
            { data: 'group', name: 'group_materials.name',title:'Group' },
            { data: 'note', name: 'note',title:'Note' }
        ],
      });
    });
    //$('div.head-label').html('<h6 class="mb-0">Data Users</h6>');
    
  }

  function movement(artCode,artikelAlternativeCode,artDesc){

    $('#mdlmovement').modal('show');
    $('#mdlartikel').text('|'+artikelAlternativeCode+'-'+artDesc);

    let dtdom ='<"d-flex justify-content-between align-items-center header-actions mx-1 row mt-75"' +
        '<"col-lg-12 col-xl-6" l>' +
        '<"col-lg-12 col-xl-6 pl-xl-75 pl-0"<"dt-action-buttons text-xl-right text-lg-left text-md-right text-left d-flex align-items-center justify-content-lg-end align-items-center flex-sm-nowrap flex-wrap mr-1"<"mr-1"f>B>>' +
        '>t' +
        '<"d-flex justify-content-between mx-2 row mb-1"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>';
    let arr_col_print =[2,3,4,5,6,7]; 
    $(function(){
      let oTable =$("#mdlmovetable").DataTable({
        ajax:
        {
          url:'{{ route("article.movement")}}',
          data:{
            articleCode:artCode
          },
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
          { className: 'dt-right', 'targets': [ 4 ] },
          { className: 'dt-right', 'targets': [ 5 ] },
          { className: 'dt-right', 'targets': [ 6 ] },
          { className: 'dt-right', 'targets': [ 7 ] }
        ],
        order: [[ 2, 'asc' ]],
        bDestroy: true, //pakai ini supaya bisa di load berulang2
        // scrollX: true, //pakai ini supaya waktu responsive  bisa di scroll horizontal
        columns: [
          { data: 'movement_code', name: 'movement_code' },
          { data: 'movement_date', name: 'movement_date'},
          { data: 'movement_type', name: 'movement_type'},
          { data: 'movement_transnno', name: 'movement_transnno'},
          { data: "movement_price", render: $.fn.dataTable.render.number( ',', '.', 0 ) },
          { data: "movement_min", render: $.fn.dataTable.render.number( ',', '.', 3 ) },
          { data: "movement_plus", render: $.fn.dataTable.render.number( ',', '.', 3 ) },
          { data: "balanceqty", render: $.fn.dataTable.render.number( ',', '.', 3 ) },
          { data: 'movement_desc', name: 'movement_desc'}
        ],
      });
    });
  }

    //export all data on datatables and all pages
    var oldExportAction = function (self, e, dt, button, config) {
        if (button[0].className.indexOf('buttons-excel') >= 0) {
            if ($.fn.dataTable.ext.buttons.excelHtml5.available(dt, config)) {
                $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config);
            }
            else {
                $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button, config);
            }
        } else if (button[0].className.indexOf('buttons-print') >= 0) {
            $.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
        }
    };

    var newExportAction = function (e, dt, button, config) {
        var self = this;
        var oldStart = dt.settings()[0]._iDisplayStart;

        dt.one('preXhr', function (e, s, data) {
            // Just this once, load all data from the server...
            data.start = 0;
            data.length = 2147483647;

            dt.one('preDraw', function (e, settings) {
                // Call the original action function 
                oldExportAction(self, e, dt, button, config);

                dt.one('preXhr', function (e, s, data) {
                    // DataTables thinks the first item displayed is index 0, but we're not drawing that.
                    // Set the property to what it was before exporting.
                    settings._iDisplayStart = oldStart;
                    data.start = oldStart;
                });

                // Reload the grid with the original page. Otherwise, API functions like table.cell(this) don't work properly.
                setTimeout(dt.ajax.reload, 0);

                // Prevent rendering of the full data to the DOM
                return false;
            });
        });

        // Requery the server with the new one-time export settings
        dt.ajax.reload();
    };

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
