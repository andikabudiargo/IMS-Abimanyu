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
                    <form id="frmAdd" name="frmAdd" action="{{ route('account.update',['id'=>Crypt::encryptString($accounts->id)]) }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="account">Account</label>
                                <input type="text" id="account" name="account" class="form-control text-uppercase disabled-el" value="{{old('account',$accounts->account)}}"  disabled maxlength="50" autofocus />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="desc">Description*</label>
                                <input type="text" id="desc" name="desc" class="form-control" value="{{ old('desc',$accounts->description) }}" required  maxlength="100"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-5">
                                <label for="openingBalance">Opening balance</label>
                                <input type="text" id="openingBalance" name="openingBalance" value="{{ old('openingBalance',$accounts->opening_balance) }}" class="form-control numeral-mask angka" maxlength="15"/>
                            </div>
                        </div>
                        {{-- <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="group">Group</label>
                                <select class="select2 w-100" id="group" name="group">
                                    @foreach($groups as $val)
                                        <option value="{{$val->code}} {{ old('group',$accounts->group_code)==$val->code ? 'selected' : '' }}">{{$val->code}} - {{$val->name}}</option>
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
                                        <option value="{{$val->code}}" {{ old('type',$accounts->type_code)==$val->code ? 'selected' : '' }} >{{ $val->code }} - {{$val->name}}</option>
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
                                        <option value="{{$val->sub_code}}" {{ old('subAccount',$accounts->parent_id)== $val->sub_code ? 'selected' : '' }}>{{$val->description}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="dept">Dept</label>
                                <select class="select2 w-100" id="dept" name="dept">
                                    @foreach($depts as $val)
                                        <option value="{{$val->code}}" {{ old('dept',$accounts->dept_code)== $val->code ? 'selected' : '' }}>{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div> --}}
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="cashBank">Cash/Bank</label>
                                <select class="select2 w-100" id="cashBank" name="cashBank">
                                    <option value=""></option>
                                    <option value="cash" {{ old('cashBank',$accounts->cash_bank) == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank" {{ old('cashBank',$accounts->cash_bank) == 'bank' ? 'selected' : '' }}>Bank</option>
                                </select>
                            </div>
                        </div>
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
        // $('#group').val('{{ Request::old('group',$accounts->group_code) }}').trigger('change');
        // $('#dept').val('{{ Request::old('dept',$accounts->dept_code) }}').trigger('change');
        // $('#type').val('{{ Request::old('type',$accounts->type_code) }}').trigger('change');
        // $('#cashBank').val('{{ Request::old('cashBank',$accounts->cash_bank) }}').trigger('change');
    });

    $("#cmdSave").click(function(){       
        $('.disabled-el').removeAttr('disabled');
        $("#frmAdd").submit(); // Submit the form
    });

    $("#cmdCancel").click(function() {
        $(".select2").val('').trigger('change');
        $("#frmAdd").validate().resetForm();
        $("#account").focus();
    });

    numeralMask = $('.numeral-mask');
    if (numeralMask.length) {
        new Cleave(numeralMask, {
        numeral: true,
        numeralThousandsGroupStyle: 'thousand'
        });
    }
</script>
@endsection