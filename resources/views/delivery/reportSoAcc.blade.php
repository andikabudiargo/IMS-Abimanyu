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
        <form class="needs-validation" novalidate autocomplete="off">
            <div class="form-row">
              <div class="col-md-3 form-group">
                <label for="soDate">Delivery Date</label>
                <input type="text" id="soDate" name="soDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-6"> 
                <label class="form-label" for="searchCustomer">Customer</label>
                <select class="select2 form-control" id="searchCustomer" name="searchCustomer">
                    <option value=""></option>
                    @foreach($customers as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-9"> 
                <label for="searchSo">SO Number</label> <small class="text-muted">Daftar So Yang sudah di buat DN</small>
                <select class="select2 form-control" id="searchSo" name="searchSo" multiple>
                  <option value=""></option>
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="cmdPrint" name="cmdPrint">Print</button>
                    <button type="button" class="btn btn-info" id ="cmdExport" name="cmdExport"><i class="fa fa-download"></i> Downlod Excel</button>
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
  let searchSoDate = $("#soDate");
  let searchCustomer = $("#searchCustomer"); 
  let rangePickr = $('.flatpickr-range');

  $(document).ready(function(){    
  
  });

  searchCustomer.change(function(){
    searchSo.val("");
    let aSearchSoDate = searchSoDate.val();
    let aSearchCustomer = $(this).val();
    if(aSearchCustomer || aSearchSoDate){
      searchSo.empty();
      searchSo.attr('disabled','disabled');
      fSearchSo('searchSo',aSearchCustomer,aSearchSoDate);
    }else{
      searchSo.empty();
      searchSo.attr('disabled','disabled');
    }
  })

  function fSearchSo(obj,customer, soDate) {
    if(customer || soDate){
        $.ajax({
            url:"{{ route('delivery.list.so.dn') }}",
            method:"GET",
            data:{
                customer:customer,
                soDate:soDate
            },
            success:function(result){
                if(result){
                    $('#'+obj).html(result);
                    searchSo.removeAttr('disabled');
                }
            },
            error: function (response) {
                //Error here
                Swal.fire("Warning","Get list SO failed","warning");
            }
        })
    }
  }

  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range',
      onClose: function(selectedDates, dateStr, instance) {
        let aSearchSoDate = dateStr;
        let aSearchCustomer = searchCustomer.val();
        if(aSearchCustomer || aSearchSoDate){
          searchSo.empty();
          searchSo.attr('disabled','disabled');
          fSearchSo('searchSo',aSearchCustomer,aSearchSoDate);
        }else{
          searchSo.empty();
          searchSo.attr('disabled','disabled');
        }
      }
    });
  }

  $("#cmdPrint").click(function(){
    let id = searchSo.val();
    if(id){
      let url = "{{ route('delivery.print.so', ['so_code'=>':id']) }}";
      url = url.replace('%3Aid', id);
      window.open(url, '_blank');
    }
  });

  $("#cmdExport").click(function(){
    let id = searchSo.val();
    if(id){
      let url = "{{ route('delivery.export.so', ['so_code'=>':id']) }}";
      url = url.replace('%3Aid', id);
      window.location.href = url;
    }
    // window.open(url, '_blank');

  });
 
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
