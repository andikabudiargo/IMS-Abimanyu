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
                    <h4 class="card-title">Suppliers</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('supplier.store') }}" method="post" autocomplete="off">
                        @csrf
                        {{-- <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="kode">Kode</label>
                                    <input type="text" id="kode" name="kode" class="form-control" value="{{ old('kode') }}" required maxlength="20" autofocus />
                                </div>
                            </div>
                        </div> --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="nama">Name</label>
                                    <input type="text" id="nama" name="nama" class="form-control" value="{{ old('nama') }}" required  maxlength="100"/>
                                    <input type="hidden" id="inisial" name="inisial" class="form-control" value="{{ old('inisial') }}" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="alamat">Address</label>
                                    <textarea type="text" id="alamat" name="alamat" class="form-control" rows="2" value="{{ old('alamat') }}" maxlength="100"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="telepon">Telepon</label>
                                <input type="text" id="telepon" name="telepon" class="form-control angka" value="{{ old('telepon') }}" maxlength="20" />
                            </div>
                            <div class="form-group col-md-4">
                                <label class="form-label" for="fax">Fax</label>
                                <input type="text" id="fax" name="fax" class="form-control angka" value="{{ old('fax') }}" maxlength="20"/>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="form-label" for="hp">HP</label>
                                <input type="text" id="hp" name="hp" class="form-control angka" value="{{ old('hp') }}" maxlength="15"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kontak">Kontak*</label>
                                <input type="text" id="kontak" name="kontak" class="form-control" value="{{ old('kontak') }}" maxlength="20" required />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" maxlength="50" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="topBatas2">Term</label>
                                <div class="input-group input-group-merge">
                                    <input type="text" id="termin" name="termin" class="form-control angka" value="{{ old('termin') }}" maxlength="4"/>
                                    <div class="input-group-append">
                                        <span class="input-group-text">Hari</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="npwp">NPWP</label>
                                <input type="text" id="npwp" name="npwp" value="{{ old('npwp') }}" class="form-control masking-npwp angka" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="alamatNpwp">Alamat NPWP</label>
                                <textarea type="text" id="alamatNpwp" name="alamatNpwp" class="form-control" rows="2" maxlength="100">{{ old('alamatNpwp') }}</textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kotaNpwp">Kota</label>
                                <select class="select2 w-100" id="kotaNpwp" name="kotaNpwp">
                                    <option label=""></option>
                                    @foreach($cities as $val)
                                        <option value="{{$val->region_code}}">{{$val->region_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- <div class="row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="account">Account</label>
                                <select class="select2 w-100" id="account" name="account">
                                    <option label=""></option>
                                    @foreach($accounts as $val)
                                        <option value="{{$val->account}}" {{ $val->account == old("account") ? "selected" : ""}} >{{$val->account}} | {{$val->description}} </option>
                                    @endforeach
                                </select>
                            </div>
                        </div> --}}
                        <div class="row">
                            <div class="col-12">
                                <button class="btn btn-outline-secondary" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button>
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
    });

    $("#cmdSave").click(function(){       
        $('.disabled-el').removeAttr('disabled');
        $("#frmAdd").submit(); // Submit the form
    });

    $("#cmdCancel").click(function() {
        $(".select2").val('').trigger('change');
        $("#frmAdd").validate().resetForm();
    });

    function getCustAcronym(aString){
        let initials= "";
        let namaPerusahaan = ["CV","PT","PTE.","LTD.","CORP.","INC.","PT.","CV.","PTE","LTD","CORP","INC","PD","PD.","UD","UD."];        
        let wordCount = aString.trim().split(' ').length;
        if (wordCount == 1){
            initials = aString.substring(0,3).toUpperCase();
        }else if (wordCount > 1){ 
            let aString1 = aString;
            aString = aString.split(' ');
            let dataExist = namaPerusahaan.indexOf(aString[wordCount-1].toUpperCase());
            wordCount = dataExist != -1 ? wordCount-1 : wordCount;
                        
            let newString="";
            for (let i=0 ;i < wordCount;i++){
                newString+=aString[i].trim() +' ';
            }   
            aString1 = newString.trim();

            if (aString1.trim().split(' ').length == 2){
                aString1 = aString1+' '+aString1.trim().slice(-1);
            }

            if (aString1.trim().split(' ').length > 3){
                aString1 = aString1.trim().split(' ').slice(0,3).join(' ');
            }

            initials = aString1.split(' ').reduce((result, currentWord) => 
            result + currentWord.charAt(0).toUpperCase(), '');
        }
        
        return initials;
    }

    document.getElementById("nama").addEventListener("input", acroFunction);
    function acroFunction() {
        let aString = document.getElementById('nama').value;
        document.getElementById('inisial').value= getCustAcronym(aString);
    }

    npwp = $('.masking-npwp');
    if (npwp.length) {
        // 99.999.999.9-999.999       
        new Cleave(npwp, {
        delimiters: ['.','.','.','-','.'],
        blocks: [2,3,3,1,3,3],
        uppercase: true
        });
    }


</script>
@endsection