@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <input type="text" id="article" name="article" hidden>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="fcNumber">Forecasting Number</label>
                                    <input type="text" id="fcNumber" name="fcNumber" class="form-control" disabled/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="year">Tahun*</label>
                                    <select class="select2 form-control" id="year" name="year" disabled>
                                        <option value=""></option>
                                        @for ($i = 2022; $i <= 2050; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="bulanAwal">Bulan Awal*</label>
                                    <select class="select2 form-control" id="bulanAwal" name="bulanAwal" disabled>
                                        <option value=""></option>
                                        @foreach ($bulan as $key=>$val)
                                            <option value="{{ $key }}">{{ $val }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="bulanAkhir">Bulan Akhir*</label>
                                    <select class="select2 form-control" id="bulanAkhir" name="bulanAkhir" disabled>
                                        <option value=""></option>
                                        @foreach ($bulan as $key=>$val)
                                            <option value="{{ $key }}">{{ $val }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="forcastName">Forecasting Name</label>
                                    <input type="text" id="forcastName" name="forcastName" class="form-control" disabled/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled></textarea>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('forecastSales.index') }}" class="btn btn-light">< Back</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Detail data</h4>
                </div>
                <div class="card-body" >
                    <div class="col-12">
                        <div class="card-datatable table-responsive pt-0">
                            <table id="listTable" class="display" style="width:100%">
                                <thead>
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
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/jquery-ui.css') }}">
<style>

    textarea {
        resize: none;
    }

    .mb-03{
        margin-bottom: 0.3rem;
    }
    
    label.titik-dua::after{
        content : ":"; 
        position : absolute;
        right : 1px;
    }

    td.isian{
        padding-right:10px;
        padding-left:10px;
    }

    td.nopadding{
        padding-right:0px;
        padding-left:0px;
    }

    td.isian-satu{
        padding-right:5px;
        padding-left:15px;
        width: 25%;border-top: 1px solid #ffffff !important;
        border-bottom: 1px solid #ffffff !important;
        border-left: 1px solid #ffffff !important;
    }

    td.disabled{
        background-color:#f8f8f8;
        color:black;
    }

    label.tanpa-padding{
        padding-top: 5px;
        padding-bottom: 0px;
    }

    .totalLine{
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }


</style>
@endsection
@section('scripts')
<script src="{{ asset('assets/js/ui.1.13.0.jquery-ui.js') }}"></script>
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy'); 
    let vFcNumber = $('#fcNumber'); 
    let vYear = $('#year');
    let vBulanAwal = $('#bulanAwal');
    let vBulanAkhir = $('#bulanAkhir');
    let vForcastName = $('#forcastName');
    let vNote = $('#note');
    let inEdit = 'false';

    $(document).ready(function(){           
        validateFormToast('frmAdd');
        $("#cmdSave").hide();
        $("#cmdUpdateHeader").hide();

        feather.replace({
            width: 14,
            height: 14
        });    

        if("{{ $forcastNumber }}"){
            vYear.val("{{ $year }}").trigger('change');
            vBulanAwal.val("{{ $bulanAwal }}").trigger('change');
            vBulanAkhir.val("{{ $bulanAkhir }}").trigger('change');
            vFcNumber.val("{{ $forcastNumber }}");
            vForcastName.val("{{ $forcastName }}");
            vNote.val("{{ $note }}");
            vYear.attr('disabled','disabled');
            vBulanAwal.attr('disabled','disabled');
            vBulanAkhir.attr('disabled','disabled');
            listDataAll("{{ $forcastNumber }}");
            $("#cmdUpdateHeader").show();
        }

    });

    listDataAll =(fcNumber)=>{
        let bulanAwal = vBulanAwal.val();
        let bulanAkhir = vBulanAkhir.val();
        let year = vYear.val().slice(-2);;
        let listJudul = add_judul(bulanAwal,bulanAkhir);
        let customer = $('#customerCode').val();
        let forcastName = $('#forcastName').val();
        let zFcnumber = fcNumber;

        let jumlahBulan = parseInt(bulanAkhir)-parseInt(bulanAwal);
        
        let kolomPrint = [1,2,3];
        for(i=1;i<=jumlahBulan+1;i++){
          kolomPrint.push(i+2);
        }
        
        if ($('#listTable tr').length >0){
            // console.log("ada");
            let table= $('#listTable').DataTable();
            table.destroy();
            $('#listTable tbody > tr').remove();
            $("#listTable thead > tr").remove();
        }

        $('#listTable thead').append("<tr><th>Customer</th><th>Article Code</th>"+listJudul+"</tr>");

        $.ajax({
            url:"{{route('forecastSales.get.list.article')}}",
            method:"POST",
            data:{
                customerCode:customer,
                year:year,
                bulanAwal:bulanAwal,
                bulanAkhir:bulanAkhir,
                forcastName:forcastName,
                fcnumber:zFcnumber
            },
            success:function(result){
                let conversi = ['satu','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas','duabelas'];
                for(i=0;i< result.data.length;i++){
                    list=""
                    list+=`<td >${result.data[i].nama}</td>`
                    list+=`<td >${result.data[i].article_alternative_code}</td>`
                    list+=`<td >${result.data[i].article_desc}</td>`
                    for(a=parseInt(bulanAwal);a<=parseInt(bulanAkhir);a++){
                        z=conversi[a];
                        let qty = result.data[i][z];
                        list+= `<td class="text-right"> ${qty ? humanizeNumber(qty) : 0} </td>`; 
                    }
                    $('#listTable tbody').append("<tr>"+list+"</tr>");
                }

                $('#listTable').DataTable({
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
    }   
 
    showData =(uCustomer,articleId)=>{
        let uYear=$('#year').val().slice(-2);
        let uBulanAwal = vBulanAwal.val();
        let uBulanAkhir = vBulanAkhir.val();
        let uFcNumber = vFcNumber.val();

        for(i=parseInt(uBulanAwal);i<=parseInt(uBulanAkhir);i++){
            $('#'+uYear+i).val(0);
        }
        
        $.ajax({
            url:"{{route('forecastSales.get.qty.article')}}",
            method:"POST",
            data:{
                customerCode:uCustomer,
                // article:uArticle,
                year:uYear,
                articleId:articleId,
                fcNumber:uFcNumber
            },
            success:function(result){
                for(i=0;i< result.data.length;i++){
                    $('#'+result.data[i].year+result.data[i].month).val(result.data[i].qty).trigger('input');
                }
                activate_angka();
                mask_thousand();
            }
        })
    }
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection