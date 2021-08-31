@extends('layouts.app')
@section('title', 'Permissions')
@section('content')
@include('layouts.breadcrumb')

<section id="table-permissions">
  <div class="row">
      <div class="col-12">
          <div class="card" id="oki">
              <table id="detailedTable" class="datatables-basic table">
                  <thead>
                  </thead>
              </table>
          </div>
      </div>
  </div>
</section>

<!-- Modal tambah permission-->
<div id="modalAddPermission" class="modal bisa-geser fade text-left" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header" >
          <h4>Tambah<span class="semi-bold"> @yield('title')</span></h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="frmAdd" name="frmAdd" class="form form-vertical" autocomplete="off">
            <div class="row">
              <div class="col-12">
                <label>Group name</label>
                <div class="form-group" required>
                  <input type="text" id="name" name="name" class="form-control" maxlength="30" >
                </div>
              </div>
              <div class="demo-inline-spacing col-12" id="kelompok_edit">
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="chk_index" name="chk_index"  value="1" checked />
                  <label class="custom-control-label" for="chk_index">Index</label>
                </div>
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="chk_list" name="chk_list"  value="1" checked />
                  <label class="custom-control-label" for="chk_list">List</label>
                </div>
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="chk_create" name="chk_create"  value="1" checked />
                  <label class="custom-control-label" for="chk_create">Create</label>
                </div>
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="chk_edit" name="chk_edit"  value="1" checked />
                  <label class="custom-control-label" for="chk_edit">Edit</label>
                </div>
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="chk_delete" name="chk_delete"  value="1" checked />
                  <label class="custom-control-label" for="chk_delete">Delete</label>
                </div>
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="chk_other" name="chk_other"  value="1" checked />
                  <label class="custom-control-label" for="chk_other">Other</label>
                </div>
              </div>
            </div>
            <br>
            <div id="form_other">
              <div class="row" >
                <div class="col-10">
                  <label>Display Name</label>
                  <div class="form-group required">                      
                    <input type="text"  id="display_name" name="display_name" class="form-control"  maxlength="30">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-10">
                  <label>Description</label>
                  <div class="form-group">
                    <input type="text"  id="description" name="description" class="form-control"  maxlength="30">
                  </div>
                </div>
              </div>
            </div>
            <br>
            <button id="cmdSave" class="btn btn-primary" type="button">
              <span class="simpanspan">Simpan</span>
            </button>
            <button id="cmdCancel" class="btn btn-primary" data-dismiss="modal" type="button">
              <span>Cancel</span>
            </button>
          </form>
        </div>
      </div>
    <!-- /.modal-content -->
  </div>
</div>
<!-- /.modal-dialog -->

<!-- Modal edit permission-->
<div id="modalEditPermission" class="modal draggable fade text-left" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4>Edit<span class="semi-bold"> @yield('title')</span></h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="frmAdd" name="frmAdd" class="form form-vertical" autocomplete="off">
              <input type="hidden" id="permission_id" name="permission_id">
              <div class="row">
                <div class="col-12">
                  <label>Group name</label>
                  <div class="form-group" required>
                    <input type="text" id="editGroupName" name="editGroupName" class="form-control" maxlength="30" >
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <label>Name</label>
                  <div class="form-group" required>
                    <input type="text" id="editName" name="editName" class="form-control" maxlength="30" >
                  </div>
                </div>
              </div>
              <div class="row" >
                <div class="col-12">
                  <label>Display Name</label>
                  <div class="form-group required">                      
                    <input type="text"  id="editDisplayName" name="editDisplayName" class="form-control"  maxlength="30">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <label>Description</label>
                  <div class="form-group">
                    <input type="text"  id="editDescription" name="editDescription" class="form-control"  maxlength="30">
                  </div>
                </div>
              </div>
              <br>
              <button id="cmdSaveEdit" class="btn btn-primary" type="button">
                <span class="simpanEditSpan">Save</span>
              </button>
              <button id="cmdEditCancel" class="btn btn-primary" data-dismiss="modal" type="button">
                <span>Cancel</span>
              </button>
            </form>
        </div>
      </div>
    <!-- /.modal-content -->
  </div>
