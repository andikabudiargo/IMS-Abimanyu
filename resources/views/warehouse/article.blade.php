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
                      <label class="form-label" for="searchSupplier">Supplier/customer</label>
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
                    <div class="col-md-4 col-12 mb-1">
                      <label for="searchQty">QTY</label>
                      {{-- <fieldset> --}}
                          <div class="input-group">
                              <div class="input-group-prepend">
                                <select class="form-control" id="searchOperator" name="searchOperator">
                                  <option value="">All</option>
                                  <option value=">">></option>
                                  <option value="<"><</option>
                                  <option value="=">=</option>
                                </select>
                              </div>
                              <input type="text" class="form-control numeral-mask-digit text-right" id="searchQty" name="searchQty" />
                          </div>
                      {{-- </fieldset> --}}
                  </div>
                </div>
                <div class="form-row">
                    <div class="col-12"> 
                        <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                        <a href="{{ route('stockTake.export') }}" class="btn btn-info"><i class="fa fa-download"></i> Downlod Stock</a>
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

  let name = $("#searchName");
  let code = $("#seachCode");
  let group = $("#searchGroup");
  let supp = $("#searchSupplier");
  let type = $("#searchType");
  let opr = $("#searchOperator");
  let qty = $("#searchQty");


  $(document).ready(function(){
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        let href = $(this).data('href');
        $('#modalConfirmation').attr("action", href);
    });
    mask_thousand_digit(numberOfDecimalDigit);
  });

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
    showList(name.val(),code.val(),group.val(),supp.val(),type.val(),opr.val(),qty.val());
  });

  $("#btnSearch").click(function(e){
      showList(name.val(),code.val(),group.val(),supp.val(),type.val(),opr.val(),qty.val());
  });


  opr.change(function(e){
    qty.focus();
  });

  const showList = (name,code,group,supp,type,opr,qty) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('warehouse.article.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [6,8,9]},
      ],
      dataSearch:  {
        name:name,
        code:code,
        group:group,
        supp:supp,
        type:type,
        opr:opr,
        qty:qty
      },
      orderColumn:[[ 1, 'asc' ],[ 2, 'asc' ]],
      excelFileName:'article_stock'
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
      arrColPrint:[0,1,2,3,4,5,6,7,8],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [5,6,7] },
      ],
      dataSearch:  {
        articleCode:artCode
      },
      orderColumn:[[0,'asc'],[1,'asc']],
      excelFileName:'movement'+artDesc
    });
  }

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
