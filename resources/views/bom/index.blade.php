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
              <div class="form-group col-md-3"> 
                <label for="searchBom">Bom Number</label>
                <input type="text" class="form-control text-uppercase" id="searchBom" name="searchBom" placeholder=""  />
              </div>
              <div class="form-group col-md-5">
                <label class="form-label" for="articleCode">Article*</label>
                <select class="select2 form-control" id="articleCode" name="articleCode" required>
                    <option label=""></option>
                    @foreach($articles as $val)
                        <option value="{{ $val->article_code }}" >{{ $val->article_alternative_code }} - {{ $val->article_desc }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @can('bom-create')
                    <a href="{{ route('bom.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
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
    let searchBom = $("#searchBom").val();
    let articleCode = $("#articleCode").val();
    showList(searchBom,articleCode);

  });

  function showList(searchBom,articleCode){
    // let dtdom = '<"card-header border-bottom p-1"<"head-label">><"d-flex justify-content-between align-items-center mx-0 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-4"f><"col-sm-12 col-md-2"<"dt-action-buttons text-right"B>>>t<"d-flex justify-content-between mx-0 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>';
    let dtdom ='<"d-flex justify-content-between align-items-center header-actions mx-1 row mt-75"' +
        '<"col-lg-12 col-xl-6" l>' +
        '<"col-lg-12 col-xl-6 pl-xl-75 pl-0"<"dt-action-buttons text-xl-right text-lg-left text-md-right text-left d-flex align-items-center justify-content-lg-end align-items-center flex-sm-nowrap flex-wrap mr-1"<"mr-1"f>B>>' +
        '>t' +
        '<"d-flex justify-content-between mx-2 row mb-1"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>';
    let arr_col_print =[2,3,4,5,6]; 
    $(function(){
      let oTable =$("#detailedTable").DataTable({
        ajax:
        {
          url:'{{ route("bom.list")}}',
          data:{
              searchBom:searchBom,
              articleCode:articleCode,
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
        responsive: {
          details: {
            display: $.fn.dataTable.Responsive.display.modal({
              header: function (row) {
                var data = row.data();
                return 'Details of ' + data['nama'];
              }
            }),
            type: 'column',
            renderer: function (api, rowIdx, columns) {
              var data = $.map(columns, function (col, i) {
                return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
                  ? '<tr data-dt-row="' +
                      col.rowIndex +
                      '" data-dt-column="' +
                      col.columnIndex +
                      '">' +
                      '<td>' +
                      col.title +
                      ':' +
                      '</td> ' +
                      '<td>' +
                      col.data +
                      '</td>' +
                      '</tr>'
                  : '';
              }).join('');
              return data ? $('<table class="table"/>').append(data) : false;
            }
          }
        },
        language: {
          paginate: {
            // remove previous & next text from pagination
            previous: '&nbsp;',
            next: '&nbsp;'
          }
        },
        columnDefs: [
          {
            // For Responsive
            className: 'control',
            orderable: false,
            responsivePriority: 2,
            targets: 0
          },
          {
            responsivePriority: 1,
            targets: 2
          },
          {
            responsivePriority: 3,
            targets: 3
          },
          {
            responsivePriority: 4,
            targets: 4
          },
          { width: '10%', targets: 1 },
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
            { data: 'group_id',name:'group_id', title:'',orderable: false, searchable: false },
            { data: 'action', name: 'action',title:'action', orderable: false, searchable: false },
            { data: 'bom_code', name: 'bom_code',title:'BOM Code' },
            { data: 'customer', name: 'customer',title:'Customer' },
            { data: 'article_code', name: 'article_code',title:'Article' },
            { data: 'group_of_material', name: 'group_of_material',title:'Group' },
            { data: 'status', name: 'status',title:'Status' },
            { data: 'note', name: 'note',title:'Note' },
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
