@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    {{-- <h4 class="card-title">Status: New</h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>     --}}
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <input type="text" id="article" name="article" hidden>
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
                            <div>
                                <table id="detailTable" style="width:98%;table-layout: fixed;">
                                    <tbody>
                                        <tr id="judulTabel">
                                        </tr>
                                    </tbody>
                                </table>
                            </div>      
                            <div class="" id="item_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                            </div>
                            <br>
                            <div>
                                <button class="btn btn-primary btn-prev" type="button" id="cmdSave" name="cmdSave">
                                    <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Save</span>
                                </button>
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

    $(document).ready(function(){           
        validateFormToast('frmAdd');
        $("#cmdSave").hide();
        feather.replace({
            width: 14,
            height: 14
        });        
    });

    $('#customerCode,#year,#bulanAwal,#bulanAkhir').change(function(e){
        let $this= $(this);
        let idku= $this.attr('id');
        if ($this.val()){
            $("#judulTabel th").remove();
            listDetailBulan();
        }

        if ($('#customerCode').val()){            
            let bulanAwal = $('#bulanAwal').val();
            let bulanAkhir = $('#bulanAkhir').val();
            let year = $('#year').val().slice(-2);;
            let listJudul = add_judul(bulanAwal,bulanAkhir);
            let customer= $('#customerCode').val();
            // $("#judulListTabel").append(listJudul);
            // console.log(listJudul);
            
            if ($('#listTable tr').length >0){
                console.log("ada");
                let table= $('#listTable').DataTable();
                table.destroy();
                $('#listTable tbody > tr').remove();
                $("#listTable thead > tr").remove();
            }

            $('#listTable thead').append("<tr><td>Action</td>"+listJudul+"</tr>");

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
                            <button class="btn btn-success btn-sm" type="button" onclick="editArticle('${result.data[i].article_code}')" id="cmdEdit" name="cmdEdit" >Edit</button>
                            </td>`
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
                    });
                }
            })
        }

    });
 
    $('body').on('change', '#articleId', function() {
        let $this= $(this);
        let year=$('#year').val().slice(-2);
        let bulanAwal = $('#bulanAwal').val();
        let bulanAkhir = $('#bulanAkhir').val();

        if ($this.val()){

            for(i=parseInt(bulanAwal);i<=parseInt(bulanAkhir);i++){
                $('#'+year+i).val(0);
            }

            let article = $this.val();
            let customer = $('#customerCode').val();
            let articleId = $('#articleId').val();

            $.ajax({
                url:"{{route('forecastSales.get.qty.article')}}",
                method:"POST",
                data:{
                    customerCode:customer,
                    article:article,
                    year:year,
                    articleId:articleId
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
    });

    editArticle = (articleCode) =>{
        $('#articleId').val(articleCode).trigger('change');
    }

    deleteArticle = (customerId,articleCode,year,articleDesc) =>{
        Swal.fire({
            icon: 'warning',
            title: `Do you want to delete the article <br> ${articleDesc}?`,
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url:"{{route('forecastSales.destroy')}}",
                    method:"POST",
                    data:{
                        customerId:customerId,
                        articleCode:articleCode,
                        year:year,
                        articleDesc:articleDesc
                    },
                    success:function(result){
                        $('#customerCode').val(customerId).trigger('change');
                        Swal.fire(result.message, '', result.alert);
                    }
                })
                
            } else if (result.isDenied) {
                // Swal.fire('Changes are not saved', '', 'info')
            }
        })
    }
    
    vcDate = $('#vcDate');
    if (vcDate.length) {
        vcDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }

    $("#cmdSave").click(function(){
        let details = [];
        let flag = 0;
        let pesan ='';

        $(".data-bulan").map(function(i) {  
            let $this=$(this);
            let urutan= $this.data("urutan");
            let month= $this.data("month");
            let year= $this.data("year");
            let customerCode= $('#customerCode').val();
            let articleId= $('#articleId').val();
            
            details.push({
                "fc_code": customerCode+year+month,
                "customer_id": customerCode,
                "article_code": articleId,
                "qty": $this.val(),
                "year": year,
                "month": month
            });

        });

        if (flag == 0){
            let customerCodeIni= $('#customerCode').val();
            $.ajax({
                type: "post",
                url: "{{ route('forecastSales.store') }}",
                data: {
                    details:JSON.stringify(details),
                },
                dataType: "json",
                success: function(data) {
                    if (data.status == 0 ){
                        let message="";
                        for(let i = 0; i < data.message.length; i++) {
                            show_msg(data.title, data.message[i], data.alert);
                        }
                    }else{
                        show_msg(data.title, data.message, data.alert);
                        emptyList();
                        $('#customerCode').val(customerCodeIni).trigger('change');

                    }
                },
                error: function(error) {
                    console.log(error);
                }
            });
        }else{
            Swal.fire('Warning..',pesan,'warning');
        }
    })
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection