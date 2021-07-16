@extends('layouts.app')
@section('title', 'Users')
@section('content')
@include('layouts.breadcrumb')
<section id="users-index">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">  
          <div class="card-title">@yield('title')
          </div>
        </div>
        <div class="card-body">
          <form class="needs-validation" novalidate>
            <div class="form-row">
              <div class="col-md-4"> 
                <div class="form-group">
                  <label for="basicInput">Username</label>
                  <input type="text" class="form-control text-uppercase" id="SearchUser" name="SearchUser" placeholder="" />
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
            @can('user-create')
            <a href="{{ route('users.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
            @endcan
        </div>
      </div>
    </div>
  </div>
</section>

<section id="table-users">
  <div class="card">
    <h5 class="card-header">Search Filter</h5>
    <div class="d-flex justify-content-between align-items-center mx-50 row pt-0 pb-2">
      <div class="col-md-4 user_role"></div>
      <div class="col-md-4 user_name"></div>
      <div class="col-md-4 user_email"></div>
    </div>
  </div>
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">List User</h4>
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
	
@endsection
@section('styles')
<style>
</style>
@endsection

@section('scripts')
<script type="text/javascript">

  function validasidelete(userid){
     Swal.fire({
        title: "Yakin akan di hapus?",
        text: "ID "+userid+" Akan dihapus",
        icon: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "Hapus",
        closeOnConfirm: false
    } ,
    function(){
        $.ajax({
          dataType: 'json',
          type:'DELETE',
          url: "{{route('users.delete')}}",
          data:{userid:userid},
          success: function(data) {
              //  Swal.fire("UPDATED!", "Generate data berhasil", "success");
              tampildata('');
          },
            error: function(data) {
              // Swal.fire("Error :" + data.status);
              tampildata('');
          }
        });
         Swal.fire("DELETED!", "Hapus data berhasil", "success");
      });
      //return confirm("Do you want to delete this item?"); 
  }

	$("#btnSearch").click(function(e){
		var nama =$("#SearchUser").val();
    tampildata(nama);
  });

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
    tampildata('');
  });

  function lockUnlock(){
    $(".userLock").change(function(){
      let id = $(this).attr('id');
      let key= $(this).data('nama');
      let newStatus,oldStatus;
      let domId="lblUserLock_"+ key;
      if (this.checked) {
        newStatus=1;
        oldsStatus=0;
        updateStatus(key,oldStatus,newStatus,domId)
      } else {
        newStatus=0;
        oldStatus=1;
        updateStatus(key,oldStatus,newStatus,domId)
      }
    });
  }

  function updateStatus(username,oldStatus,newStatus,domId){
      $.ajax({
        dataType: 'json',
        type:'POST',
        url: "{{route('user.update.status')}}",
        data:{
          username:username,
          oldStatus:oldStatus,
          newStatus:newStatus
        },
        success: function(data) {
          if (data.status=1){
            if (newStatus==1){
              $("#"+domId).text("Active");
            }

            if (newStatus==0){
              $("#"+domId).text("Locked");
            }
          }else{
             Swal.fire("Warning", data.message, "warning");
          }
        },
        error: function(data) {
           Swal.fire("Error","Error :" + data.status,"error");
        }
      });
  }
  
	function tampildata(nama){
    // let dtdom = '<"card-header border-bottom p-1"<"head-label">><"d-flex justify-content-between align-items-center mx-0 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-4"f><"col-sm-12 col-md-2"<"dt-action-buttons text-right"B>>>t<"d-flex justify-content-between mx-0 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>';
    let dtdom ='<"d-flex justify-content-between align-items-center header-actions mx-1 row mt-75"' +
        '<"col-lg-12 col-xl-6" l>' +
        '<"col-lg-12 col-xl-6 pl-xl-75 pl-0"<"dt-action-buttons text-xl-right text-lg-left text-md-right text-left d-flex align-items-center justify-content-lg-end align-items-center flex-sm-nowrap flex-wrap mr-1"<"mr-1"f>B>>' +
        '>t' +
        '<"d-flex justify-content-between mx-2 row mb-1"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>';
    let arr_col_print =[1,2,3,4]; 
    $(function(){
        var oTable =$("#detailedTable").DataTable({
            ajax:
            {
              url:'{{ route("user.lists")}}',
              data:{q:nama}
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
                    return 'Details of ' + data['name'];
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
                targets: 1,
                visible: false
              },
              {
                // Avatar image/badge, Name and post
                targets: 2,
                responsivePriority: 4,
                render: function (data, type, full, meta) {
                  var $user_img = full['avatar'],
                    $name = full['username'],
                    $post = full['name'];
                    if ($user_img) {
                        // For Avatar image
                        var $output =
                          '<img src="' + assetPath + 'images/avatars/' + $user_img + '" alt="Avatar" width="32" height="32">';
                      } else {
                        // For Avatar badge
                        var stateNum = full['status'];
                        var states = ['success', 'danger', 'warning', 'info', 'dark', 'primary', 'secondary'];
                        var $state = states[stateNum],
                          $name = full['username'],
                          $initials = $name.match(/\b\w/g) || [];
                        $initials = (($initials.shift() || '') + ($initials.pop() || '')).toUpperCase();
                        $output = '<span class="avatar-content">' + $initials + '</span>';
                    }

                    var colorClass = $user_img === '' ? ' bg-light-' + $state + ' ' : '';
                    // Creates full output for row
                    var $row_output =
                      '<div class="d-flex justify-content-left align-items-center">' +
                      '<div class="avatar ' +
                      colorClass +
                      ' mr-1">' +
                      $output +
                      '</div>' +
                      '<div class="d-flex flex-column">' +
                      '<span class="emp_name text-truncate font-weight-bold">' +
                      $name +
                      '</span>' +
                      '<small class="emp_post text-truncate text-muted">' +
                      $post +
                      '</small>' +
                      '</div>' +
                      '</div>';
                    return $row_output;
                }
              },
              {
                responsivePriority: 1,
                targets: 2
              },
              { width: '10%', targets: 8 }
            ],
            drawCallback: function( settings ) {
              feather.replace({
                    width: 14,
                    height: 14
              });

              lockUnlock();
            },
            order: [[ 1, 'asc' ]],
            bDestroy: true, //pakai ini supaya bisa di load berulang2
            // scrollX: true, //pakai ini supaya waktu responsive  bisa di scroll horizontal
            columns: [
                { data: 'group_id',name:'group_id', title:'',orderable: false, searchable: false },
                { data: 'name', name: 'name',title:'Name' },
                { data: 'username', name: 'username',title:'Username'},
                { data: 'email', name: 'email',title:'Email' },
                { data: 'status', name: 'email',title:'Status' },
                { data: 'roles', name: 'roles',title:'Roles' },
                { data: 'last_login_at', name: 'last_login_at',title:'Last login' },
                { data: 'last_login_ip', name: 'last_login_ip',title:'Last IP' },
                { data: 'action', name: 'action',title:'action', orderable: false, searchable: false },
            ],
            initComplete: function () {
              // Adding role filter once table initialized
              $( "#UserRole" ).remove();
              $( "#UserName" ).remove();
              this.api()
                .columns(3)
                .every(function () {
                  var column = this;
                  var select = $(
                    '<select id="UserRole" class="form-control text-capitalize mb-md-0 mb-2"><option value=""> Select Email </option></select>'
                  )
                    .appendTo('.user_email')
                    .on('change', function () {
                      var val = $.fn.dataTable.util.escapeRegex($(this).val());
                      column.search(val ? '^' + val + '$' : '', true, false).draw();
                    });

                  column
                    .data()
                    .unique()
                    .sort()
                    .each(function (d, j) {
                      select.append('<option value="' + d + '" class="text-capitalize">' + d + '</option>');
                    });
                });
              this.api()
                .columns(5)
                .every(function () {
                  var column = this;
                  var select = $(
                    '<select id="UserRole" class="form-control text-capitalize mb-md-0 mb-2"><option value=""> Select Role </option></select>'
                  )
                    .appendTo('.user_role')
                    .on('change', function () {
                      var val = $.fn.dataTable.util.escapeRegex($(this).val());
                      column.search(val ? '^' + val + '$' : '', true, false).draw();
                    });

                  column
                    .data()
                    .unique()
                    .sort()
                    .each(function (d, j) {
                      select.append('<option value="' + d + '" class="text-capitalize">' + d + '</option>');
                    });
                });
              // Adding plan filter once table initialized
              this.api()
                .columns(2)
                .every(function () {
                  var column = this;
                  var select = $(
                    '<select id="UserName" class="form-control text-capitalize mb-md-0 mb-2"><option value=""> Select Username </option></select>'
                  )
                    .appendTo('.user_name')
                    .on('change', function () {
                      var val = $.fn.dataTable.util.escapeRegex($(this).val());
                      column.search(val ? '^' + val + '$' : '', true, false).draw();
                    });

                  column
                    .data()
                    .unique()
                    .sort()
                    .each(function (d, j) {
                      select.append('<option value="' + d + '" class="text-capitalize">' + d + '</option>');
                    });
                });
            }
      });
      // $('div.head-label').html('<h6 class="mb-0">Data Users</h6>');
    });
  }

  $('#detailedTable').on('draw.dt', function () {  
      $('.my-tooltip').tooltip({
            trigger: "hover"
      });
  });
	
	$.ajaxSetup({
	    headers: {
	        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
	    }
	});

</script>
@endsection