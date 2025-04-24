@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
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
              <div class="form-group col-md-4"> 
                <label for="searchBom">Bom Number</label>
                <input type="text" class="form-control text-uppercase" id="searchBom" name="searchBom" placeholder=""  />
              </div>
              <div class="form-group col-md-4">
                <label class="form-label" for="articleCode">Article FG</label>
                <select class="select2 form-control" id="articleCode" name="articleCode">
                    <option value="">All</option>
                    @foreach($articles as $val)
                        <option value="{{ $val->article_code }}" >{{ $val->article_alternative_code }} - {{ $val->article_desc }}</option>
                    @endforeach
                </select>
              </div>
              <div class="form-group col-md-4">
                <label class="form-label" for="articleCodeRm">Article RM</label>
                <select class="select2 form-control" id="articleCodeRm" name="articleCodeRm">
                    <option value="">All</option>
                    @foreach($articlesRm as $val)
                        <option value="{{ $val->article_code }}" >{{ $val->article_alternative_code }} - {{ $val->article_desc }}</option>
                    @endforeach
                </select>
              </div>
              <div class="form-group col-md-4">
                <label class="form-label" for="articleMaterial">Article Material</label>
                <select class="select2 form-control" id="articleMaterial" name="articleMaterial">
                    <option value="">All</option>
                    @foreach($materials as $val)
                        <option value="{{ $val->article_code }}" >{{ $val->article_alternative_code }} - {{ $val->article_desc }}</option>
                    @endforeach
                </select>
              </div>
              {{-- <div class="form-group col-md-3">
                <label class="form-label" for="status">Status</label>
                <select class="select2 form-control" id="status" name="status">
                    <option value="">All</option>
                    @foreach($status as $key=>$val)
                        <option value="{{ $key }}" >{{ $val }}</option>
                    @endforeach
                </select>
              </div> --}}
            </div>
            <div class="form-row">
              <div class="col-12"> 
                  <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
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
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  let searchBom = $("#searchBom");
  let articleCode = $("#articleCode");
  let articleMaterial = $("#articleMaterial");
  let articleCodeRm = $("#articleCodeRm");

  $(document).ready(function(){    

  });

  $("#btnSearch").click(function(e){
    showList(searchBom.val(),articleCode.val(),articleMaterial.val(),articleCodeRm.val());
  });

  const showList = (searchBom,articleCode,articleMaterial,articleCodeRm) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('bom.report.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16],
      columnDefs :[
        { width: '5%', targets: 0 },
        {
            targets: [ 8,11 ],
            render: $.fn.dataTable.render.number(',', '.', 4,''),
            className: "text-right"
        },
      ],
      type:'POST',
      dataSearch:  {
        searchBom:searchBom,
        articleCode:articleCode,
        articleMaterial:articleMaterial,
        articleCodeRm:articleCodeRm
      },
      orderColumn:[[ 1, 'desc' ]],
      excelFileName:'detail_bom',
      
    });
  }



  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
