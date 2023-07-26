@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

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
            <div class="form-group col-md-2">
                <label class="form-label" for="year">Tahun*</label>
                <select class="select2 form-control" id="year" name="year" required>
                    <option value=""></option>
                    @for ($i = 2022; $i <= 2050; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="form-group col-md-2">
                <label class="form-label" for="bulanAwal">Bulan Awal*</label>
                <select class="select2 form-control" id="bulanAwal" name="bulanAwal" required>
                    <option value=""></option>
                    @foreach ($bulan as $key=>$val)
                        <option value="{{ $key }}">{{ $val }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2">
                <label class="form-label" for="bulanAkhir">Bulan Akhir*</label>
                <select class="select2 form-control" id="bulanAkhir" name="bulanAkhir" required>
                    <option value=""></option>
                    @foreach ($bulan as $key=>$val)
                        <option value="{{ $key }}">{{ $val }}</option>
                    @endforeach
                </select>
            </div>
          </div>
          <div class="form-row">
              <div class="form-group col-md-6">
                  <label for="customerCode">Customer</label>
                  <select class="select2 form-control" id="customerCode" name="customerCode">
                      <option value="">Choose Customer</option>
                      @foreach($customers as $val)
                      <option value="{{ $val->kode }}">{{ $val->nama }}</option>
                      @endforeach
                  </select>
              </div>
          </div>
          <div class="form-row">
              <div class="col-12"> 
                  <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                  {{-- @can('pettyCash-create') --}}
                  <a href="{{ route('forecastSales.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                  {{-- @endcan --}}
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
                <table id="detailedTable" >
                  <thead class="thead-light">
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
        </div>  
      </div>
    </div>
  </div>
</section>

@include('forecasting.sales.addArticle')

@endsection
@section('styles')
<style>

td.wrapok {
    white-space:normal
}
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  $(document).ready(function(){    
    
  });

  $("#btnSearch").click(function(e){

      let bulanAwal = $('#bulanAwal').val();
      let bulanAkhir = $('#bulanAkhir').val();
      let year = $('#year').val().slice(-2);;
      let listJudul = add_judul(bulanAwal,bulanAkhir);
      let customer= $('#customerCode').val();

      

      if ((parseInt(bulanAkhir)-parseInt(bulanAwal) >= 0) && year){

        let jumlahBulan = parseInt(bulanAkhir)-parseInt(bulanAwal);
        
        let kolomPrint = [1,2];
        for(i=1;i<=jumlahBulan+1;i++){
          kolomPrint.push(i+2);
        }
  
        $("#judulTabel th").remove();
        listDetailBulan();
        
        if ($('#detailedTable tr').length >0){
            let table= $('#detailedTable').DataTable();
            $('#detailedTable tbody > tr').remove();
            $("#detailedTable thead > tr").remove();
        }

        $('#detailedTable thead').append("<tr><td>Action</td><td>Customer</td>"+listJudul+"</tr>");

        $.ajax({
            url:"{{route('forecastSales.get.list.article')}}",
            method:"POST",
            data:{
                customerCode:customer,
                year:year,
                bulanAwal:bulanAwal,
                bulanAkhir:bulanAkhir
            },
            success:function(result){
                let conversi = ['satu','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas','duabelas'];
                for(i=0;i< result.data.length;i++){
                    
                    list=`<td ><button class="btn btn-danger btn-sm" type="button" onclick="deleteArticle('${result.data[i].customer_id}','${result.data[i].article_code}','${result.data[i].year}','${result.data[i].article_desc}')" id="cmdEdit" name="cmdEdit" >Delete</button> 
                        </td>`
                    list+=`<td >${result.data[i].nama}</td>`
                    list+=`<td >${result.data[i].article_desc}</td>`
                    for(a=parseInt(bulanAwal);a<=parseInt(bulanAkhir);a++){
                        z=conversi[a];
                        let qty = result.data[i][z];
                        list+= `<td class="text-right"> ${qty ? humanizeNumber(qty) : 0} </td>`; 
                    }
                    $('#detailedTable tbody').append("<tr>"+list+"</tr>");
                }

                $('#detailedTable').DataTable({
                    bDestroy: true, //pakai ini supaya bisa di load berulang2
                    scrollX: true,
                    buttons: true,
                    dom:` <"d-flex justify-content-between align-items-center header-actions mx-1 row mt-75"<"col-lg-12 col-xl-6" l><"col-lg-12 col-xl-6 pl-xl-75 pl-0"<"dt-action-buttons text-xl-right text-lg-left text-md-right text-left d-flex align-items-center justify-content-lg-end align-items-center flex-sm-nowrap flex-wrap mr-1"<"mr-1" f>'B'>>>t<"d-flex justify-content-between mx-2 row mb-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>`,
                    buttons: [
                    {
                      extend: 'collection',
                      className: 'btn btn-outline-secondary dropdown-toggle mt-07',
                      text: feather.icons['share'].toSvg({ class: 'font-small-4 mr-50' }) + 'Export',
                      buttons: [
                        {
                          extend: 'csv',
                          text: feather.icons['file-text'].toSvg({ class: 'font-small-4 mr-50' }) + 'Csv',
                          className: 'dropdown-item',
                          exportOptions: { columns: kolomPrint }
                        },
                        {
                          extend: 'excel',
                          text: feather.icons['file'].toSvg({ class: 'font-small-4 mr-50' }) + 'Excel',
                          className: 'dropdown-item',
                          exportOptions: { columns: kolomPrint },
                          // action: newExportAction,
                          title:null,
                          filename:'fc_sales'
                        },
                        
                      ],
                      init: function (api, node, config) {
                        $(node).removeClass('btn-secondary');
                        $(node).parent().removeClass('btn-group');
                        setTimeout(function () {
                          $(node).closest('.dt-buttons').removeClass('btn-group').addClass('d-inline-flex');
                        }, 50);
                      }
                    },
                    ],
                });
            }
        })
      }else{
        swal.fire('Info',"Isi dahulu filter Tahun dan bulan dengan benar!!",'warning');
      }
  });
  
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
