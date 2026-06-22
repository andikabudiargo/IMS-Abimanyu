@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

{{-- FILTER --}}
<section id="article-index">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <form class="needs-validation" novalidate>
                <div class="form-row">
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
                    <div class="form-group col-md-4"> 
                      <label class="form-label" for="searchStatus">Status</label>
                      <select class="select2 form-control" id="searchStatus" name="searchStatus">
                          <option value="">All</option>
                          <option value="1">Requested</option>
                          <option value="2">Approved</option>
                          <option value="3">Submitted</option>
                      </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12"> 
                        <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch">Search</button>
                        @can('article-request-create')
                          <a href="{{ route('article.request.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                        @endcan
                    </div>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</section>

{{-- 4 STAT CARDS --}}
<section id="section-stats">
    <div class="row">

        {{-- Total --}}
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card stat-card cursor-pointer" data-filter="">
                <div class="card-body d-flex align-items-center justify-content-between py-1">
                    <div>
                        <h6 class="text-muted mb-25">Total Request</h6>
                        <h2 class="font-weight-bolder mb-0" id="statTotal">-</h2>
                    </div>
                    <div class="avatar bg-light-info p-50">
                        <span class="avatar-content"><i data-feather="layers" class="font-medium-5"></i></span>
                    </div>
                </div>
                <div class="card-footer py-50 bg-light-info" style="border-radius:0 0 .357rem .357rem">
                    <small class="text-info">Klik untuk tampilkan semua</small>
                </div>
            </div>
        </div>

        {{-- Requested --}}
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card stat-card cursor-pointer" data-filter="1">
                <div class="card-body d-flex align-items-center justify-content-between py-1">
                    <div>
                        <h6 class="text-muted mb-25">Requested</h6>
                        <h2 class="font-weight-bolder mb-0" id="statRequested">-</h2>
                    </div>
                    <div class="avatar bg-light-success p-50">
                        <span class="avatar-content"><i data-feather="edit-3" class="font-medium-5"></i></span>
                    </div>
                </div>
                <div class="card-footer py-50 bg-light-success" style="border-radius:0 0 .357rem .357rem">
                    <small class="text-success">Klik untuk filter Requested</small>
                </div>
            </div>
        </div>

        {{-- Approved --}}
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card stat-card cursor-pointer" data-filter="2">
                <div class="card-body d-flex align-items-center justify-content-between py-1">
                    <div>
                        <h6 class="text-muted mb-25">Approved</h6>
                        <h2 class="font-weight-bolder mb-0" id="statApproved">-</h2>
                    </div>
                    <div class="avatar bg-light-primary p-50">
                        <span class="avatar-content"><i data-feather="check-circle" class="font-medium-5"></i></span>
                    </div>
                </div>
                <div class="card-footer py-50 bg-light-primary" style="border-radius:0 0 .357rem .357rem">
                    <small class="text-primary">Klik untuk filter Approved</small>
                </div>
            </div>
        </div>

        {{-- Submitted --}}
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card stat-card cursor-pointer" data-filter="3">
                <div class="card-body d-flex align-items-center justify-content-between py-1">
                    <div>
                        <h6 class="text-muted mb-25">Submitted</h6>
                        <h2 class="font-weight-bolder mb-0" id="statSubmitted">-</h2>
                    </div>
                    <div class="avatar bg-light-danger p-50">
                        <span class="avatar-content"><i data-feather="send" class="font-medium-5"></i></span>
                    </div>
                </div>
                <div class="card-footer py-50 bg-light-danger" style="border-radius:0 0 .357rem .357rem">
                    <small class="text-danger">Klik untuk filter Submitted</small>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- TABLE --}}
<section id="table-article">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">@yield('title') List</h4>
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

@section('styles')
<style>
  .stat-card {
      transition: transform .15s ease, box-shadow .15s ease;
      border: 2px solid transparent;
  }
  .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(0,0,0,.12) !important;
  }
  .stat-card.active-filter { border: 2px solid #7367f0; }
  .cursor-pointer { cursor: pointer; }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
  let $body = $('body');

  // state filter status dari card
  let activeStatusFilter = '';

  // load angka cards
  function loadStats() {
      $.get("{{ route('article.request.stats') }}", function(res) {
          $('#statTotal').text(res.total);
          $('#statRequested').text(res.requested);
          $('#statApproved').text(res.approved);
          $('#statSubmitted').text(res.submitted);
          feather.replace();
      });
  }

  $(document).ready(function(){
    $(document).on('click', '#deleteButton', function(event) {
        event.preventDefault();
        let href = $(this).data('href');
        $('#modalConfirmation').attr("action", href);        
    });

    loadStats();
    triggerSearch();
  });

  // klik card → filter status
  $('.stat-card').on('click', function() {
      $('.stat-card').removeClass('active-filter');
      $(this).addClass('active-filter');

      activeStatusFilter = $(this).data('filter').toString();

      // sinkronkan dropdown status biar konsisten
      $('#searchStatus').val(activeStatusFilter).trigger('change');

      triggerSearch();
  });

  // reload di card table
  $('a[data-action="reload"]').on('click', function () {
      triggerSearch();
  });

  // search button → pakai nilai dropdown status
  $("#btnSearch").click(function(e){
      $('.stat-card').removeClass('active-filter');
      activeStatusFilter = $("#searchStatus").val() || '';
      triggerSearch();
  });

  function triggerSearch() {
      let name   = $("#searchName").val();
      let group  = $("#searchGroup").val();
      let supp   = $("#searchSupplier").val();
      let type   = $("#searchType").val();
      let status = activeStatusFilter !== '' ? activeStatusFilter : ($("#searchStatus").val() || '');
      showList(name, status, group, supp, type);
  }

  const showList = (name,status,group,supp,type) => {
    if ($('#detailedTable tr').length > 0){
        let table = $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('article.request.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [ 6,8,9,10 ] },
      ],
      dataSearch:  {
        name:name,
        status:status,
        group:group,
        supp:supp,
        type:type
      },
      orderColumn:[[ 13, 'desc' ]],   // created_at terbaru paling atas
      excelFileName:'article_request'
    });
  }

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });
</script>
@endsection