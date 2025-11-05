@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
{{-- @include('partials.alert') --}}
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
                    <div class="form-group col-md-4 d-none"> 
                      <label class="form-label" for="searchGroup">Group</label>
                      <select class="select2 form-control" id="searchGroup" name="searchGroup">
                          <option value="">All</option>
                          @foreach($groups as $val)
                              <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                          @endforeach
                      </select>
                    </div>
                    <div class="form-group col-md-4"> 
                      <label class="form-label" for="searchSupplier">Supplier/Customer</label>
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
                        {{-- @can('article-create')
                        <a href="{{ route('article.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                        @endcan --}}
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
      <h4 class="card-title"> Adjustment Safety Stock</h4>
      <div class="heading-elements">
          <ul class="list-inline mb-0">
              <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
              <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
          </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <form id="frmExcel" name="frmExcel" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="form-row">
              <div class="col-lg-3 col-md-12">
                  <div class="form-group">
                      <div>
                          <input type="file" class="custom-file-input" name="file" id="file" required/>
                          <label class="custom-file-label" for="file">Choose file</label>
                      </div>
                  </div>
              </div>
              <div class="col-lg-6 col-md-12">
                  <button type="button" class="btn btn-primary">
                      <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                      <span class="align-middle d-sm-inline-block d-none" id="uploadExcel">Upload Excel</span>
                  </button>
              </div>
          </div>
          <div class="form-row">
              <div class="col-lg-3 col-md-12">
                  <a href="{{ route('articles.safetyStock.export.excel') }}" class="btn btn-light"><i data-feather="download"></i> Downlod Template</a>
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
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-striped" id="mdlmovetable">
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
  let $body = $('body');
  $(document).ready(function(){
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        let href = $(this).data('href');
        $('#modalConfirmation').attr("action", href);
    });
    $('#frmExcel').on('submit', function(event){
        event.preventDefault();
        $.ajax({
            url:"{{ route('articles.safetyStock.import.excel') }}",
            method:"POST",
            data: new FormData(this),
            dataType:"json",
            contentType:false,
            cache:false,
            processData:false,
            beforeSend:function(){
                $('#uploadExcel').attr('disabled','disabled');
            },
            success:function(data){
                
                $('#file').val(null);
                if(data.status == 1){
                    Swal.fire({
                        title: "Proses validasi...",
                        icon: "warning",
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                    })

                    let timerId = setInterval(() => checkVariable(), 1000);
                    function checkVariable() {
                        if (data.dataDetail.length > 0) {
                            clearInterval(timerId);
                            $(".loading-spinner-container").removeClass("-show");

                            Swal.fire({
                              title: `Yakin akan proses update sejumlah ${data.JumlahData} data?`,
                              showDenyButton: true,
                              // showCancelButton: true,
                              confirmButtonText: 'Yes',
                              denyButtonText: 'Cancel',
                              customClass: {
                                actions: 'my-actions',
                                cancelButton: 'order-1 right-gap',
                                confirmButton: 'order-2',
                                denyButton: 'order-3',
                              },
                            }).then((result) => {
                              if (result.isConfirmed) {
                                updateDataSafetyStock(data.namaFile,'update');
                                // Swal.fire('Saved!', data.namaFile, 'success')
                              } else if (result.isDenied) {
                                updateDataSafetyStock(data.namaFile,'cancel');
                                // Swal.fire('Changes are not saved', '', 'info')
                              }
                            })
                        }
                    }

                }
            },
            error: function(xhr, status, error) {
                let err = JSON.parse(xhr.responseText);
                // Swal.fire('Error..',err.errors.file[0],'error');
                Swal.fire('Error..',err.message,'error');
                $(".loading-spinner-container").removeClass("-show");
            }
        })
    });
  });

  updateDataSafetyStock = (file,type) => {
    $.ajax({
      url:"{{ route('articles.safetyStock.update') }}",
      method:"POST",
      data: {
        file:file,
        type:type
      },
      dataType:"json",
      success:function(data){
          show_msg(data.title, data.message, data.alert);
          
          
          // if(data.status == 1){
          //     Swal.fire('Success..',data.message,'success');
          //     location.reload();
          // } else {
          //     for(let i = 0; i < data.message.length; i++) {
          //         show_msg(data.title, data.message[i], data.alert);
          //     }
          //     swal.fire("warning",data.pesan,"warning");
          //     $(".loading-spinner-container").removeClass("-show");
          // }
      },
      error: function(xhr, status, error) {
          Swal.fire('Error..','Error','error');
          // $(".loading-spinner-container").removeClass("-show");
      }
    })
  }

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
      let name = $("#searchName").val();
      let code = $("#seachCode").val();
      let group = $("#searchGroup").val();
      let supp = $("#searchSupplier").val();
      let type = $("#searchType").val();
      showList(name,code,group,supp,type);
  });

  $("#btnSearch").click(function(e){
      let name = $("#searchName").val();
      let code = $("#seachCode").val();
      let group = $("#searchGroup").val();
      let supp = $("#searchSupplier").val();
      let type = $("#searchType").val();
      showList(name,code,group,supp,type);      
  });

  const showList = (name,code,group,supp,type) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('article.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,11,12],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [ 5,7,8,9 ] },
      ],
      dataSearch:  {
        name:name,
        code:code,
        group:group,
        supp:supp,
        type:type
      },
      orderColumn:[[ 2, 'asc' ]],
      excelFileName:'article'
    });
  }

  const movement = (artCode,artikelAlternativeCode,artDesc) => {
    $('#mdlmovement').modal('show');
    $('#mdlartikel').text(' | '+artikelAlternativeCode+' - '+artDesc);

    if ($('#mdlmovetable tr').length >0){
        let table= $('#mdlmovetable').DataTable();
        table.destroy();
        $('#mdlmovetable tbody > tr').remove();
        $("#mdlmovetable thead > tr").remove();
    }
    showDataTables({
      tableId:"mdlmovetable",
      route:"{{ route('article.movement') }}",
      kolom:{!! $kolomMovement !!},
      arrColPrint:[0,1,2,3,4,5,6,7],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [4,5,6,7]},
      ],
      dataSearch:  {
        articleCode:artCode
      },
      orderColumn:[[0,'asc'],[2,'asc']],
      excelFileName:'movement'+artDesc
    });
  }

  $("#uploadExcel").click(function(){
      if (!$("#frmExcel")[0].checkValidity()){
          $("#frmExcel").submit();
      }else{
          $(".loading-spinner-container").addClass("-show");
          $("#uploadExcel").attr('disabled','disabled');
          $('.disabled-el').removeAttr('disabled');
          $("#frmExcel").submit();
      }
  });

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
