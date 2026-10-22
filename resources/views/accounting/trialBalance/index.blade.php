@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="trialBalance-index">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form class="needs-validation" novalidate>
            <div class="form-row">
              <div class="col-md-4">
                <div class="form-group">
                  <label for="searchTbNumber">TB Number</label>
                  <input type="text" class="form-control text-uppercase" id="searchTbNumber" name="searchTbNumber">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label for="searchYear">Year</label>
                  <select class="form-control" id="searchYear" name="searchYear">
                    <option value="">-- Semua --</option>
                    @for($y = date('Y'); $y >= 2023; $y--)
                      <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                  </select>
                </div>
              </div>
            </div>
            <div class="form-row">
              <div class="col-12">
                <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch">Search</button>
                <a href="{{ route('trialBalance.saved.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<section id="table-trialBalance">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">List @yield('title')</h4>
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
@section('scripts')
<script type="text/javascript">
  $(document).ready(function(){
    showList();

    $(document).on('click', '#deleteButton', function(event) {
      event.preventDefault();
      $('#modalConfirmation').attr('action', $(this).data('href'));
    });
  });

  $('a[data-action="reload"]').on('click', function () {
    showList();
  });

  $("#btnSearch").click(function(e){
    showList($("#searchTbNumber").val(), $("#searchYear").val());
  });

  const showList = (tbNumber, year) => {
    showDataTables({
      tableId: "detailedTable",
      route: "{{ route('trialBalance.list') }}",
      kolom: {!! $kolom !!},
      orderColumn: [[ 1, 'desc' ]],
      dataSearch: {
        tbNumber: tbNumber,
        year: year
      },
      excelFileName: 'trial_balance'
    });
  }
</script>
@endsection
