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
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @if($start == false)
                    <button type="button" class="btn btn-success" id ="btnPrint" name="btnPrint">Print</button>
                    @endif
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
  <div id="tampilData1">
    {{-- Data summary --}}
    {{-- <div class="col-xl-12 col-md-12 col-12">
      <div class="card card-statistics">
          <div class="card-header">
              <h4 class="card-title">Statistics</h4>
              <div class="d-flex align-items-center">
                  <p class="card-text font-small-2 mr-25 mb-0">Updated 1 month ago</p>
              </div>
          </div>
          <div class="card-body statistics-body">
              <div class="row">
                  <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                      <div class="media">
                          <div class="avatar bg-light-primary mr-2">
                              <div class="avatar-content">
                                  <i data-feather="trending-up" class="avatar-icon"></i>
                              </div>
                          </div>
                          <div class="media-body my-auto">
                              <h4 class="font-weight-bolder mb-0">{{ number_format($quickRatio,2) }}</h4>
                              <p class="card-text font-small-3 mb-0">Quick Ratio</p>
                          </div>
                      </div>
                  </div>
                  <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                      <div class="media">
                          <div class="avatar bg-light-info mr-2">
                              <div class="avatar-content">
                                  <i data-feather="user" class="avatar-icon"></i>
                              </div>
                          </div>
                          <div class="media-body my-auto">
                              <h4 class="font-weight-bolder mb-0">{{ number_format($currentRatio,2) }}</h4>
                              <p class="card-text font-small-3 mb-0">Current Ratio</p>
                          </div>
                      </div>
                  </div>
                  <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-sm-0">
                      <div class="media">
                          <div class="avatar bg-light-danger mr-2">
                              <div class="avatar-content">
                                  <i data-feather="box" class="avatar-icon"></i>
                              </div>
                          </div>
                          <div class="media-body my-auto">
                              <h4 class="font-weight-bolder mb-0">{{ number_format($debtEquityRatio,2) }}</h4>
                              <p class="card-text font-small-3 mb-0">Debt Equity Ratio</p>
                          </div>
                      </div>
                  </div>
                  <div class="col-xl-3 col-sm-6 col-12">
                      <div class="media">
                          <div class="avatar bg-light-success mr-2">
                              <div class="avatar-content">
                                  <i data-feather="dollar-sign" class="avatar-icon"></i>
                              </div>
                          </div>
                          <div class="media-body my-auto">
                              <h4 class="font-weight-bolder mb-0">{{ number_format($equityRatio,2) }}</h4>
                              <p class="card-text font-small-3 mb-0">Equity Ratio</p>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
    </div> --}}
    @if($start == false)
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
                    <h2 class="font-weight-bolder mt-1">{{ number_format($quickRatio,2) }}</h2>
                    <p class="card-text">Quick Ratio</p>
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
                    <h2 class="font-weight-bolder mt-1">{{ number_format($currentRatio,2) }}</h2>
                    <p class="card-text">Current Ratio</p>
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
                  <h2 class="font-weight-bolder mt-1">{{ number_format($debtEquityRatio,2) }}</h2>
                  <p class="card-text">Debt Equity Ratio</p>
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
                  <h2 class="font-weight-bolder mt-1">{{ number_format($equityRatio,2) }}</h2>
                  <p class="card-text">Equity Ratio</p>
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
              <div class="col-md-10">
                <div class="card">
                  <div class="card-body">
                    <button type="button" class="btn btn-primary" id="showListButton" data-text="Show All"><span id="showAllIcon"></span></button>
                    <br>
                    <br>
                    <table class="table table-condensed table-striped">
                      <tbody>
                        @foreach($mains as $key => $main)
                          <tr>
                              <td width="70%" style="font-size:15px;font-weight:bold"> {{ $main->main_name }}</td>
                              <td style="font-size:15px;font-weight:bold" class="text-right">{{ $tanggal }}</td>
                          </tr>
                          @foreach($groups as $keyGroup => $group)
                            @if($group->main == $main->main)
                              <tr data-toggle="collapse" data-target="#demo{{ $keyGroup+1 }}{{ $key }}" class="headerData">
                                <td colspan="2" ><span id="oki{{ $keyGroup+1 }}{{ $key }}" class="classIcon" data-posisi-icon="plus"></span> {{ $group ->group_name}}</td>
                              </tr>
                              <tr>
                                <td colspan="2" class="hiddenRow">
                                  <div class="accordian-body collapse listDataDetail" id="demo{{ $keyGroup+1 }}{{ $key }}"> 
                                    <table class="table table-striped" width="100%">
                                      <tbody>
                                        @foreach($details as $keyDetail => $detail)
                                          @if($detail->group_code == $group->group_data)
                                            <tr>
                                              <td width="70%">{{ $detail->sub_group_name }} ({{ $detail->account }})</td>
                                              <td class="text-right">Rp.{{ number_format($detail->saldo) }} </td>
                                            </tr>
                                          @endif
                                        @endforeach
                                        @foreach($totalGroups as $keyTotalGroup => $totalGroup)
                                          @if($totalGroup->group_code == $group->group_data)
                                            <tr>
                                              <td style="font-size:18px;font-weight:bold">Total {{ $totalGroup->group_name }}</td>
                                              <td style="font-size:18px;font-weight:bold" class="text-right">Rp.{{ number_format($totalGroup->jumlah) }} </td> 
                                            </tr>
                                          @endif
                                        @endforeach
                                      </tbody>
                                    </table>
                                  </div> 
                                </td>
                              </tr>
                            @endif
                          @endforeach
                          @foreach($totalMains as $keyTotalMain => $totalMain)
                            @if($totalMain->main == $main->main)
                              <tr>
                                <td width="70%" style="font-size:20px;font-weight:bold">Total {{ $totalMain->main_name }}</td>
                                <td style="font-size:20px;font-weight:bold" class="text-right">Rp.{{ number_format($totalMain->jumlah) }}</td>
                              </tr>
                            @endif
                          @endforeach
                        @endforeach
                      </tbody>
                    </table>
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
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  let currentDate = todayDate('dd-mm-yyyy');  
  $(".loading-spinner-container").addClass("-show");
  $(document).ready(function(){  
    validateFormToast("frmFilter");    
  });

  const rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

  // const bsDate = $('#bsDate');
  // if (bsDate.length) {
  //   bsDate.flatpickr({
  //         dateFormat: "d-m-Y"
  //     });
  // }

  const connectId = $('.classIcon');
  connectId.html(feather.icons['plus'].toSvg());
  $("#showAllIcon").html(feather.icons['eye'].toSvg() + ' Show All');

  $('#showListButton').on('click', function () {
    $("#showAllIcon").html(feather.icons['eye-off'].toSvg());
    let textButton = $(this).data('text');
    if(textButton == 'Show All'){
      $('.listDataDetail').addClass("show");
      $('.classIcon').data('posisi-icon', 'minus');
      const connectId = $('.classIcon');
      connectId.html(feather.icons['minus'].toSvg());
      $("#showAllIcon").html(feather.icons['eye-off'].toSvg() + ' Hide All');
      $(this).data('text', 'Hide All');
    }else{

      $('.listDataDetail').removeClass("show");
      $('.classIcon').data('posisi-icon', 'plus');
      const connectId = $('.classIcon');
      connectId.html(feather.icons['plus'].toSvg());
      $("#showAllIcon").html(feather.icons['eye'].toSvg() + ' Show All');
      $(this).data('text', 'Show All');
    }
  });
        
  $('.headerData').on('click', function () {
    const myId = $("#"+$(this).find('span').attr('id'));
    const currentIcon = $(this).find('span').data('posisi-icon');
    const newIcon = currentIcon =='plus' ? 'minus' : 'plus';
    myId.html(feather.icons[newIcon].toSvg());
    myId.data('posisi-icon', newIcon);
  });
  
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
        href = "{{ route('balanceSheet.index') }}";
        $('#frmFilter').attr("action", href);
        $('.disabled-el').removeAttr('disabled');
        $('#tampilData1').hide();
        $('#tampilData2').hide();
        $("#btnPrint").hide();
        $("#frmFilter").submit();
    }
  });

  $("#btnPrint").click(function(e){

    let currentURL = window.location.href.slice(window.location.href.indexOf('?') + 1);
    let oki = currentURL.split('=');
    let iko = oki[1].replace(/\+/g,'');

    if((oki[0] === 'bsDate') && (oki.length> 0)){
      e.preventDefault();
      if (!$("#frmFilter")[0].checkValidity()){
          $("#frmFilter").submit();
      }else{
          let id = iko;
          let url = "{{ route('balanceSheet.print', ['bsDate'=>':id']) }}";
          url = url.replace('%3Aid', id);
          window.open(url, '_blank');
      }
    }else{
      swal.fire("Warning", "Isi periode dulu, lalu tekan search ....","warning");
    }

  });

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
