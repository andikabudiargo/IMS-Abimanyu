@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
{{-- @include('partials.alert') --}}
<section id="add-index">
    <div class="row">
        <div class="col-6">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">accounts</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('account.store') }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="account">Account*</label>
                                <input type="text" id="account" name="account" class="form-control text-uppercase" value="{{ old('account') }}"  required maxlength="50" autofocus />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="desc">Description*</label>
                                <input type="text" id="desc" name="desc" class="form-control" value="{{ old('desc') }}" required  maxlength="100"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-5">
                                <label for="openingBalance">Opening balance</label>
                                <input type="text" id="openingBalance" name="openingBalance" oninput='inputDecimal(this)' value="{{ old('openingBalance') }}" class="form-control numeral-mask-digit" maxlength="20"/>
                            </div>
                        </div>
                        {{-- <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="group">Group</label>
                                <select class="select2 w-100" id="group" name="group">
                                    <option value="">All</option>
                                    @foreach($groups as $val)
                                        <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div> --}}
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="type">Account Type*</label>
                                <select class="select2 w-100" id="type" name="type" required>
                                    <option value=""></option>
                                    @foreach($types as $val)
                                        <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="subAccount">Sub account*</label>
                                <select class="select2 w-100" id="subAccount" name="subAccount" required>
                                    <option value=""></option>
                                    @foreach($subAcc as $val)
                                        <option value="{{$val->sub_code}}">{{$val->description}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="accHeader">Header</label>
                                <select class="select2 w-100" id="accHeader" name="accHeader">
                                    <option value=""></option>
                                    <option label="HEADER">HEADER</option>
                                    <option label="DETAIL">DETAIL</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="accDebitCredit">Header</label>
                                <select class="select2 w-100" id="accDebitCredit" name="accDebitCredit">
                                    <option value=""></option>
                                    <option label="DEBIT">DEBIT</option>
                                    <option label="KREDIT">KREDIT</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="accFinalStatement">Final Statement</label>
                                <select class="select2 w-100" id="accFinalStatement" name="accFinalStatement">
                                    <option value=""></option>
                                    <option label="NERACA">NERACA</option>
                                    <option label="LABA_RUGI">LABA RUGI</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="note">Notes</label>
                                <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ old('note') }}</textarea>
                            </div>
                        </div>
                        {{-- <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="dept">Dept</label>
                                <select class="select2 w-100" id="dept" name="dept">
                                    <option value="">All</option>
                                    @foreach($depts as $val)
                                        <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div> --}}
                        {{-- <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="cashBank">Cash/Bank</label>
                                <select class="select2 w-100" id="cashBank" name="cashBank">
                                    <option value=""></option>
                                    <option label="cash">Cash</option>
                                    <option label="bank">Bank</option>
                                </select>
                            </div>
                        </div> --}}
                        
                        <div class="row">
                            <div class="col-12">
                                <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">Back</a>
                                <button class="btn btn-success" type="button" id="cmdSave" name="cmdSave">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('styles')
<style>
    textarea {
        resize: none;
    }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    $(document).ready(function(){           
        validateFormToast("frmAdd");
        // $('#group').val('{{ Request::old('group') }}').trigger('change');
        // $('#dept').val('{{ Request::old('dept') }}').trigger('change');
        // $('#type').val('{{ Request::old('type') }}').trigger('change');
        // $('#cashBank').val('{{ Request::old('cashBank') }}').trigger('change');
        mask_thousand();
        mask_thousand_digit(2);
    });

    let delayTimer;
    function inputDecimal(ele) {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(function() {
            let nilai = ele.value.replace(/,/gi, '') || 0;;
            ele.value = humanizeNumber(parseFloat(nilai).toFixed(2)).toString();
        }, 1100); 
    }

    $("#cmdSave").click(function(){       
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{ 
            $('.disabled-el').removeAttr('disabled');
            $("#frmAdd").submit(); // Submit the form
        }
    });

    $("#cmdCancel").click(function() {
        $(".select2").val('').trigger('change');
        $("#frmAdd").validate().resetForm();
        $("#account").focus();
    });

</script>
@endsection