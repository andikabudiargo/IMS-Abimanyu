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
                            <div class="form-group col-md-12">
                                <label class="form-label" for="type">Account Type*</label>
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <select class="select2 w-100" id="type" name="type" required>
                                            <option value=""></option>
                                            @foreach($types as $val)
                                                <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary ml-1" id="btnAddType" data-toggle="tooltip" title="Tambah Account Type"><i data-feather="plus" class="feather-14"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="subAccount">Sub account</label>
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <select class="select2 w-100" id="subAccount" name="subAccount" disabled>
                                            <option value="">Choose Account Type first</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary ml-1" id="btnAddSub" data-toggle="tooltip" title="Tambah Sub Account"><i data-feather="plus" class="feather-14"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                             <div class="form-group col-md-6">
                                <label class="form-label" for="accHeader">Category</label>
                                <select class="select2 w-100" id="accHeader" name="accHeader">
                                    <option value=""></option>
                                    <option label="HEADER">HEADER</option>
                                    <option label="DETAIL">DETAIL</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="account">Account*</label>
                                <select class="select2 w-100" id="account" name="account" required disabled>
                                    <option value="">Choose Account Type first</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="desc">Description*</label>
                                <input type="text" id="desc" name="desc" class="form-control" value="{{ old('desc') }}" required  maxlength="100"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="accDebitCredit">Normal Balance</label>
                                <select class="select2 w-100" id="accDebitCredit" name="accDebitCredit">
                                    <option value=""></option>
                                    <option label="DEBIT">DEBIT</option>
                                    <option label="KREDIT">KREDIT</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="openingBalance">Opening balance</label>
                                <input type="text" id="openingBalance" name="openingBalance" oninput='inputDecimal(this)' value="{{ old('openingBalance') }}" class="form-control numeral-mask-digit" maxlength="20"/>
                            </div>
                             <div class="form-group col-md-4">
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

<div class="modal fade" id="mdlAddType" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Account Type</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="frmAddType" autocomplete="off">
            <div class="form-group">
                <label for="newTypeCode">Code*</label>
                <input type="text" id="newTypeCode" class="form-control text-uppercase" maxlength="10" required />
            </div>
            <div class="form-group">
                <label for="newTypeName">Name*</label>
                <input type="text" id="newTypeName" class="form-control" maxlength="100" required />
            </div>
            <div class="text-danger" id="errAddType"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="cmdSaveType">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="mdlAddSub" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Sub Account</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="frmAddSub" autocomplete="off">
            <div class="form-group">
                <label for="newSubCode">Code* <small class="text-muted">(contoh: 1100.80, kelipatan 10 dari Account Type)</small></label>
                <input type="text" id="newSubCode" class="form-control text-uppercase" maxlength="50" required />
            </div>
            <div class="form-group">
                <label for="newSubDesc">Description*</label>
                <input type="text" id="newSubDesc" class="form-control" maxlength="100" required />
            </div>
            <div class="text-danger" id="errAddSub"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="cmdSaveSub">Save</button>
      </div>
    </div>
  </div>
</div>

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
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    $(document).ready(function(){
        validateFormToast("frmAdd");
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

    // Sub Account & Account (nomor akun) sama-sama mengikuti Account Type yang dipilih.
    // Sub Account dipakai untuk kategori, Account Number untuk cari nomor yang belum dipakai
    // di seluruh range type tsb (contoh: pilih 1100 -> tawarkan 1100.01 s/d 1100.99 yang kosong).
    $('#type').on('change', function(){
        let typeCode = $(this).val();

        if(!typeCode){
            resetSelect('#subAccount', 'Choose Account Type first');
            resetSelect('#account', 'Choose Account Type first');
            return;
        }

        $.ajax({
            url: "{{ route('dynamic.dependent') }}",
            method: 'POST',
            data: { dependent: 'accSub', value: typeCode },
            success: function(result){
                let hasSub = $(result).filter('option').length > 0;
                $('#subAccount')
                    .html('<option value="">'+(hasSub ? 'Choose Sub Account' : 'No Sub Account for this type')+'</option>' + result)
                    .prop('disabled', false)
                    .trigger('change');
            }
        });

        $.ajax({
            url: "{{ route('dynamic.dependent') }}",
            method: 'POST',
            data: { dependent: 'accNumberRange', value: typeCode },
            success: function(result){
                $('#account').html('<option value="">Choose Account Number</option>' + result).prop('disabled', false).trigger('change');
            }
        });
    });

    function resetSelect(selector, placeholder){
        $(selector).html('<option value="">'+placeholder+'</option>').prop('disabled', true).trigger('change');
    }

    // Tambah Account Type lewat modal, tanpa pindah halaman
    $('#btnAddType').click(function(){
        $('#errAddType').text('');
        $('#frmAddType')[0].reset();
        $('#mdlAddType').modal('show');
    });

    $('#cmdSaveType').click(function(){
        let code = $('#newTypeCode').val().trim();
        let name = $('#newTypeName').val().trim();
        if(!code || !name){
            $('#errAddType').text('Code dan Name wajib diisi.');
            return;
        }

        $.ajax({
            url: "{{ route('accType.store') }}",
            method: 'POST',
            data: { kode: code, nama: name },
            headers: { Accept: 'application/json' },
            success: function(result){
                let option = new Option(result.code+' - '+result.name, result.code, true, true);
                $('#type').append(option).trigger('change');
                $('#mdlAddType').modal('hide');
            },
            error: function(xhr){
                let msg = xhr.responseJSON && xhr.responseJSON.errors
                    ? Object.values(xhr.responseJSON.errors).flat().join(' ')
                    : (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan Account Type.';
                $('#errAddType').text(msg);
            }
        });
    });

    // Tambah Sub Account lewat modal, mengikuti Account Type yang sedang dipilih
    $('#btnAddSub').click(function(){
        let typeCode = $('#type').val();
        if(!typeCode){
            alert('Pilih Account Type dahulu.');
            return;
        }
        $('#errAddSub').text('');
        $('#frmAddSub')[0].reset();
        $('#mdlAddSub').modal('show');
    });

    $('#cmdSaveSub').click(function(){
        let typeCode = $('#type').val();
        let subCode = $('#newSubCode').val().trim();
        let desc = $('#newSubDesc').val().trim();
        if(!subCode || !desc){
            $('#errAddSub').text('Code dan Description wajib diisi.');
            return;
        }

        $.ajax({
            url: "{{ route('accSub.store') }}",
            method: 'POST',
            data: { typeCode: typeCode, subCode: subCode, desc: desc },
            headers: { Accept: 'application/json' },
            success: function(result){
                let option = new Option(result.description, result.sub_code, true, true);
                $('#subAccount').append(option).trigger('change');
                $('#mdlAddSub').modal('hide');
            },
            error: function(xhr){
                let msg = xhr.responseJSON && xhr.responseJSON.errors
                    ? Object.values(xhr.responseJSON.errors).flat().join(' ')
                    : (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan Sub Account.';
                $('#errAddSub').text(msg);
            }
        });
    });

</script>
@endsection