</div>
<!-- /.modal-dialog -->

@endsection

@section('styles')
  <style>
    .checkbox {
        margin-bottom: 1px !important;
        margin-top: 1px !important;
    }
  </style>
@endsection

@section('scripts')
  <script type="text/javascript">
    $(document).ready(function(){    
        $('#form_other').hide();
        showList();
    });

    $('#chk_other').change(function () {
	    if ($(this).is(':checked')) {
	        $('#form_other').show();
          $('#display_name').focus();
	    } else {
	    	$('#form_other').hide();
	    }
	  });

    function showList(oIsiCari){
      let dtdom = '<"card-header border-bottom p-1"<"head-label"><"dt-action-buttons text-right"B>><"d-flex justify-content-between align-items-center mx-0 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"d-flex justify-content-between mx-0 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>';
      let arr_col_print =[3,4,5];
      let groupColumn = 1;
      let groupColumnSpan = 4;
      var oTable = $("#detailedTable").DataTable({
        processing: true,
        serverSide: true,
        // buttons: true,
        bDestroy: true, //pakai ini supaya bisa di load berulang2
        oSearch: {"sSearch":oIsiCari},
        scrollX: true,
        ajax:{url: "{{ route('permission.list') }}"},
        columns: [
          { data: 'responsive_id', name: 'responsive_id', orderable: false, searchable: false},
          { data: 'group_name', name: 'group_name' ,title:'Group'},
          { data: 'name', name: 'name' ,title:'Name'},
          { data: 'display_name', name: 'display_name',title:'Display name' },
          { data: 'description', name: 'description',title:'Description' },
          { data: 'action', name: 'action',title:'Action', orderable: false, searchable: false},
        ],
        columnDefs: [
          {
            // For Responsive
            className: 'control',
            orderable: false,
            responsivePriority: 2,
            targets: 0
          },
          { visible: false, targets: groupColumn },
          {
            responsivePriority: 1,
            targets: 3
          }
        ],
        drawCallback: function (settings) {
          var api = this.api();
          var rows = api.rows({ page: 'current' }).nodes();
          var last = null;

          api
            .column(groupColumn, { page: 'current' })
            .data()
            .each(function (group, i) {
              if (last !== group) {
                $(rows)
                  .eq(i)
                  .before('<tr class="group" style="background-color: #75759d;color: white;"><td colspan="'+groupColumnSpan+'">' + group + '</td></tr>');

                last = group;
              }
            });
            feather.replace({
                width: 14,
                height: 14
            });
        },
        
        order: [[groupColumn, 'asc']],
        dom:dtdom,
        displayLength: 10,
        lengthMenu: [
                [ 10, 25, 50, -1 ],
                [ '10', '25', '50', 'All' ]
        ],
        buttons: [
          {
            extend: 'collection',
            className: 'btn btn-outline-secondary dropdown-toggle mr-2',
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
          {
            text: feather.icons['plus'].toSvg({ class: 'mr-50 font-small-4' }) + 'Add New Permission',
            className: 'create-new btn btn-primary',
            attr: {
              'data-toggle': 'modal',
              'data-target': '#modalAddPermission'
            },
            init: function (api, node, config) {
              $(node).removeClass('btn-secondary');
            }
          }
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
                // console.log(columns);
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
        }
      });
      $('div.head-label').html('<h6 class="mb-0">Permission lists</h6>');
      // Order by the grouping
      $('.dt-row-grouping tbody').on('click', 'tr.group', function () {
        var currentOrder = table.order()[0];
        if (currentOrder[0] === groupColumn && currentOrder[1] === 'asc') {
          groupingTable.order([groupColumn, 'desc']).draw();
        } else {
          groupingTable.order([groupColumn, 'asc']).draw();
        }
      });

      oTable.on('draw.dt', function () {
        $('.my-tooltip').tooltip({
             trigger: "hover"
        });
      });
    }

    $('#modalAddPermission').on('shown.bs.modal', function() {
      bebersih();
      $('#name').focus();
    });

   

    function validasidelete(permission_id){

      Swal.fire({
        title: "Yakin akan di delete?",
        text: "Permission akan di delete dari database",
        icon: 'info',
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "Hapus",
        closeOnConfirm: false
      }).then((result) => {
        /* Read more about isConfirmed, isDenied below */
        if (result.isConfirmed) {
          $.ajax({
            dataType: 'json',
            type:'POST',
            url: "{{ route('delete.permission') }}",
            data:{
              permission_id:permission_id
            },
            success: function(data) {
                Swal.fire("Selesai!",data.message, "success");
                showList();
            },
              error: function(data) {
                alert(data.status);
            }
          });
        } 
      })
    }

    function validasiedit(id,name,groupName,displayName,description){   
        $('#permission_id').val(id);
        $('#editGroupName').val(groupName);
        $('#editName').val(name);
        $('#editName').attr('disabled','disabled');
        $('#editDisplayName').val(displayName);
        $('#editDescription').val(description);
        $('#modalEditPermission').modal('show');
        $('#modalEditPermission').on('shown.bs.modal', function() {
          $('#editGroupName').focus();
        });
    }

    function bebersih(){

      $("#chk_index").prop('checked', true);
      $("#chk_create").prop('checked', true);
      $("#chk_edit").prop('checked', true);
      $("#chk_list").prop('checked', true);
      $("#chk_delete").prop('checked', true);
      $("#chk_other").prop('checked', false);

      $('#permission_id').val('');
      $('#name').val('');
      $('#name').removeAttr('disabled');
      $('#display_name').val('');
      $('#description').val('');
      $('.simpanspan').text('Save');
      $("#kelompok_edit").show();
      $('#form_other').hide();
      $('#name').focus();
      
    }
    
    $("#cmdCancel").click(function(e){
        bebersih();
    });

    $("#cmdSave").click(function(e){
      e.preventDefault();
      var permission_id=$('#permission_id').val();
      var menu_name=$('#name').val();
      var display_name=$('#display_name').val();
      var description=$('#description').val();
      var index,create,edit,list,adelete,other;
      
      $('#chk_index').is(":checked") ?  index = '1' :  index = '0';
      $('#chk_create').is(":checked") ?  create = '1' :  create = '0';
      $('#chk_edit').is(":checked") ?  edit = '1' :  edit = '0';
      $('#chk_list').is(":checked") ?  list = '1' :  list = '0';
      $('#chk_delete').is(":checked") ?  adelete = '1' :  adelete = '0';
      $('#chk_other').is(":checked") ?  other = '1' :  other = '0';

      var flag=0;
      var pesan='';
      
      if (other =='0' && menu_name==''){
        pesan +="Nama menu harus diisi...";
        flag =1;
      }

      if (other =='1' && display_name =='' ){
          pesan +="Display Name harus diisi...";
          flag =1;
      }

      if (flag==0) {
        $.ajax({
          dataType: 'json',
          type:'POST',
          url: "{{ route('store.permission') }}",
          data: { permission_id:permission_id,
                  name:menu_name,
                  display_name:display_name,
                  description:description,
                  index:index,
                  create:create,
                  edit:edit,
                  list:list,
                  delete:adelete,
                  other:other
                },
            success: function(data) {
            if (data.status ==1){
              bebersih();
              showList(menu_name);
            }else{
              Swal.fire('Warning ... ', data.message ,'warning');    
            }
        },
          error: function(data) {
            Swal.fire('Error..',data.status,'error');
          }
        }); 
      }else{
        $('#name').focus();
        Swal.fire('Warning ... ', pesan ,'warning');    
      }
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

  </script>
@endsection
