@extends('layouts.main')
@section('title', 'Menu')
@section('content')
<div class="row">
  <div class="col-md-8">
    <div id="card-advance" class="card card-default">
      <div class="card-header ">
        <div class="card-title"><h5 class="font-montserrat text-uppercase text-black">@yield('title')</h5></div>
        <div class="card-controls">
          <ul>
            <li><a href="#" class="card-collapse" data-toggle="collapse">
                  <i class="card-icon card-icon-collapse"></i>
                </a>
            </li>
          </ul>
        </div>
        <p class="pull-right bold font-montserrat text-uppercase" id="status-area"> </p>
      </div>
      <div class="card-block">
        <form role="form" autocomplete="off">
          <div class="row clearfix"></div>
          <button id="btntambah" class="btn btn-info" type="button" onclick="mdltambah()"><span class="proses">Tambah Test untuk penambahan pull</span></button>
        </form>
      <br>
        <div class="pull-right" id="btnexport"></div>
        <div class="table-responsive">
          <table id="detailedTable" class="display nowrap" width="100%" cellspacing="0">
            <thead>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card card-default">
      <div class="card-header ">
        <div class="card-title"><h5 class="font-montserrat text-uppercase text-black">Daftar Menu</h5></div>
      </div>
      <div class="card-block oki">
        {{-- <div id="drag-tree">
            {!! $menu_tree !!}
        </div> --}}
      </div>
    </div>
  </div>
</div>

<!-- Modal tambah menu-->
<div id="modalAddMenu"  class="modal fade slide-up disable-scroll show" tabindex="-1" role="dialog" aria-hidden="false" >
  <div class="modal-dialog" >
    <div class="modal-content-wrapper">
      <div class="modal-content">
        <div class="modal-header clearfix text-left">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="pg-close fs-14"></i></button> 
          <h5>Tambah<span class="semi-bold"> @yield('title')</span></h5>
          <p class="p-b-10"></p>
        </div>
        <div class="modal-body">
          <form id="frmAdd" name="frmAdd" role="form" autocomplete="off">
              <input type="hidden" id="menu_id" name="menu_id" >
              <div class="row">
                  <div class="form-group form-group-default required">
                    <label>Name</label>
                    <input type="text" id="name" name="name" class="form-control" maxlength="30" >
                  </div>
              </div>
              <div class="row">
                <div class="radio radio-success">
                  <input type="radio" value="menu" name="optionmanu" id="menu" checked="checked">
                  <label for="menu">Menu</label>
                  <input type="radio" value="submenu" name="optionmanu" id="submenu">
                  <label for="submenu">Sub Menu</label>
                </div>
              </div>
              <div id="daftar_menu">
                <div class="row" >
                    <div class="form-group form-group-default form-group-default-select2 required">
                      <label>Menu</label>
                      <select id="menu_id" name="menu_id" class="cs-select cs-skin-slide cs-transparent form-control dropmodal" data-init-plugin="select2" style="width:100%">
                        @foreach($menus as $val)
                        <option value="{{$val->id}}">{{$val->title}}</option>
                        @endforeach
                      </select>
                    </div>
                </div>
              </div>
              <div class="row" >
                  <div class="form-group form-group-default form-group-default-select2 required">
                    <label>Permission</label>
                    <select id="permission_id" name="permission_id" class="cs-select cs-skin-slide cs-transparent form-control dropmodal" data-init-plugin="select2" style="width:100%">
                      @foreach($permissions as $val)
                      <option value="{{$val->id}}">{{$val->display_name}}</option>
                      @endforeach
                    </select>
                  </div>
              </div>
              <div class="row" >
                  <div class="form-group form-group-default form-group-default-select2 required">
                    <label>Icon</label>
                    <select id="icon" name="icon" class="select2-icon  cs-select cs-skin-slide cs-transparent form-control" data-init-plugin="select2" style="width:100%">
                      @foreach($icons as $val)
                      <option value="{{$val->fa_name}}" data-icon="{{$val->fa_name}}">{{$val->fa_name}}</option>
                      @endforeach
                    </select>
                  </div>
              </div>              
              <div class="row">
                  <div class="form-group form-group-default form-group-default-select2 required">
                    <label>Route</label>
                    <select id="route" name="route" class="select2-icon  cs-select cs-skin-slide cs-transparent form-control" data-init-plugin="select2" style="width:100%">
                      <option value="" ></option>
                      @foreach($routeCollection as $val)
                      <option value="{{$val->getName()}}" >{{$val->methods()[0]}} : {{$val->uri()}} as {{ $val->getName() }}</option>
                      @endforeach
                    </select>
                  </div>
              </div>
              <div class="row">
                  <div class="form-group form-group-default">
                    <label>Description</label>
                    <input type="text"  id="description" name="description" class="form-control"  maxlength="30">
                  </div>
              </div>
              <br>
              <div class="clearfix"></div>
              <button id="cmdsimpan" class="btn btn-complete btn-cons pull-left" type="button">
                <span class="simpanspan">Simpan</span>
              </button>
              <button id="cmdcancel" class="btn btn-default btn-cons pull-left" data-dismiss="modal" type="button">
                <span>Cancel</span>
              </button>
            </form>
        </div>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
