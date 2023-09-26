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
            {{-- <div class="form-row">
              <div class="col-md-3 form-group">
                <label for="dnDate">Delivery Date</label>
                <input type="text" id="dnDate" name="dnDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-6"> 
                <label class="form-label" for="searchCustomer">Customer</label>
                <select class="select2 form-control" id="searchCustomer" name="searchCustomer">
                    <option value="">All</option>
                    @foreach($customers as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
            </div> --}}
            <div class="form-row">
              {{-- <div class="form-group col-md-3 d-none"> 
                <label for="searchDn">Delivery Number</label>
                <input type="text" class="form-control text-uppercase" id="searchDn" name="searchDn" placeholder=""  />
              </div> --}}
              <div class="form-group col-md-9"> 
                <label for="searchSo">SO Number</label> <small class="text-muted">Daftar So Yang sudah di buat DN</small>
                <select class="select2 form-control" id="searchSo" name="searchSo">
                  <option value="">All</option>
                  @foreach($salesOrders as $val)
                      <option value="{{ $val->so_code }}">{{ $val->so_code }} | {{ $val->nama }}  | {{ $val->po_number }}</option>
                  @endforeach
                </select>
              </div>
              {{-- <div class="form-group col-md-2 d-none"> 
                <label class="form-label" for="searchStatus">Delivery Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}">{{ $val }}</option>
                    @endforeach
                </select>
              </div> --}}
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="cmdPrint" name="cmdPrint">Print</button>
                    {{-- <a href="{{ route('stockTake.export') }}" class="btn btn-info"><i class="fa fa-download"></i> Downlod Excel </a> --}}
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection
@section('styles')
@endsection
@section('scripts')
<script type="text/javascript">
  let searchSo = $("#searchSo");
  // let searchDn = $("#searchDn");
  // let searchCustomer = $("#searchCustomer"); 
  // let searchStatus = $("#searchStatus");
  // let dnDate = $("#dnDate");

  $("#cmdPrint").click(function(){
    let id = searchSo.val();
    let url = "{{ route('delivery.print.so', ['so_code'=>':id']) }}";
    url = url.replace('%3Aid', id);
    window.open(url, '_blank');
  });

  $(document).ready(function(){    
  
  });
 
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
