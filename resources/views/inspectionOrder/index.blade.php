@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="inspectionOrder-index">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form class="needs-validation" novalidate>
              <div class="form-row">
                  <div class="col-md-4">
                      <div class="form-group">
                      <label for="searchOrderNo">Order No</label>
                      <input type="text" class="form-control" id="searchOrderNo" name="searchOrderNo" placeholder="" />
                      </div>
                  </div>
                  <div class="col-md-4">
                      <div class="form-group">
                      <label for="searchArticle">Article</label>
                      <input type="text" class="form-control" id="searchArticle" name="searchArticle" placeholder="Article code" />
                      </div>
                  </div>
              </div>
              <div class="form-row">
                  <div class="col-12">
                      <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch">Search</button>
                      <a href="{{ route('inspectionOrder.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                  </div>
              </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<section id="table-inspectionOrder">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title"> @yield('title') List</h4>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <div class="row">
            <div class="col-sm-12">
              <div class="card-datatable table-responsive pt-0">
                <table id="detailedTable" class="table">
                  <thead class="thead-light"></thead>
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
@section('scripts')
<script type="text/javascript">
  $(document).ready(function(){
    let href;
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        href = $(this).data('href');
        $('#modalConfirmation').attr("action", href);
    });
    showList();
  });

  $("#btnSearch").click(function(e){
      showList($("#searchOrderNo").val(), $("#searchArticle").val());
  });

  function showList(orderNo, articleCode){
    $(function(){
      let oTable = $("#detailedTable").DataTable({
        ajax: { url:'{{ route("inspectionOrder.list")}}', data: { orderNo: orderNo, articleCode: articleCode } },
        processing: true,
        serverSide: true,
        bDestroy: true,
        order: [[ 1, 'desc' ]],
        columns: [
            { data: 'action', name: 'action', title:'Action', orderable: false, searchable: false },
            { data: 'order_no', name: 'order_no', title:'Order No' },
            { data: 'inspection_date', name: 'inspection_date', title:'Date' },
            { data: 'post_name', name: 'post_name', title:'Inspection Post' },
            { data: 'article_alternative_code', name: 'article_alternative_code', title:'Article' },
            { data: 'pass_rate', name: 'pass_rate', title:'Pass Rate', className: 'text-right' },
            { data: 'pass_through_rate', name: 'pass_through_rate', title:'Pass Through', className: 'text-right' },
            { data: 'result', name: 'result', title:'Result' },
            { data: 'status', name: 'status', title:'Status' }
        ],
      });
    });
  }

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection
