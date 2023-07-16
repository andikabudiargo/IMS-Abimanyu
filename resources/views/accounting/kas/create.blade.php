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
                                <div class="form-group col-md-4">
                                    <label for="voucherNumber">Voucher Number</label>
                                    <input type="text" id="voucherNumber" name="voucherNumber" class="form-control" disabled/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="vcDate">Date*</label>
                                    <input type="text" id="vcDate" name="vcDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="period">Period*</label>
                                    <select class="select2 form-control" id="period" name="period" required>
                                        <option value=""></option>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="recFrom">Received From*</label>
                                    <select class="select2 form-control" id="recFrom" name="recFrom" required>
                                        <option value=""></option>
                                        @foreach ($accounts as $val)
                                            <option value="{{ $val->account }}">{{ $val->account }}|{{ $val->description }}</option>
                                        @endforeach
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3 d-none other-desc">
                                    <div class="form-group">
                                        <label for="recFromDesc">Other Received From Desc*</label>
                                        <input type="text" id="recFromDesc" name="recFromDesc" class="form-control" required/>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-group">
                                        <label for="totalAmount">Amount*</label>
                                        <input type="text" id="totalAmount" name="totalAmount" class="form-control text-right numeral-mask" maxlength="12" required/>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-10">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
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
                    <div>
                        <table class="" style="width:98%;table-layout: fixed;">
                            <tbody>
                                <tr>
                                    <td class="isian" style="width: 20%">
                                        <label>Account</label>
                                    </td>
                                    <td class="isian"style="width: 25%">
                                        <label>Description</label>
                                    </td>
                                    <td class="isian" style="">
                                        <label>Referensi</label>
                                    </td>
                                    <td class="isian" style="">
                                        <label>CC</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Debit</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Credit</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>-</label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>      
                    <div class="" id="item_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                        <input type="text" id ="last_row_number" class="d-none" value="0">
                    </div>
                    <table class="table-bordered" style="width: 98%;table-layout: fixed;">
                        <tbody>
                            <tr>
                                <td colspan="4" class="isian text-right" style="border-left: 1px solid white;border-bottom: 1px solid white;">
                                    <label style="font-size: 12pt;">Total</label>
                                </td>
                                <td class="isian" style="width: 10%">
                                    <input type="text" class="form-control-plaintext numeral-mask text-right" id="vcTotalDebit" disabled />
                                </td>
                                <td class="isian" style="width: 10%">
                                    <input type="text" class="form-control-plaintext numeral-mask text-right" id= "vcTotalCredit" disabled />
                                </td>
                                <td class="isian text-center" style="width: 5%;border-right: 1px solid white;border-bottom: 1px solid white;">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4" class="isian text-right" style="border-left: 1px solid white;border-bottom: 1px solid white;">
                                    <label style="font-size: 12pt;">Selisih</label>
                                </td>
                                <td class="isian" style="width: 10%">
                                </td>
                                <td class="isian" style="width: 10%">
                                    <input type="text" class="form-control-plaintext numeral-mask text-right" id="selisih" disabled />
                                </td>
                                <td class="isian text-center" style="width: 5%;border-right: 1px solid white;border-bottom: 1px solid white;">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add row</span>
                        </button>
                    </div>
                    <hr>
                    <div class="col-12">
                        <a href="{{ route('kasPenerimaan.index') }}" class="btn btn-light">Back</a>
                        <button class="btn btn-info" type="button" id="cmdNew" name="cmdNew">New</button>
                        <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@include('accounting.kas.addArticle')
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
    let type = "{{ $type }}";
    // var availableTags =;
    // availableTags=availableTags.replace(/[[\]]/g,'');
    // availableTags=availableTags.replace(/&quot;/g,'').split(",");
        
    $(document).ready(function(){           
        validateFormToast('frmAdd');
        vcDate.val(currentDate);
        add_new_row();
    });
    
    vcDate = $('#vcDate');
    if (vcDate.length) {
        vcDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }
    
    $("#cmdSave").click(function(){  
        let objTotalVcDebit= $('#vcTotalDebit').val().replace(/,/gi, '') || 0;
        let objTotalVcCredit= $('#vcTotalCredit').val().replace(/,/gi, '') || 0;
        let vcDate = $('#vcDate').val();
        let period = $('#period').val();
        let totalAmount = $('#totalAmount').val().replace(/,/gi, '') || 0;
        let note = $('#note').val();
        let recFrom = $('#recFrom').val();
        let recFromDesc = $('#recFromDesc').val();
            
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{  
            if (((parseInt(objTotalVcDebit)-parseInt(objTotalVcCredit)) == 0) && (parseInt(objTotalVcCredit)==parseInt(totalAmount))){ 
                $('#cmdSave').attr('disabled','disabled');
                $('.disabled-el').removeAttr('disabled');
                // ambil semua data article
                let objvcDesc= $('#item_row input[name="vcDesc[]"]');
                let objVcRef= $('#item_row select[name="vcRef[]"]');
                let objVcCc= $('#item_row select[name="vcCc[]"]');
                let objVcDebit= $('#item_row input[name="vcDebit[]"]');
                let objVcCredit= $('#item_row input[name="vcCredit[]"]');
                let objAccount= $('#item_row select[name="account[]"]');
                let details = []; 
                let flag=0; 
                let pesan="";
                let cekIsi=0;

                objAccount.map(function(i) {  
                    let $this=$(this);
                    if ($this.val()){
                        let sAccount=$this.val();
                        let sDesc=objvcDesc.eq(i).val();
                        let sRef=objVcRef.eq(i).val();
                        let sCc=objVcCc.eq(i).val();
                        let sDebit=objVcDebit.eq(i).val().replace(/,/gi, '') || 0;
                        let sCredit=objVcCredit.eq(i).val().replace(/,/gi, '') || 0;

                        if ((sDesc!=='') && ((sDebit + sCredit) > 0) && (sAccount!=='') && (sCc!=='')){
                            details.push({
                                "account":sAccount,
                                "description":sDesc,
                                "reference":sRef,
                                "cc":sCc,
                                "debit":sDebit,
                                "credit":sCredit,
                            });
                        }

                        if ((sDesc =='') || (sCc =='') || ((sDebit + sCredit) == 0)){
                            cekIsi++;
                        }

                    }
                });

                if ((details.length == 0) || (cekIsi >0)){
                    pesan +="Detail must be filled Out completely <br>"; 
                    flag=1;
                }

                if (flag == 0){
                    $.ajax({
                        type: "post",
                        url: "{{ route('kasPenerimaan.store') }}",
                        data: {
                            details:JSON.stringify(details),
                            vcDate:vcDate,
                            period:period,
                            note:note,
                            totalAmount:totalAmount,
                            recFrom:recFrom,
                            recFromDesc:recFromDesc
                        },
                        dataType: "json",
                        success: function(data) {
                            if (data.status == 0 ){
                                let message="";
                                for(let i = 0; i < data.message.length; i++) {
                                    show_msg(data.title, data.message[i], data.alert);
                                }                        
                                $('#voucherNumber').attr('disabled','disabled');
                            }else{
                                show_msg(data.title, data.message, data.alert);
                                $('#voucherNumber').val(data.vcNumber);
                                $('#voucherNumber').attr('disabled','disabled');
                                $('#cmdSave').attr('disabled','disabled');
                                $('#addNewRow').attr('disabled','disabled');
                                window.location.href = "{{ route('kasPenerimaan.create') }}";
                            }
                        },
                        error: function(error) {
                            console.log(error);
                        }
                    });
                }else{
                    $('#cmdSave').removeAttr('disabled');
                    Swal.fire('Warning..',pesan,'warning');
                }
            }else{
                Swal.fire('Warning..',"Data belum balance",'warning');
            }
        }
    });

    let cloneCount=0;
    function add_new_row() {
        $("#item_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#item_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#vcDesc').attr('id', 'vcDesc'+ cloneCount);
        $("#new_row"+ cloneCount).find('#vcRef').attr('id', 'vcRef'+ cloneCount);
        $("#new_row"+ cloneCount).find('#account').attr('id', 'account'+ cloneCount);
        $("#new_row"+ cloneCount).find('#vcCc').attr('id', 'vcCc'+ cloneCount);
        accList('account','account'+ cloneCount);
        
        $("#account"+cloneCount).select2();
        $("#vcCc"+cloneCount).select2();

        $('#remove_button').tooltip();
        // tombolPanah('vcDebit');
        // tombolPanah('vcCredit');
        activate_angka();
        mask_thousand();
        hitungTotal();
        hitungGrandTotal();
        findInvoice();
        getAmount();
        $('[data-toggle="tooltip"]').tooltip();
        
        // $("#vcDesc"+ cloneCount).autocomplete({
        //     source: availableTags
        // });

    };

    function accList(dependent,obj) {
      $.ajax({
        url:"{{route('dynamic.dependent')}}",
        method:"POST",
        data:{
            dependent:dependent
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val('').trigger('change');
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
                    window.location.href = "{{ route('kasPenerimaan.create') }}";
                }
            })
        }else{
            window.location.href = "{{ route('kasPenerimaan.create') }}";
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

    // function findInvoice(){
    //     let objAccount = $('#item_row select[name="account[]"]');
    //     let objVcRef= $('#item_row select[name="vcRef[]"]');
    //     let objVcDebit= $('#item_row input[name="vcDebit[]"]');
    //     let objVcCredit= $('#item_row input[name="vcCredit[]"]');
        
    //     objAccount.change(function(e){        
    //         let objIndex = objAccount.index(this);
    //         let accountNumber = objAccount.eq(objIndex).val();
    //         let recFrom = $('#recFrom').val();
    //         let objCust = "vcRef"+(objIndex+1);
    //         if(accountNumber){
    //             if (accountNumber =='1100.40'){
    //                 // if(recFrom){
    //                     invList('referenceAr',objCust,recFrom);
    //                 // }else{
    //                 //     Swal.fire('Warning..','Kolom bayar ke /supplier code masih kosong','warning');
    //                 // }
    //             }else{
    //                 objVcDebit.eq(objIndex).val("");
    //                 objVcCredit.eq(objIndex).val("");
    //                 objVcRef.eq(objIndex).empty().trigger('change');
    //                 hitungGrandTotal();
    //             }
    //         }
    //     });
    // }

    // function invList(dependent,obj,value) {
    //   $.ajax({
    //     url:"{{route('dynamic.dependent')}}",
    //     method:"POST",
    //     data:{
    //         dependent:dependent,
    //         value:value
    //     },
    //     success:function(result){
    //         $('#'+obj).html(result);
    //         $('#'+obj).val("").trigger('change');
    //     }
    //   })
    // }

    // function getAmount(){
    //     let objRef = $('#item_row select[name="vcRef[]"]');
    //     objRef.change(function(e){ 
    //         let objIndex = objRef.index(this);
    //         let vRef = objRef.eq(objIndex).val();
    //         if(vRef){
    //             getAmountValue(vRef,objIndex); 
    //         }
    //     });
    // }   

    // function getAmountValue(vRef,objIndex) {
    //     let objVcDebit= $('#item_row input[name="vcDebit[]"]');
    //     let objVcCredit= $('#item_row input[name="vcCredit[]"]');
    //     $.ajax({
    //         type: "get",
    //         url: "{{ route('kasPenerimaan.get.invoice.amount') }}",
    //         data: {
    //             vRef:vRef
    //         },
    //         dataType: "json",
    //         success: function(data) {
    //             objVcCredit.eq(objIndex).val('');
    //             objVcDebit.eq(objIndex).val('');
    //             if(data.amount){
    //                 objVcDebit.eq(objIndex).val(humanizeNumber(data.amount));
    //                 objVcCredit.eq(objIndex).val('');
    //                 hitungGrandTotal();
    //             }
    //         },
    //         error: function(error) {
    //             console.log(error);
    //         }
    //     });
    // }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection