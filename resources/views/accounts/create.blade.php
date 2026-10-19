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
                                        <select class="select2 w-100" id="subAccount" disabled>
                                            <option value="">Choose Account Type first</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary ml-1" id="btnAddSub" data-toggle="tooltip" title="Tambah Sub Account"><i data-feather="plus" class="feather-14"></i></button>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="parentId" name="subAccount" value="" />
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
                        <div class="row d-none" id="rowParentHeader">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="parentHeader">Parent Header <small class="text-muted">(kosongkan kalau langsung di bawah Sub Account)</small></label>
                                <select class="select2 w-100" id="parentHeader" disabled>
                                    <option value="">Choose Sub Account first</option>
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
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Kelola Account Type</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive mb-3" style="max-height:220px;overflow-y:auto;">
          <table class="table table-sm table-striped mb-0">
            <thead class="thead-light"><tr><th>Code</th><th>Name</th><th></th></tr></thead>
            <tbody id="listType"></tbody>
          </table>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong id="lblFormType">Tambah Baru</strong>
            <a href="javascript:;" id="btnCancelEditType" class="d-none">Batal edit, tambah baru</a>
        </div>
        <form id="frmAddType" autocomplete="off">
            <input type="hidden" id="editTypeId" value="" />
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
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" id="cmdSaveType">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="mdlAddSub" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Kelola Sub Account</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="frmAddSub" autocomplete="off">
            <input type="hidden" id="editSubId" value="" />
            <div class="form-group">
                <label for="newSubType">Account Type*</label>
                <select class="select2 w-100" id="newSubType" required>
                    <option value=""></option>
                    @foreach($types as $val)
                        <option value="{{$val->code}}">{{$val->code}} - {{$val->name}}</option>
                    @endforeach
                </select>
            </div>
            <div class="table-responsive mb-3" style="max-height:200px;overflow-y:auto;">
              <table class="table table-sm table-striped mb-0">
                <thead class="thead-light"><tr><th>Code</th><th>Description</th><th></th></tr></thead>
                <tbody id="listSub"></tbody>
              </table>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong id="lblFormSub">Tambah Baru</strong>
                <a href="javascript:;" id="btnCancelEditSub" class="d-none">Batal edit, tambah baru</a>
            </div>
            <div class="form-group">
                <label for="newSubCode">Code*</label>
                <select class="select2 w-100" id="newSubCode" required disabled>
                    <option value="">Choose Account Type first</option>
                </select>
            </div>
            <div class="form-group">
                <label for="newSubDesc">Description*</label>
                <input type="text" id="newSubDesc" class="form-control" maxlength="100" required />
            </div>
            <div class="text-danger" id="errAddSub"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
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

    // Struktur COA: Account Type (1100) -> Sub Account (1100.10) -> Account 2 segmen
    // di dekade sub-nya (1100.13, HEADER atau DETAIL) -> kalau DETAIL boleh juga nested
    // 3 segmen di bawah HEADER yang ada (1100.13.1), lewat field Parent Header.

    // 1) Account Type dipilih -> load Sub Account-nya
    $('#type').on('change', function(){
        let typeCode = $(this).val();

        if(!typeCode){
            resetSelect('#subAccount', 'Choose Account Type first');
            resetSelect('#account', 'Choose Account Type first');
            resetParentHeader();
            return;
        }

        $.ajax({
            url: "{{ route('dynamic.dependent') }}",
            method: 'POST',
            data: { dependent: 'accSub', value: typeCode },
            success: function(result){
                $('#subAccount').html(result).prop('disabled', false).trigger('change');
            }
        });
    });

    // 2) Sub Account dipilih -> load daftar HEADER di bawahnya (buat opsi Parent Header)
    //    dan set parent_id sementara = sub account (dipakai kalau Category != DETAIL atau
    //    Parent Header dikosongkan)
    $('#subAccount').on('change', function(){
        let subCode = $(this).val();
        $('#parentHeader').val('');
        $('#parentId').val(subCode || '');

        if(!subCode){
            resetSelect('#account', 'Choose Sub Account first');
            resetParentHeader();
            return;
        }

        refreshAccountNumber();

        $.ajax({
            url: "{{ route('dynamic.dependent') }}",
            method: 'POST',
            data: { dependent: 'accHeaderBySub', value: subCode },
            success: function(result){
                $('#parentHeader').html(result).prop('disabled', false).trigger('change');
            }
        });
    });

    // 3) Category berubah -> tampilkan/sembunyikan field Parent Header (cuma relevan untuk DETAIL)
    $('#accHeader').on('change', function(){
        let isDetail = $(this).val() === 'DETAIL';
        $('#rowParentHeader').toggleClass('d-none', !isDetail);
        if(!isDetail){
            $('#parentHeader').val('').trigger('change');
        }
        refreshAccountNumber();
    });

    // 4) Parent Header berubah -> kalau dipilih, parent_id = header itu & nomor akun jadi 3 segmen;
    //    kalau dikosongkan, balik ke parent_id = sub account & nomor akun 2 segmen dekade
    $('#parentHeader').on('change', function(){
        $('#parentId').val($(this).val() || $('#subAccount').val() || '');
        refreshAccountNumber();
    });

    function refreshAccountNumber(){
        let isDetail = $('#accHeader').val() === 'DETAIL';
        let headerCode = $('#parentHeader').val();
        let subCode = $('#subAccount').val();

        if(isDetail && headerCode){
            $.ajax({
                url: "{{ route('dynamic.dependent') }}",
                method: 'POST',
                data: { dependent: 'accDetailNumberRange', value: headerCode },
                success: function(result){
                    $('#account').html(result).prop('disabled', false).trigger('change');
                }
            });
            return;
        }

        if(!subCode){
            resetSelect('#account', 'Choose Sub Account first');
            return;
        }

        $.ajax({
            url: "{{ route('dynamic.dependent') }}",
            method: 'POST',
            data: { dependent: 'accNumberRange', value: subCode },
            success: function(result){
                $('#account').html(result).prop('disabled', false).trigger('change');
            }
        });
    }

    function resetSelect(selector, placeholder){
        $(selector).html('<option value="">'+placeholder+'</option>').prop('disabled', true).trigger('change');
    }

    function resetParentHeader(){
        $('#rowParentHeader').addClass('d-none');
        resetSelect('#parentHeader', 'Choose Sub Account first');
    }

    // ===== Kelola Account Type (list + tambah + edit + hapus), semuanya di dalam modal =====
    $('#btnAddType').click(function(){
        resetTypeForm();
        loadTypeList();
        $('#mdlAddType').modal('show');
    });

    function resetTypeForm(){
        $('#errAddType').text('');
        $('#editTypeId').val('');
        $('#newTypeCode').val('').prop('disabled', false);
        $('#newTypeName').val('');
        $('#lblFormType').text('Tambah Baru');
        $('#btnCancelEditType').addClass('d-none');
    }

    $('#btnCancelEditType').click(resetTypeForm);

    function loadTypeList(){
        $.ajax({
            url: "{{ route('accType.quickList') }}",
            method: 'GET',
            success: function(rows){
                let html = '';
                rows.forEach(function(row){
                    html += '<tr>'
                        + '<td>'+row.code+'</td>'
                        + '<td>'+row.name+'</td>'
                        + '<td class="text-right">'
                        + '<a href="javascript:;" class="btnEditType mr-2" data-id="'+row.id+'" data-code="'+row.code+'" data-name="'+row.name+'"><i data-feather="edit-2" class="feather-14"></i></a>'
                        + '<a href="javascript:;" class="btnDelType text-danger" data-id="'+row.id+'" data-code="'+row.code+'"><i data-feather="trash-2" class="feather-14"></i></a>'
                        + '</td></tr>';
                });
                $('#listType').html(html);
                featherSVG();
            }
        });
    }

    $(document).on('click', '.btnEditType', function(){
        $('#errAddType').text('');
        $('#editTypeId').val($(this).data('id'));
        $('#newTypeCode').val($(this).data('code')).prop('disabled', true);
        $('#newTypeName').val($(this).data('name'));
        $('#lblFormType').text('Edit '+$(this).data('code'));
        $('#btnCancelEditType').removeClass('d-none');
    });

    $(document).on('click', '.btnDelType', function(){
        if(!confirm('Hapus Account Type '+$(this).data('code')+'?')){
            return;
        }
        let id = $(this).data('id');
        $.ajax({
            url: "{{ route('accType.quickDestroy') }}",
            method: 'POST',
            data: { id: id },
            headers: { Accept: 'application/json' },
            success: function(){
                loadTypeList();
                $('#type option[value="'+$(this).data('code')+'"]').remove();
                $('#type').trigger('change');
                if($('#editTypeId').val() == id){
                    resetTypeForm();
                }
            }.bind(this),
            error: function(xhr){
                alert((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus Account Type.');
            }
        });
    });

    $('#cmdSaveType').click(function(){
        let editId = $('#editTypeId').val();
        let code = $('#newTypeCode').val().trim();
        let name = $('#newTypeName').val().trim();
        if(!code || !name){
            $('#errAddType').text('Code dan Name wajib diisi.');
            return;
        }

        if(editId){
            $.ajax({
                url: "{{ route('accType.quickUpdate') }}",
                method: 'POST',
                data: { id: editId, nama: name },
                headers: { Accept: 'application/json' },
                success: function(result){
                    $('#type option[value="'+result.code+'"]').text(result.code+' - '+result.name);
                    resetTypeForm();
                    loadTypeList();
                },
                error: function(xhr){
                    $('#errAddType').text((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan Account Type.');
                }
            });
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
                resetTypeForm();
                loadTypeList();
            },
            error: function(xhr){
                let msg = xhr.responseJSON && xhr.responseJSON.errors
                    ? Object.values(xhr.responseJSON.errors).flat().join(' ')
                    : (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan Account Type.';
                $('#errAddType').text(msg);
            }
        });
    });

    // ===== Kelola Sub Account (list + tambah + edit + hapus), semuanya di dalam modal =====
    // Account Type di modal di-prefill dari form utama (kalau sudah dipilih), tapi tetap
    // bisa diganti. Code tambah baru = dropdown nomor kelipatan 10 yang belum dipakai
    // (1100.10, 1100.20, dst). Code tidak bisa diubah saat edit, cuma description.
    $('#btnAddSub').click(function(){
        resetSubForm();

        let typeCode = $('#type').val();
        $('#newSubType').val(typeCode || '').trigger('change');
        $('#mdlAddSub').modal('show');
    });

    function resetSubForm(){
        $('#errAddSub').text('');
        $('#editSubId').val('');
        $('#newSubDesc').val('');
        $('#newSubCode').prop('disabled', false);
        $('#lblFormSub').text('Tambah Baru');
        $('#btnCancelEditSub').addClass('d-none');
    }

    $('#btnCancelEditSub').click(function(){
        resetSubForm();
        $('#newSubType').trigger('change');
    });

    function loadSubList(typeCode){
        if(!typeCode){
            $('#listSub').html('');
            return;
        }
        $.ajax({
            url: "{{ route('accSub.quickList') }}",
            method: 'GET',
            data: { typeCode: typeCode },
            success: function(rows){
                let html = '';
                rows.forEach(function(row){
                    html += '<tr>'
                        + '<td>'+row.sub_code+'</td>'
                        + '<td>'+row.description+'</td>'
                        + '<td class="text-right">'
                        + '<a href="javascript:;" class="btnEditSub mr-2" data-id="'+row.id+'" data-code="'+row.sub_code+'" data-desc="'+row.description+'"><i data-feather="edit-2" class="feather-14"></i></a>'
                        + '<a href="javascript:;" class="btnDelSub text-danger" data-id="'+row.id+'" data-code="'+row.sub_code+'"><i data-feather="trash-2" class="feather-14"></i></a>'
                        + '</td></tr>';
                });
                $('#listSub').html(html);
                featherSVG();
            }
        });
    }

    $(document).on('click', '.btnEditSub', function(){
        $('#errAddSub').text('');
        $('#editSubId').val($(this).data('id'));
        $('#newSubDesc').val($(this).data('desc'));
        $('#newSubCode').prop('disabled', true);
        let option = new Option($(this).data('code'), $(this).data('code'), true, true);
        $('#newSubCode').html('').append(option).trigger('change');
        $('#lblFormSub').text('Edit '+$(this).data('code'));
        $('#btnCancelEditSub').removeClass('d-none');
    });

    $(document).on('click', '.btnDelSub', function(){
        if(!confirm('Hapus Sub Account '+$(this).data('code')+'?')){
            return;
        }
        let id = $(this).data('id');
        let typeCode = $('#newSubType').val();
        $.ajax({
            url: "{{ route('accSub.quickDestroy') }}",
            method: 'POST',
            data: { id: id },
            headers: { Accept: 'application/json' },
            success: function(){
                loadSubList(typeCode);
                if($('#editSubId').val() == id){
                    resetSubForm();
                }
                $('#subAccount option[value="'+$(this).data('code')+'"]').remove();
                if($('#subAccount').val() === null){
                    $('#subAccount').val('').trigger('change');
                }
            }.bind(this),
            error: function(xhr){
                alert((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus Sub Account.');
            }
        });
    });

    $('#newSubType').on('change', function(){
        let typeCode = $(this).val();
        resetSubForm();
        loadSubList(typeCode);

        if(!typeCode){
            resetSelect('#newSubCode', 'Choose Account Type first');
            return;
        }

        $.ajax({
            url: "{{ route('dynamic.dependent') }}",
            method: 'POST',
            data: { dependent: 'accSubCodeRange', value: typeCode },
            success: function(result){
                $('#newSubCode').html(result).prop('disabled', false).trigger('change');
            }
        });
    });

    $('#cmdSaveSub').click(function(){
        let editId = $('#editSubId').val();
        let typeCode = $('#newSubType').val();
        let subCode = $('#newSubCode').val();
        let desc = $('#newSubDesc').val().trim();
        if(!typeCode || !subCode || !desc){
            $('#errAddSub').text('Account Type, Code, dan Description wajib diisi.');
            return;
        }

        if(editId){
            $.ajax({
                url: "{{ route('accSub.quickUpdate') }}",
                method: 'POST',
                data: { id: editId, desc: desc },
                headers: { Accept: 'application/json' },
                success: function(result){
                    $('#subAccount option[value="'+result.sub_code+'"]').text(result.sub_code+' - '+result.description);
                    resetSubForm();
                    loadSubList(typeCode);
                },
                error: function(xhr){
                    $('#errAddSub').text((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan Sub Account.');
                }
            });
            return;
        }

        $.ajax({
            url: "{{ route('accSub.store') }}",
            method: 'POST',
            data: { typeCode: typeCode, subCode: subCode, desc: desc },
            headers: { Accept: 'application/json' },
            success: function(result){
                let option = new Option(result.sub_code+' - '+result.description, result.sub_code, true, true);
                if (typeCode === $('#type').val()) {
                    $('#subAccount').append(option).trigger('change');
                }
                resetSubForm();
                loadSubList(typeCode);
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
