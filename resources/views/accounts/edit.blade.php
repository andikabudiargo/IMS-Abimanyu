@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<section id="add-index">
    <div class="row">
        <div class="col-6">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">accounts</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('account.update',['id'=> $accounts->id]) }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="account">Account</label>
                                <input type="text" id="account" name="account" class="form-control text-uppercase disabled-el" value="{{old('account',$accounts->account)}}"  disabled maxlength="50" autofocus />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="desc">Description</label>
                                <input type="text" id="desc" name="desc" class="form-control" value="{{ old('desc',$accounts->description) }}" required  maxlength="100"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-5">
                                <label for="openingBalance">Opening balance</label>
                                <input type="text" id="openingBalance" name="openingBalance" value="{{ old('openingBalance',$accounts->opening_balance) }}" class="form-control numeral-mask angka" maxlength="15"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="group">Group</label>
                                <select class="select2 w-100" id="group" name="group">
                                    <option label=""></option>
                                    @foreach($groups as $val)
                                        <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="type">Account Type</label>
                                <select class="select2 w-100" id="type" name="type">
                                    <option label=""></option>
                                    @foreach($types as $val)
                                        <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="dept">Dept</label>
                                <select class="select2 w-100" id="dept" name="dept">
                                    <option label=""></option>
                                    @foreach($depts as $val)
                                        <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="cashBank">Cash/Bank</label>
                                <select class="select2 w-100" id="cashBank" name="cashBank">
                                    <option label=""></option>
                                    <option label="cash">Cash</option>
                                    <option label="bank">Bank</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
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
        $("#frmAdd").validate({
            invalidHandler: function(event, validator) {
            let errors = validator.numberOfInvalids();
            if (errors) {
                var message = errors == 1
                    ? 'You missed 1 field. It has been highlighted'
                    : 'You missed ' + errors + ' fields. They have been highlighted';
                $("#alert-message .alert-body").html(message);
                $("#alert-message").show();
                $("#alert-message").fadeTo(5000, 500).slideUp(500, function(){
                    $("#alert-message").slideUp(500);
                });
            } else {
                $("#alert-message").hide();
            }
        }
        }).settings.ignore = "";
        
        $('#group').val('{{ Request::old('group',$accounts->group_code) }}').trigger('change');
        $('#dept').val('{{ Request::old('dept',$accounts->dept_code) }}').trigger('change');
        $('#type').val('{{ Request::old('type',$accounts->type_code) }}').trigger('change');
        $('#cashBank').val('{{ Request::old('cashBank',$accounts->cash_bank) }}').trigger('change');
        
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