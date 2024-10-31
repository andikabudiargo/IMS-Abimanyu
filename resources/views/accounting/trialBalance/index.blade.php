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
        <form id="frmFilter" name="frmFilter" method="get" autocomplete="off">
            <div class="form-row">
              <div class="form-group col-md-3">
                <label class="form-label" for="bsDate">Date *</label>
                <input type="text" id="bsDate" name="bsDate" class="form-control  flatpickr-range" placeholder="DD-MM-YYYY" required/>
              </div>
              <div class="form-group col-md-2">
                <label class="form-label" for="period1">Period Awal</label>
                <select class="select2 form-control" id="period1" name="period1" >
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
              </div>
              <div class="form-group col-md-2">
                <label class="form-label" for="period2">Period Akhir</label>
                <select class="select2 form-control" id="period2" name="period2" >
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @if($start == false)
                    <button type="button" class="btn btn-success" id ="btnPrint" name="btnPrint">Print</button>
                    {{-- <button type="button" class="btn btn-secondary" id ="btnExport" name="btnExport">Export to Excel</button> --}}
                    @endif
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
  <div id="tampilData1">
    @if($start == false)
    <div class="row">
      <div class="col-xl-12 col-md-12 col-12">
        <div class="row match-height">
          <div class="col-lg-3 col-md-3">
              <div class="card">
                <div class="card-body">
                  <div class="card-header flex-column align-items-start pb-0">
                      <div class="avatar bg-light-success p-50 m-0">
                          <div class="avatar-content">
                              <i data-feather="bar-chart" class="font-medium-5"></i>
                          </div>
                      </div>
                      <h2 class="font-weight-bolder mt-1">{{ $saldoAwal }}</h2>
                      <p class="card-text">Saldo Awal</p>
                  </div>
                </div>
              </div>
          </div>
          <div class="col-lg-3 col-md-3">
              <div class="card card-tiny-line-stats">
                <div class="card-body">
                  <div class="card-header flex-column align-items-start pb-0">
                      <div class="avatar bg-light-primary p-50 m-0">
                          <div class="avatar-content">
                              <i data-feather="bar-chart" class="font-medium-5"></i>
                          </div>
                      </div>
                      <h2 class="font-weight-bolder mt-1">{{ $saldoAkhir }}</h2>
                      <p class="card-text">Saldo Akhir</p>
                  </div>
                </div>
              </div>
          </div>
          <div class="col-lg-3 col-md-3">
            <div class="card card-tiny-line-stats">
              <div class="card-body">
                <div class="card-header flex-column align-items-start pb-0">
                    <div class="avatar bg-light-warning p-50 m-0">
                        <div class="avatar-content">
                            <i data-feather="bar-chart" class="font-medium-5"></i>
                        </div>
                    </div>
                    <h2 class="font-weight-bolder mt-1">{{ $net }}</h2>
                    <p class="card-text">Net</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-3">
            <div class="card card-tiny-line-stats">
              <div class="card-body">
                <div class="card-header flex-column align-items-start pb-0">
                    <div class="avatar bg-light-secondary p-50 m-0">
                        <div class="avatar-content">
                            <i data-feather="bar-chart" class="font-medium-5"></i>
                        </div>
                    </div>
                    <h2 class="font-weight-bolder mt-1">{{ $pergerakanKas }}</h2>
                    <p class="card-text">Pergerakan Kas</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    @endif
    {{-- End Data Summary --}}
  </div>
</section>
<div id="tampilData2">
  @if($start == false)
    <section id="table-detail">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title"> @yield('title')</h4>
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
              <div class="col-md-12">
                <div class="card">
                  <div class="card-body" >
                    <button type="button" class="btn btn-primary" id="showListButton" data-text="Show All">Show All</button>
                    <br>
                    <br>
                    <div class="tableFixHead">
                      <table class="table table-condensed">
                        <thead>
                          <tr>
                            <th rowspan="2" class="text-center" style="vertical-align: middle" width="30%">Akun</th>
                            <th colspan="2" class="text-center">Saldo Awal</th>
                            <th colspan="2" class="text-center">Pergerakan</th>
                            <th colspan="2" class="text-center">Saldo Akhir</th>
                          </tr>
                          <tr>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Credit</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Credit</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Credit</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($groups as $keyGroup => $group)
                              <tr class="parent" id ="row{{$keyGroup}}">
                                <td colspan="7" class="judul-row{{$keyGroup}} doraemon">
                                  {{ $group ->group_name}}
                                </td>
                              </tr>
                              <tr>
                                @foreach($details as $keyDetail => $detail)
                                  @if($detail->group_data== $group->group_data)
                                    <tr class="child-row{{$keyGroup}} oki" style="display: none;">
                                      <td width="40%">{{ $detail->sub_group_name }} ({{ $detail->account }})</td>
                                      <td width="10%" class="text-left">Rp.{{ number_format($detail->opening_balance_debit) }} </td>
                                      <td width="10%" class="text-left">Rp.{{ number_format($detail->opening_balance_credit) }} </td>
                                      <td width="10%" class="text-left">Rp.{{ number_format($detail->pergerakan_debit) }} </td>
                                      <td width="10%" class="text-left">Rp.{{ number_format($detail->pergerakan_credit) }} </td>
                                      <td width="10%" class="text-left">Rp.{{ number_format($detail->saldo_akhir_debit) }} </td>
                                      <td width="10%" class="text-left">Rp.{{ number_format($detail->saldo_akhir_credit) }} </td>
                                    </tr>
                                  @endif
                                @endforeach
                          @endforeach
                        </tbody>
                        <tfoot>
                          <tr>
                            <td width="40%"> Grand Total</td>
                            <td width="10%" class="text-left">Rp.{{ number_format($total[0]->opening_balance_debit) }} </td>
                            <td width="10%" class="text-left">Rp.{{ number_format($total[0]->opening_balance_credit) }} </td>
                            <td width="10%" class="text-left">Rp.{{ number_format($total[0]->pergerakan_debit) }} </td>
                            <td width="10%" class="text-left">Rp.{{ number_format($total[0]->pergerakan_credit) }} </td>
                            <td width="10%" class="text-left">Rp.{{ number_format($total[0]->saldo_akhir_debit) }} </td>
                            <td width="10%" class="text-left">Rp.{{ number_format($total[0]->saldo_akhir_credit) }} </td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div> 
          </div>
        </div>
      </div>
    </section>
  @endif
</div>
@endsection
@section('styles')
<style>
  
  .hiddenRow {
      padding: 0 !important;
  }

  .headerData{
    cursor: pointer;
  }

  .hCollapsed::before {
    content: "+ ";
  }

  .hExpanded::before {
    content: "- ";
  }

  .parent {
    cursor: pointer;
  }

  .tableFixHead { 
    overflow: auto; 
    max-height: 50vh; 
  }

  .tableFixHead thead tfoot {
    /* position: sticky;
    /* top: 0; */
    background: #eee; */
  }

  .tableFixHead thead {
    position: sticky;
    top: 0;
    background: #eee;
  }

  .tableFixHead tfoot {
    position: sticky;
    bottom: 0;
    background: #eee;
  }

</style>
@endsection
@section('scripts')
<script type="text/javascript">   
  let currentDate = todayDate('dd-mm-yyyy');  
  $(".loading-spinner-container").addClass("-show");
  $(document).ready(function(){  
    validateFormToast("frmFilter");    

      // $('.oki').toggle();  
      $('.doraemon').addClass('hCollapsed');
      
      $('tr.parent')  
      .css("cursor", "pointer")
      .click(function () {  
          $(this).siblings('.child-' + this.id).toggle();  
          if ($('.judul-' + this.id).hasClass('hCollapsed')) {
            $('.judul-' + this.id).removeClass('hCollapsed').addClass('hExpanded');
          } else {
            $('.judul-' + this.id).removeClass('hExpanded').addClass('hCollapsed');
          }
      });  

      // $('tr[@class^=child-]').hide().children('td');  
  });

  const rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

    //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
    let searchName = $("#searchName").val();
    showList(searchNoAsset,searchName);
  });

  $("#btnSearch").click(function(e){
    e.preventDefault();
    if (!$("#frmFilter")[0].checkValidity()){
        $("#frmFilter").submit();
    }else{
        $(".loading-spinner-container").addClass("-show");
        href = "{{ route('trialBalance.index') }}";
        $('#frmFilter').attr("action", href);
        $('.disabled-el').removeAttr('disabled');
        $('#tampilData1').hide();
        $('#tampilData2').hide();
        $("#btnPrint").hide();
        $("#btnExport").hide();
        $("#frmFilter").submit();
    }
  });

  $("#btnPrint").click(function(e){

    let currentURL = window.location.href.slice(window.location.href.indexOf('?') + 1);
    let period1 = currentURL.split('&')[1].split('=')[1];
    let period2 = currentURL.split('&')[2].split('=')[1];
    let oki = currentURL.split('=');
    let iko = oki[1].split('&')[0].replace(/\+/g,'');

    if((oki[0] === 'bsDate') && (oki.length> 0)){
      e.preventDefault();
      if (!$("#frmFilter")[0].checkValidity()){
          $("#frmFilter").submit();
      }else{
          let id = iko;
          let uPeriod1 = period1;
          let uPeriod2 = period2;

          let url = "{{ route('trialBalance.print', ['bsDate'=>':id','period1'=>':uPeriod1','period2'=>':uPeriod2']) }}";
          url = url.replace('%3Aid', id);
          url = url.replace('%3AuPeriod1', uPeriod1);
          url = url.replace('%3AuPeriod2', uPeriod2);
          url = url.replace("&amp;", "&").replace("&amp;", "&"); 

          window.open(url, '_blank');
      }
    }else{
      swal.fire("Warning", "Isi periode dulu, lalu tekan search ....","warning");
    }

  });

  $("#btnExport").click(function(e){

    let currentURL = window.location.href.slice(window.location.href.indexOf('?') + 1);
    let period1 = currentURL.split('&')[1].split('=')[1];
    let period2 = currentURL.split('&')[2].split('=')[1];
    let oki = currentURL.split('=');
    let iko = oki[1].split('&')[0].replace(/\+/g,'');

    if((oki[0] === 'bsDate') && (oki.length> 0)){
      e.preventDefault();
      if (!$("#frmFilter")[0].checkValidity()){
          $("#frmFilter").submit();
      }else{
          let id = iko;
          let uPeriod1 = period1;
          let uPeriod2 = period2;
          let url = "{{ route('labaRugi.export.excel', ['bsDate'=>':id','period1'=>':uPeriod1','period2'=>':uPeriod2']) }}";
          
          url = url.replace('%3Aid', id);
          url = url.replace('%3AuPeriod1', uPeriod1);
          url = url.replace('%3AuPeriod2', uPeriod2);
          url = url.replace("&amp;", "&").replace("&amp;", "&");          
          window.open(url);
      }
    }else{
      swal.fire("Warning", "Isi periode dulu, lalu tekan search ....","warning");
    }

  });

  $('#showListButton').on('click', function () {
    let textButton = $(this).data('text');
    // $('.oki').toggle();
    if(textButton == 'Show All'){
      $(this).data('text', 'Hide All');
      $(this).text("Hide All");
      $('.doraemon').removeClass('hCollapsed');
      $('.doraemon').addClass('hExpanded');
      $('.oki').css('display','table-row')
    }else{
      $(this).data('text', 'Show All');
      $(this).text("Show All");
      $('.doraemon').removeClass('hExpanded');
      $('.doraemon').addClass('hCollapsed');      
      $('.oki').css('display','none')
    }
  });

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
