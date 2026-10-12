@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="inspectionRules-index">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form class="needs-validation" novalidate>
              <div class="form-row">
                  <div class="col-md-4">
                      <div class="form-group">
                      <label for="searchArticle">Article Code</label>
                      <input type="text" class="form-control" id="searchArticle" name="searchArticle" placeholder="" />
                      </div>
                  </div>
              </div>
              <div class="form-row">
                  <div class="col-12">
                      <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch">Search</button>
                      @can('inspection-rule-create')
                      <a href="{{ route('inspectionRule.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                      @endcan
                  </div>
              </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<section id="table-inspectionRules">
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
      showList($("#searchArticle").val());
  });

  function showList(articleCode){
    $(function(){
      let oTable = $("#detailedTable").DataTable({
        ajax: { url:'{{ route("inspectionRule.list")}}', data: { articleCode: articleCode } },
        processing: true,
        serverSide: true,
        bDestroy: true,
        order: [[ 1, 'asc' ]],
        columns: [
            { data: 'action', name: 'action', title:'Action', orderable: false, searchable: false },
            { data: 'post_name', name: 'post_name', title:'Inspection Post' },
            { data: 'article_code', name: 'article_code', title:'Article Code' },
            { data: 'target_pass_rate', name: 'target_pass_rate', title:'Target Pass Rate (%)' },
            { data: 'target_pass_through_rate', name: 'target_pass_through_rate', title:'Target Pass Through (%)' },
            { data: 'status', name: 'status', title:'Status' }
        ],
      });
    });
  }

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection
