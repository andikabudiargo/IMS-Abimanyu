@extends('layouts.app')
@section('title', 'Users')
@section('content')
@include('layouts.breadcrumb')
<section id="users-index">
  <div class="form-row">
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
                  <input type="text" class="form-control" id="SearchUser" name="SearchUser" placeholder="" />
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
  function validasidelete(userid,username){
    Swal.fire({
      title: "Are you sure?",
        text: "User: "+username+" will be deleted",
        icon: "warning",
        showCancelButton: true,
        customClass: {
          cancelButton: 'order-1 right-gap',
          confirmButton: 'order-2 btn-danger',
        },
        confirmButtonText: "Yes",
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          dataType: 'json',
          type:'DELETE',
          url: "{{ route('users.delete') }}",
          data:{
            userid:userid
          },
          success: function(data) {
              Swal.fire("DELETED!", data.message, "success");
              tampildata('');
          },
          error: function(data) {
              Swal.fire("DELETED!", data.status, "warning");
              tampildata('');
          }
        });
      }   
    });
  }
    
  $("#btnSearch").click(function(e){
    let nama =$("#SearchUser").val();
    tampildata(nama);
  });

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
    let nama =$("#SearchUser").val();
    tampildata(nama);
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
    let arr_col_print =[1,2,3,4]; 
    $(function(){
        var oTable =$("#detailedTable").DataTable({
            ajax:{
              url:'{{ route("user.lists")}}',
              data:{q:nama}
            },
            processing: true,
            serverSide: true,
            buttons: true,
            dom:dtdomGlob,
            lengthMenu: [
              [ 10, 25, 50, -1 ],
              [ '10', '25', '50', 'all' ]
            ],
            buttons: buttonExportGlob(arr_col_print),
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
            columns: {!! $kolom !!},
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