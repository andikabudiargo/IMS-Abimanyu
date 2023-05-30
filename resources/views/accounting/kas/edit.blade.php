@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $status }}</h4>
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
                                    <input type="text" id="voucherNumber" name="voucherNumber" value="{{ $header->voucher_number }}" class="form-control" disabled/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="vcDate">Date*</label>
                                    <input type="text" id="vcDate" name="vcDate" value="{{ $header->voucher_date }}" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="period">Period*</label>
                                    <select class="select2 form-control" id="period" name="period" required>
                                        <option value=""></option>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <option value="{{ $i }}" {{$i == $header->period ? "selected" : ""}}>{{ $i }}</option>
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
                                            <option value="{{ $val->account }}" {{$val->account == $header->receive_from ? "selected" : ""}} >{{ $val->account }}|{{ $val->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-group">
                                        <label for="totalAmount">Amount*</label>
                                        <input type="text" id="totalAmount" name="totalAmount" value="{{ $header->amount }}" class="form-control text-right numeral-mask" maxlength="12" required/>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-10">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }}</textarea>
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
                                    <td class="isian" style="width: 30%">
                                        <label>Account</label>
                                    </td>
                                    <td class="isian" style="">
                                        <label>Description</label>
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
                    <table class="" style="width: 98%;table-layout: fixed;">
                        <tbody>
                            <tr>
                                <td class="isian" style="width: 30%">
                                    <label>Total</label>
                                </td>
                                <td class="isian" style="">
                                </td>
                                <td class="isian" style="">
                                </td>
                                <td class="isian" style="width: 10%">
                                    <input type="text" class="form-control-plaintext numeral-mask text-right" id="vcTotalDebit" disabled />
                                </td>
                                <td class="isian" style="width: 10%">
                                    <input type="text" class="form-control-plaintext numeral-mask text-right" id= "vcTotalCredit" disabled />
                                </td>
                                <td class="isian text-center" style="width: 5%">
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
                    <div class="form-row">
                        <div class="col-md-12">
                            <a href="{{ route('kasPenerimaan.index') }}" class="btn btn-light">Back</a>
                            @if( $approveValidate ? $approveValidate[0]->validate : '')
                                <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                            @if( $status =='NEW')
                                <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate" >Update</button>
                            @endif
                            @else
                                @if( !$approveValidate && $status =='NEW')
                                    <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate" >Update</button>
                                @endif
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            @if($val->status == true)
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-success mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="check" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-danger mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="x" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->petugas }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
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
    
    $(document).ready(function(){           
        validateFormToast('frmAdd');

        let detail = {!!  $details !!};
        for(let i=0;i<detail.length;i++){
            vcAccount = detail[i].account;
            vcDesc = detail[i].description;
            vcCc = detail[i].cost_center;
            vcDebit = detail[i].debit;
            vcCredit = detail[i].credit;
            add_new_row(vcAccount,vcDesc,vcCc,vcDebit,vcCredit);
        }
    });
    
    vcDate = $('#vcDate');
    if (vcDate.length) {
        vcDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }
    
    function reloadPage(){
        window.location.reload();
    }

    $("#cmdCancel").click(function(){
        reloadPage();
    });

    $("#cmdNew").click(function(){
        reloadPage();
    });

    $("#cmdUpdate").click(function(){  
        let objTotalVcDebit= $('#vcTotalDebit').val().replace(/,/gi, '') || 0;
        let objTotalVcCredit= $('#vcTotalCredit').val().replace(/,/gi, '') || 0;
        let vcDate = $('#vcDate').val();
        let period = $('#period').val();
        let totalAmount = $('#totalAmount').val().replace(/,/gi, '') || 0;
        let note = $('#note').val();
        let recFrom = $('#recFrom').val();
        let vcNumber = $('#voucherNumber').val();
    
        if (((parseInt(objTotalVcDebit)-parseInt(objTotalVcCredit)) == 0) && (parseInt(objTotalVcCredit)==parseInt(totalAmount))){
            if (!$("#frmAdd")[0].checkValidity()){
                $("#frmAdd").submit();
            }else{   
                $('.disabled-el').removeAttr('disabled');
                // ambil semua data article
                let objvcDesc= $('#item_row input[name="vcDesc[]"]');
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
                        let sCc=objVcCc.eq(i).val();
                        let sDebit=objVcDebit.eq(i).val().replace(/,/gi, '') || 0;
                        let sCredit=objVcCredit.eq(i).val().replace(/,/gi, '') || 0;

                        if ((sDesc!=='') && ((sDebit + sCredit) > 0) && (sAccount!=='') && (sCc!=='')){
                            details.push({
                                "account":sAccount,
                                "description":sDesc,
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
                        url: "{{ route('kasPenerimaan.update') }}",
                        data: {
                            details:JSON.stringify(details),
                            vcDate:vcDate,
                            period:period,
                            note:note,
                            totalAmount:totalAmount,
                            recFrom:recFrom,
                            vcNumber:vcNumber,
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
                                // $('#cmdUpdate').attr('disabled','disabled');
                                // $('#addNewRow').attr('disabled','disabled');
                            }
                        },
                        error: function(error) {
                            console.log(error);
                        }
                    });

                }else{
                    Swal.fire('Warning..',pesan,'warning');
                }
            }
        }else{
            Swal.fire('Warning..',"Data belum balance",'warning');
        }
    });

    let cloneCount=1;
    function add_new_row(account,desc,cc,debit,credit) {
        $("#item_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#item_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#vcDesc').attr('id', 'vcDesc'+ cloneCount);
        $("#new_row"+ cloneCount).find('#account').attr('id', 'account'+ cloneCount);
        $("#new_row"+ cloneCount).find('#vcCc').attr('id', 'vcCc'+ cloneCount);
        $("#new_row"+ cloneCount).find('#vcDebit').attr('id', 'vcDebit'+ cloneCount);
        $("#new_row"+ cloneCount).find('#vcCredit').attr('id', 'vcCredit'+ cloneCount);

        accList('account','account'+ cloneCount,account);
        
        $("#account"+cloneCount).select2();
        $("#vcCc"+cloneCount).select2();

        // $("#account"+cloneCount).val(account).trigger('change');;
        $("#vcCc"+cloneCount).val(cc).trigger('change');;
        $("#vcDesc"+cloneCount).val(desc);
        $("#vcDebit"+cloneCount).val(debit != 0 ? debit : '');
        $("#vcCredit"+cloneCount).val(credit != 0 ? credit : '');

        $('#remove_button').tooltip();
        activate_angka();
        mask_thousand();
        hitungTotal();
        hitungGrandTotal();
        $('[data-toggle="tooltip"]').tooltip();
    };

    function accList(dependent,obj,account) {
      $.ajax({
        url:"{{route('dynamic.dependent')}}",
        method:"POST",
        data:{
            dependent:dependent
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val(account).trigger('change');
        }
      })
    }

    $("#cmdApprove").click(function(){    
        let vcNumber = $('#voucherNumber').val();
        $.ajax({
            type: "get",
            url: "{{ route('kasPenerimaan.approve') }}",
            data: {
                vcNumber:vcNumber
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
                    $('#voucherNumber').attr('disabled','disabled');
                    $('#cmdApprove').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');  
                    $('#cmdUpdate').attr('disabled','disabled');
                    location.reload();       
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection