@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: New</h4>
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
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="year">Tahun*</label>
                                    <select class="select2 form-control" id="year" name="year" required>
                                        <option value=""></option>
                                        @for ($i = 2000; $i <= 2050; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="bulanAwal">Bulan Awal*</label>
                                    <select class="select2 form-control" id="bulanAwal" name="bulanAwal" required>
                                        <option value=""></option>
                                        @foreach ($bulan as $key=>$val)
                                            <option value="{{ $key }}">{{ $val }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
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
                                            <th class="isian" style="width: 30%">
                                                <label>Article</label>
                                            </th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>      
                            <div class="" id="item_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                            <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                                <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                                    <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Add</span>
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
                        <a href="{{ route('bankPenerimaan.index') }}" class="btn btn-light">Back</a>
                        <button class="btn btn-info" type="button" id="cmdNew" name="cmdNew">New</button>
                        <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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
        
    add_month =(startMonth,endMonth,urutan)=>{
        let list  = "";
        let year=$('#year').val().slice(-2);

        for(i=parseInt(startMonth);i<=parseInt(endMonth);i++){
            list+= `<td class="isian" style="">
                                <input type="text" data-urutan="${urutan}" class="form-control-plaintext tombol-panah numeral-mask text-right data-bulan" 
                                data-type-el-kiri="input" 
                                data-nama-el-kiri='month${i-1}'
                                data-type-el-kanan='input'
                                data-nama-el-kanan='month${i+1}'
                                data-month='${i}'
                                data-year='${year}'
                                id="${year}${i}" 
                                name="month${i}[]"  
                                value=${i}
                                maxlength="6" />
                            </td>`; 
        }
        
        return list;
    }

    add_judul =(startMonth,endMonth)=>{
        let judul = "";
        let year=$('#year').val().slice(-2);;
        let bulan=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Okt','Nov','Dec'];
        for(i=startMonth;i<=endMonth;i++){
            
            let namaBulan = bulan[i-1];
            judul+=`<th class="isian text-center" >
                        <label>${namaBulan}${year}</label>
                    </th>`;

        }

        return judul;
    }

    $('#customerCode').change(function(e){
        let $this= $(this);
        let customer = $this.val();
        let bulanAwal = $('#bulanAwal').val();
        let bulanAkhir = $('#bulanAkhir').val();
        let cloneCount=1;

        // $("#judulTabel th").remove();
        $("#tabelBaru td").remove();
        articleList(customer);
        let listJudul = add_judul(bulanAwal,bulanAkhir);
        $("#judulTabel").append(listJudul);
        let isiBulan = add_month(bulanAwal,bulanAkhir,cloneCount);            
        $("#item_row").append($("#new_row").clone().html());
        $("#tabelBaru").append(isiBulan);
        $('#customerId').select2();
        activate_angka();
        mask_thousand();
    });
    
    $(document).ready(function(){           
        validateFormToast('frmAdd');
        vcDate.val(currentDate);
       
        feather.replace({
            width: 14,
            height: 14
        });

        // add_new_row();
        
    });

    $('#customerId').change(function(e){
        
    });

   
    
    vcDate = $('#vcDate');
    if (vcDate.length) {
        vcDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }

    $("#addNewRow").click(function(){

        $(".data-bulan").map(function(i) {  
            let $this=$(this);
            let urutan= $this.data("urutan");
            let month= $this.data("month");
            let year= $this.data("year");
            let objCustomerId= $('#customerId'+urutan).val();
            // console.log($this.attr('id'));
            // console.log($this.val());
            // console.log(objCustomerId.eq(i).val());
            console.log(urutan+"-"+month+'-'+year+'-'+objCustomerId+"-"+$this.attr('id')+"="+$this.val());
        });
    })
    
    $("#cmdSave").click(function(){  
        $(".data-bulan").map(function(i) {  
                    let $this=$(this);
                    
                    let urutan= $this.data("urutan");
                    let month= $this.data("month");
                    let year= $this.data("year");
                    let objCustomerId= $('#customerId'+urutan).val();
                    // console.log($this.attr('id'));
                    // console.log($this.val());
                    // console.log(objCustomerId.eq(i).val());
                    console.log(urutan+"-"+month+'-'+year+'-'+objCustomerId+"-"+$this.attr('id')+"="+$this.val());

                    // if ($this.val()){
                    //     let sAccount=$this.val();
                    //     let sDesc=objvcDesc.eq(i).val();
                    //     let sCc=objVcCc.eq(i).val();
                    //     let sDebit=objVcDebit.eq(i).val().replace(/,/gi, '') || 0;
                    //     let sCredit=objVcCredit.eq(i).val().replace(/,/gi, '') || 0;

                    //     if ((sDesc!=='') && ((sDebit + sCredit) > 0) && (sAccount!=='') && (sCc!=='')){
                    //         details.push({
                    //             "account":sAccount,
                    //             "description":sDesc,
                    //             "cc":sCc,
                    //             "debit":sDebit,
                    //             "credit":sCredit,
                    //         });
                    //     }

                    //     if ((sDesc =='') || (sCc =='') || ((sDebit + sCredit) == 0)){
                    //         cekIsi++;
                    //     }

                    // }
        });


        // let objTotalVcDebit= $('#vcTotalDebit').val().replace(/,/gi, '') || 0;
        // let objTotalVcCredit= $('#vcTotalCredit').val().replace(/,/gi, '') || 0;
        // let vcDate = $('#vcDate').val();
        // let period = $('#period').val();
        // let totalAmount = $('#totalAmount').val().replace(/,/gi, '') || 0;
        // let note = $('#note').val();
        // let recFrom = $('#recFrom').val();
        // let recFromDesc = $('#recFromDesc').val();
            
        // if (!$("#frmAdd")[0].checkValidity()){
        //     $("#frmAdd").submit();
        // }else{  
        //     if (((parseInt(objTotalVcDebit)-parseInt(objTotalVcCredit)) == 0) && (parseInt(objTotalVcCredit)==parseInt(totalAmount))){ 
        //         $('#cmdSave').attr('disabled','disabled');
        //         $('.disabled-el').removeAttr('disabled');
        //         // ambil semua data article
        //         let objvcDesc= $('#item_row input[name="vcDesc[]"]');
        //         let objVcCc= $('#item_row select[name="vcCc[]"]');
        //         let objVcDebit= $('#item_row input[name="vcDebit[]"]');
        //         let objVcCredit= $('#item_row input[name="vcCredit[]"]');
        //         let objAccount= $('#item_row select[name="account[]"]');
        //         let details = []; 
        //         let flag=0; 
        //         let pesan="";
        //         let cekIsi=0;

        //         objAccount.map(function(i) {  
        //             let $this=$(this);
        //             if ($this.val()){
        //                 let sAccount=$this.val();
        //                 let sDesc=objvcDesc.eq(i).val();
        //                 let sCc=objVcCc.eq(i).val();
        //                 let sDebit=objVcDebit.eq(i).val().replace(/,/gi, '') || 0;
        //                 let sCredit=objVcCredit.eq(i).val().replace(/,/gi, '') || 0;

        //                 if ((sDesc!=='') && ((sDebit + sCredit) > 0) && (sAccount!=='') && (sCc!=='')){
        //                     details.push({
        //                         "account":sAccount,
        //                         "description":sDesc,
        //                         "cc":sCc,
        //                         "debit":sDebit,
        //                         "credit":sCredit,
        //                     });
        //                 }

        //                 if ((sDesc =='') || (sCc =='') || ((sDebit + sCredit) == 0)){
        //                     cekIsi++;
        //                 }

        //             }
        //         });

        //         if ((details.length == 0) || (cekIsi >0)){
        //             pesan +="Detail must be filled Out completely <br>"; 
        //             flag=1;
        //         }

        //         if (flag == 0){
        //             $.ajax({
        //                 type: "post",
        //                 url: "{{ route('bankPenerimaan.store') }}",
        //                 data: {
        //                     details:JSON.stringify(details),
        //                     vcDate:vcDate,
        //                     period:period,
        //                     note:note,
        //                     totalAmount:totalAmount,
        //                     recFrom:recFrom,
        //                     recFromDesc:recFromDesc
        //                 },
        //                 dataType: "json",
        //                 success: function(data) {
        //                     if (data.status == 0 ){
        //                         let message="";
        //                         for(let i = 0; i < data.message.length; i++) {
        //                             show_msg(data.title, data.message[i], data.alert);
        //                         }                        
        //                         $('#voucherNumber').attr('disabled','disabled');
        //                     }else{
        //                         show_msg(data.title, data.message, data.alert);
        //                         $('#voucherNumber').val(data.vcNumber);
        //                         $('#voucherNumber').attr('disabled','disabled');
        //                         $('#cmdSave').attr('disabled','disabled');
        //                         $('#addNewRow').attr('disabled','disabled');
        //                         window.location.href = "{{ route('bankPenerimaan.create') }}";
        //                     }
        //                 },
        //                 error: function(error) {
        //                     console.log(error);
        //                 }
        //             });
        //         }else{
        //             $('#cmdSave').removeAttr('disabled');
        //             Swal.fire('Warning..',pesan,'warning');
        //         }
        //     }else{
        //         Swal.fire('Warning..',"Data belum balance",'warning');
        //     }
        // }
    });

    let cloneCount=1;
    function add_new_row() {
        $("#item_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#item_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#item_row").find('#tabelBaru').attr('id', 'new_table_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article').attr('id', 'article'+ cloneCount);
        $("#new_row"+ cloneCount).find('#customerId').attr('id', 'customerId'+ cloneCount);
        
        let isiBulan = add_month(6,12,cloneCount);               
        $("#new_table_row"+ cloneCount).append(isiBulan);
        feather.replace({
            width: 14,
            height: 14
        });
               
        $("#customerId"+cloneCount).select2();
        $('#remove_button').tooltip();
        
        activate_angka();
        mask_thousand();
        // hitungTotal();
        // hitungGrandTotal();
        // $('[data-toggle="tooltip"]').tooltip();
        
        // $("#vcDesc"+ cloneCount).autocomplete({
        //     source: availableTags
        // });

    };

    function articleList(customer) {
      $.ajax({
        url:"{{route('forecastSales.get.article')}}",
        method:"POST",
        data:{
            customerCode:customer
        },
        success:function(result){
            $('#articleId').html(result);
            $('#articleId').val('').trigger('change');
            $('#articleId').select2();
        }
      })
    }

    

    $("#cmdNew").click(function(){ 
        let objAccount= $('#item_row select[name="account[]"]');
        let details = [];
        objAccount.map(function(i) {  
            let $this=$(this);
            if ($this.val()){
                let sAccount=$this.val();
                if (sAccount!==''){
                    details.push({
                        "account":sAccount
                    });
                }
            }
        });

        if (details.length > 0){
            Swal.fire({
                title: 'Akan input data baru?',
                text: "Apakah data sebelumnya akan di simpan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                if (result.isConfirmed) {
                    $("#cmdSave").click();
                }else{
                    window.location.href = "{{ route('bankPenerimaan.create') }}";
                }
            })
        }else{
            window.location.href = "{{ route('bankPenerimaan.create') }}";
        }
    });

    $("#recFrom").on('select2:close', function(){
        let content = this.value;
        let contentText = $("#recFrom").select2('data')[0].text;
        if(content =='other'){
            $(".other-desc").removeClass("d-none");
            $("#recFromDesc").val("");
            $("#recFromDesc").focus();
        }else{
            $(".other-desc").addClass("d-none");
            contentText = contentText.split("|");
            $("#recFromDesc").val(contentText[1].trim());
        }    
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection