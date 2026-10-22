@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="balanceSheet-index">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form class="needs-validation" novalidate>
            <div class="form-row">
              <div class="col-md-4">
                <div class="form-group">
                  <label for="searchNrcNumber">Neraca Number</label>
                  <input type="text" class="form-control text-uppercase" id="searchNrcNumber" name="searchNrcNumber">
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
              <div class="col-md-4">
                <div class="form-group">
                  <label for="searchMonth">Month</label>
                  <select class="form-control" id="searchMonth" name="searchMonth">
                    <option value="">-- Semua --</option>
                    @foreach(range(1,12) as $m)
                      <option value="{{ $m }}">{{ \DateTime::createFromFormat('n',$m)->format('F') }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
            </div>
            <div class="form-row">
              <div class="col-12">
                <button type="button" class="btn btn-primary" id="btnSearch" name="btnSearch">Search</button>
                <a href="{{ route('balanceSheet.saved.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                <button type="button" class="btn btn-outline-secondary" id="btnYearSummary"><i class="fa fa-table"></i> Ringkasan Tahunan</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<section id="table-balanceSheet">
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
    showList($("#searchNrcNumber").val(), $("#searchYear").val(), $("#searchMonth").val());
  });

  $("#btnYearSummary").click(function(e){
    const year = $("#searchYear").val();
    if(!year){
      swal.fire('Warning', 'Isi Year dulu untuk lihat ringkasan tahunan', 'warning');
      return;
    }
    window.location.href = "{{ route('balanceSheet.saved.yearSummary') }}?year=" + encodeURIComponent(year);
  });

  const showList = (nrcNumber, year, month) => {
    showDataTables({
      tableId: "detailedTable",
      route: "{{ route('balanceSheet.list') }}",
      kolom: {!! $kolom !!},
      orderColumn: [[ 1, 'desc' ]],
      dataSearch: {
        nrcNumber: nrcNumber,
        year: year,
        month: month
      },
      excelFileName: 'neraca'
    });
  }
</script>
@endsection