</div>
<!-- /.modal-dialog -->

@endsection



@section('style')
<link href="{{asset('assets/plugins/jquery-dynatree/skin/ui.dynatree.css')}}" rel="stylesheet" type="text/css" media="screen" />
<style>
  .checkbox {
      margin-bottom: 1px !important;
      margin-top: 1px !important;
  }

</style>
@endsection

@section('scripts')
<script src="{{asset('assets/plugins/jquery-dynatree/jquery.dynatree.min.js')}}" type="text/javascript"></script>
<script type="text/javascript">

  $(document).ready(function(){   
      $(".dropmodal").select2({
        dropdownParent: $("#modalAddMenu")
      }); 
      
      $('#daftar_menu').hide();
      tampildata();
      daftarmenu();
      
  });

  $( function() {
    $('#modalAddMenu').draggable({
      handle:".modal-header"
    });
  });

  $('#submenu').change(function () {
    if ($(this).is(':checked')) {
        $('#daftar_menu').show();
        ddpermission('#permission_id','');
    } 
  });

  $('#menu').change(function () {
    if ($(this).is(':checked')) {
        $('#daftar_menu').hide();
        ddpermission('#permission_id','menu');
    } 
  });

  function ddpermission(objsupp,filter) {
      $.ajax({
        type: 'GET',
        url: "{{url('dd.permission')}}",
        data:{filter:filter},
        dataType: 'json',
        success: function(s){
            $(objsupp)
                .find('option')
                .remove()
                .end()
                for (var i = 0; i < s.length; i++) {
                    okode=s[i].id;
                    onama=s[i].display_name;
                    $(objsupp)
                      .append($("<option></option>")
                      .attr("value", okode)
                      .text(onama));
                }
                if (s.length == 1) {
                    $(objsupp).val(okode);
                    $(objsupp).text(onama);
                }
        },
        error: function(html){
            alert("Dropdown Permission, Link tidak ada");
        },
    });
  }

  // supaya tooltip keluar di datatables
  $('#detailedTable').on('draw.dt', function () {
      // $('.my-dropdown').dropdown();
      $('.my-tooltip').tooltip({
            trigger: "hover"
      });
  });

  function tampildata(oIsiCari){
    $("#detailedTable thead").empty();
    $(function(){
        var oTable =$("#detailedTable").DataTable({
            processing: true,
            serverSide: true,
            buttons: true,
            scrollX: true,
            dom: 'B<"clear">lfrtip',
              lengthMenu: [
              [ 10, 25, 50, -1 ],
              [ '10', '25', '50', 'All' ]
            ],
            buttons: [
              {
                  extend: 'collection',
                  text: 'Export',
                  buttons: [ 'pdfHtml5', 'csvHtml5', 'copyHtml5', 'excelHtml5' ]
              },'colvis'
            ],
            order: [ 1, 'asc' ],
            bDestroy: true, //pakai ini supaya bisa di load berulang2
            oSearch: {"sSearch":oIsiCari},
            scrollX: true, //pakai ini supaya waktu responsive  bisa di scroll horizontal
            ajax:
            {
              url: "{{ route('list.menu') }}",
            },
            columns: [
              {data:'action',name:'action',orderable:false, searchable:false,title:'-'},
              {data:'id',name:'id',title:'ID' },
              {data:'parent_id',name:'parent_id',title:'Parent ID' },
              {data:'ordering',name:'ordering',title:'Ordering' },
              {data:'title',name:'title',title:'Title' },
              {data:'link',name:'link',title:'Link' },
              {data:'permission',name:'permission',title:'Permission' },
              {data:'iconnya',name:'iconnya',title:'Icon' }
            ]
      });
      var atable = $('#detailedTable').DataTable();
      atable.buttons().container().appendTo( '#btnexport' );

    });
  }

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
  });

  function mdltambah()
  {
    bebersih();
    $('#modalAddMenu').modal('show');
    $('#modalAddMenu').on('shown.bs.modal', function() {
      $('#name').focus();
    });
  }

  function validasidelete(permission_id){
    swal(
    {
      title: "Yakin akan di delete?",
      text: "Menu akan di delete dari database",
      type: "warning",
      showCancelButton: true,
      confirmButtonClass: "btn-danger",
      confirmButtonText: "Hapus",
      closeOnConfirm: false
    },
      function(){
        $.ajax({
          dataType: 'json',
          type:'POST',
          url: "{{ route('delete.menu') }}",
          data:{permission_id:permission_id},
          success: function(data) {
              swal("Selesai!",data.message, "success"); // or data.data['key1'] (?)
              tampildata();
          },
            error: function(data) {
              alert(data.status);
          }
        });
      }
    );
  }

  // function validasiedit(id,name,display_name,description)
  // {   
  //     $('#permission_id').val(id);
  //     $('#name').val(name);
  //     $('#name').attr('disabled','disabled');
  //     $('#display_name').val(display_name);
  //     $('#description').val(description);
  //     $("#chk_other").prop('checked', true);
  //     $('.simpanspan').text('Update');
  //     $("#kelompok_edit").hide();
  //     $('#form_other').show();
  //     $('#modalAddMenu').modal('show');
  //     $('#modalAddMenu').on('shown.bs.modal', function() {
  //       $('#name').focus();
  //     });
  // }

  // function bebersih(){

  //   $("#chk_menu").prop('checked', true);
  //   $("#chk_create").prop('checked', true);
  //   $("#chk_edit").prop('checked', true);
  //   $("#chk_list").prop('checked', true);
  //   $("#chk_delete").prop('checked', true);
  //   $("#chk_other").prop('checked', false);

  //   $('#permission_id').val('');
  //   $('#name').val('');
  //   $('#name').removeAttr('disabled');
  //   $('#display_name').val('');
  //   $('#description').val('');
  //   $('.simpanspan').text('Simpan');
  //   $("#kelompok_edit").show();
  //   $('#form_other').hide();
  //   $('#name').focus();

  // }

  $("#cmdcancel").click(function(e){
      bebersih();
  });

  // $("#cmdsimpan").click(function(e){
  //     e.preventDefault();
  //     var permission_id=$('#permission_id').val();
  //     var name=$('#name').val();
  //     var display_name=$('#display_name').val();
  //     var description=$('#description').val();
  //     var menu,create,edit,list,adelete,other;
      
  //     $('#chk_menu').is(":checked") ?  menu = '1' :  menu = '0';
  //     $('#chk_create').is(":checked") ?  create = '1' :  create = '0';
  //     $('#chk_edit').is(":checked") ?  edit = '1' :  edit = '0';
  //     $('#chk_list').is(":checked") ?  list = '1' :  list = '0';
  //     $('#chk_delete').is(":checked") ?  adelete = '1' :  adelete = '0';
  //     $('#chk_other').is(":checked") ?  other = '1' :  other = '0';

  //     var flag=0;
  //     var pesan='';

  //   if (name ==''){
  //     pesan +="Data mandatory harus diisi...";
  //     flag =1;
  //   }

  //   if ($('#chk_other').val()==1 && display_name=='' ){
  //       pesan +="Data mandatory harus diisi...";
  //       flag =1;
  //   }

  //   if (flag==0) {
  //     $.ajax({
  //       dataType: 'json',
  //       type:'POST',
  //       url: "{{ route('store.permission') }}",
  //       data: { permission_id:permission_id,
  //               name:name,
  //               display_name:display_name,
  //               description:description,
  //               menu:menu,
  //               create:create,
  //               edit:edit,
  //               list:list,
  //               delete:adelete,
  //               other:other
  //             },
  //         success: function(data) {
  //         if (data.status ==1){     
  //           flashmsg2('#status-area',data.message);
  //           bebersih();
  //           tampildata(name);
  //         }else{
  //           swal('Warning ... ', data.message ,'warning');    
  //         }
  //     },
  //       error: function(data) {
  //         swal('Error..',data.status,'error');
  //       }
  //     }); 
  //   }else{
  //     $('#name').focus();
  //     swal('Warning ... ', pesan ,'warning');    
  //   }
  // });

  function daftarmenu(){
    $.ajax({
      url: "{{route('daftar.menu')}}",
      type: "GET",
      dataType : "html",
      success: function( data ) {
          hasil1= data.replace(/^\["|\"]$/, "");
          hasil=hasil1.replace(/"]$/, "");
          new_tree='<div id="drag-tree">'+hasil+'</div>';
          $('.oki').append(new_tree.replace(/[|[\]\\]/g, ''));
          prosesulang();  
      },
      error: function( xhr, status ) {
        alert( "Sorry, there was a problem!" );
      },
        complete: function( xhr, status ) {
      }
    });
  }
  
  function prosesulang(){
    $("#drag-tree").dynatree({
        fx: {
            height: "toggle",
            duration: 200
        }, //Slide down animation
        dnd: {
            preventVoidMoves: true,
            onDragStart: function(node) {
                return true;
            },
            onDragEnter: function(node, sourceNode) {
                if (node.parent !== sourceNode.parent) {
                    return false;
                }
                return ["before", "after"];
            },
            onDrop: function(node, sourceNode, hitMode, ui, draggable) {
                sourceNode.move(node, hitMode);
            }
        }
    });
  }
  
 
  function formatText (icon) {
    return $('<span><i class="fa ' + $(icon.element).data('icon') + '"></i> ' + icon.text + '</span>');
  };

  $('.select2-icon').select2({
      templateSelection: formatText,
      templateResult: formatText,
      dropdownParent: $("#modalAddMenu")
  });
   

</script>

@endsection
