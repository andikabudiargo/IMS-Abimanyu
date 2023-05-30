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
                                    <label for="pcNumber">Petty Cash Code</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="pcNumber" name="pcNumber" class="form-control disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="voucherNumber">Voucher Number*</label>
                                    <input type="text" id="voucherNumber" name="voucherNumber" class="form-control" required/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="pcDate">Date*</label>
                                    <input type="text" id="pcDate" name="pcDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-2">
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
                                <div class="form-group col-md-2">
                                    <label for="currency">Currency*</label>
                                    <select class="select2 form-control" id="currency" name="currency" required>
                                        @foreach($currency as $val)
                                        <option value="{{$val}}">{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <div class="form-group">
                                        <label for="kurs">Kurs</label>
                                        <input type="text" id="kurs" name="kurs" class="form-control angka" maxlength="6"  />
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-11">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-12">
                                    <a href="{{ route('pettyCashs.index') }}" class="btn btn-light">Back</a>
                                    <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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
                                    <td class="isian" style="">
                                        <label>Description</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>CG</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Debit</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Credit</label>
                                    </td>
                                    <td class="isian" style="">
                                        <label>Account</label>
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
                                <td class="isian" style="">
                                    <label>Total</label>
                                </td>
                                <td class="isian" style="width: 5%">
                                    
                                </td>
                                <td class="isian" style="width: 10%">
                                    <input type="text" class="form-control-plaintext numeral-mask text-right" id="pcTotalCashIn" disabled />
                                </td>
                                <td class="isian" style="width: 10%">
                                    <input type="text" class="form-control-plaintext numeral-mask text-right" id= "pcTotalCashOut" disabled />
                                </td>
                                <td class="isian" style="">
                                </td>
                                <td class="isian text-center" style="width: 5%">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@include('pettyCash.addArticle')
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
    var availableTags ="{{ $pettyCash }}";
    availableTags=availableTags.replace(/[[\]]/g,'');
    availableTags=availableTags.replace(/&quot;/g,'').split(",");
        
    $(document).ready(function(){           
        validateForm('frmAdd');
        $('#orderDate').val(currentDate);
        add_new_row();
        add_new_row();
        add_new_row();
        add_new_row();
        add_new_row();
    });
    
    pcDate = $('#pcDate');
    if (pcDate.length) {
        pcDate.flatpickr({
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

    $("#cmdSave").click(function(){  
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{   
            $('.disabled-el').removeAttr('disabled');
            // ambil semua data article
            let objPcDesc= $('#item_row input[name="pcDesc[]"]');
            let objPcCg= $('#item_row input[name="pcCg[]"]');
            let objPcCashIn= $('#item_row input[name="pcCashIn[]"]');
            let objPcCashOut= $('#item_row input[name="pcCashOut[]"]');
            let objAccount= $('#item_row select[name="account[]"]');
            let details = []; 
            let flag=0; 
            let pesan="";

            objPcDesc.map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let sDesc=$this.val();
                    let sCg=objPcCg.eq(i).val();
                    let sCashIn=objPcCashIn.eq(i).val().replace(/,/gi, '') || 0;
                    let sCashOut=objPcCashOut.eq(i).val().replace(/,/gi, '') || 0;
                    let sAccount=objAccount.eq(i).val();

                    //jquery
                    //cek apakah article ada yang double input ato ngk
                    let obj = $.grep(details, function(obj){
                        return obj.description === sDesc;
                    })[0];
                    
                    // if(obj) {
                    //     pesan +="Description "+sDesc+" entered more than once !! <br>"; 
                    //     flag=1;
                    // } else {
                        if ((sDesc!=='') && ((sCashIn + sCashOut) > 0)){
                            details.push({
                                "description":sDesc,
                                "cg":sCg,
                                "cash_in":sCashIn,
                                "cash_out":sCashOut,
                                "account":sAccount
                            });
                        }
                    // }             
                }
            });

            if (details.length == 0){
                pesan +="Detail must be filled Out completely <br>"; 
                flag=1;
            }

            if (flag == 0){

                // let pcNumber = $('#pcNumber').val();
                let voucherNumber = $('#voucherNumber').val();
                let pcDate = $('#pcDate').val();
                let period = $('#period').val();
                let currency = $('#currency').val();
                let kurs = $('#kurs').val() || 1;
                let note = $('#note').val();

                $.ajax({
                    type: "post",
                    url: "{{ route('pettyCash.store') }}",
                    data: {
                        details:JSON.stringify(details),
                        // pcNumber:pcNumber,
                        voucherNumber:voucherNumber,
                        pcDate:pcDate,
                        period:period,
                        currency:currency,
                        kurs:kurs,
                        note:note
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            let message="";
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }                        
                            $('#pcNumber').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#pcNumber').val(data.pcNumber);
                            $('#pcNumber').attr('disabled','disabled');
                            $('#cmdSave').attr('disabled','disabled');
                            $('#addNewRow').attr('disabled','disabled');
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
    
    });

    let cloneCount=1;
    function add_new_row() {
        $("#item_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#item_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#pcDesc').attr('id', 'pcDesc'+ cloneCount);
        $("#new_row"+ cloneCount).find('#account').attr('id', 'account'+ cloneCount);
        accList('account','account'+ cloneCount);
        $("#account"+cloneCount).select2();
        $('#remove_button').tooltip();
        tombolPanah('pcCashIn');
        tombolPanah('pcCashOut');
        activate_angka();
        mask_thousand();
        hitungTotal();
        hitungGrandTotal();
        $('[data-toggle="tooltip"]').tooltip();

        $("#pcDesc"+ cloneCount).autocomplete({
            source: availableTags
        });

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

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection