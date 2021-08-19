@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<section id="employee-index">
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
                      <label for="searchEmpCode">Kode</label>
                      <input type="text" class="form-control text-uppercase" id="searchEmpCode" name="searchEmpCode" placeholder=""  />
                    </div>
                    <div class="form-group col-md-4"> 
                      <label for="searchEmpName">Nama</label>
                      <input type="text" class="form-control text-uppercase" id="searchEmpName" name="searchEmpName" placeholder="" />
                    </div>
                    <div class="form-group col-md-4"> 
                      <label class="form-label" for="dept">Departemen</label>
                      <select class="select2 form-control" id="dept" name="dept">
                          <option label=""></option>
                          @foreach($depts as $val)
                              <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                          @endforeach
                      </select>
                    </div>
                    <div class="form-group col-md-4"> 
                      <label class="form-label" for="position">Jabatan</label>
                      <select class="select2 form-control" id="position" name="position">
                          <option label=""></option>
                          @foreach($positions as $val)
                              <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                          @endforeach
                      </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12"> 
                        <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                        @can('department-create')
                        <a href="{{ route('employee.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                        @endcan
                    </div>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</section>

<section id="table-employee">
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

  $("#btnSearch").click(function(e){
      let name = $("searchEmpName").val();
      let code = $("searchEmpCode").val();
      let dept = $("searchEmpDept").val();
      let pos = $("searchEmpPos").val();
      showList(name,code,dept,pos);
  });

  function showList(name,code,dept,pos){
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
          url:'{{ route("employee.list")}}',
          data:{
              name:name,
              code:code,
              dept:dept,
              pos:pos
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
            targets: 3
          },
          { width: '10%', targets: 1 }
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
            { data: 'employee_id', name: 'employee_id',title:'Kode' },
            { data: 'name', name: 'name',title:'Nama' },
            { data: 'address', name: 'address',title:'Alamat' },
            { data: 'department', name: 'department',title:'Department' },
            { data: 'job_position', name: 'job_position',title:'Jabatan' }

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
