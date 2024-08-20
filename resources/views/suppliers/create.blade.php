@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-8">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">Suppliers</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('supplier.store') }}" method="post" autocomplete="off">
                        @csrf
                        {{-- <div class="form-row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="kode">Kode</label>
                                     <input type="text" id="kode" name="kode" class="form-control" value="{{ old('kode') }}" required maxlength="20" autofocus />
                                </div>
                            </div>
                        </div> --}}
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nama">Name*</label>
                                <input type="text" id="nama" name="nama" class="form-control text-uppercase" value="{{ old('nama') }}" required  maxlength="100"/>
                            </div>
                            <div class="form-group col-md-2">
                                <label class="form-label" for="inisial">Initial*</label>
                                <input type="text" id="inisial" name="inisial" class="form-control text-uppercase" value="{{ old('inisial') }}" required maxlength="3"/>
                            </div>
                            <div class="form-group col-md-4 align-self-end" >
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="asCustomer" name="asCustomer" {{ old('asCustomer') == 't' ? 'checked' : '' }} />
                                    <label class="custom-control-label" for="asCustomer">As Customer</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="coaHutang">COA Hutang</label>
                                <select class="select2 w-100" id="coaHutang" name="coaHutang">
                                    <option value=""></option>
                                    @foreach($accounts as $val)
                                        <option value="{{$val->account}}" {{ $val->account == old("coaHutang") ? "selected" : ""}} >{{$val->account}} | {{$val->description}} </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="coaReturPembelian">COA Retur Pembelian</label>
                                <select class="select2 w-100" id="coaReturPembelian" name="coaReturPembelian">
                                    {{-- <option value=""></option> --}}
                                    @foreach($coaReturPembelian as $val)
                                        <option value="{{$val->account}}" {{ $val->account == old("coaReturPembelian") ? "selected" : ""}} >{{$val->account}} | {{$val->description}} </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label for="alamat">Address</label>
                                <textarea type="text" id="alamat" name="alamat" class="form-control" rows="2" value="{{ old('alamat') }}" maxlength="100"></textarea>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="provinsi">Provinsi</label>
                                <select class="select2 form-control dynamicSelect" id="provinsi" name="provinsi" data-dependent="kota">
                                    <option value="">All</option>
                                    @foreach($provinces as $val)
                                    <option value="{{$val->region_code}}" >{{$val->region_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kota">Kota/Kabupaten</label>
                                <select class="select2 form-control dynamicSelect" id="kota" name="kota" data-dependent="kecamatan">
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kecamatan">Kecamatan</label>
                                <select class="select2 form-control dynamicSelect" id="kecamatan" name="kecamatan" data-dependent="kelurahan">
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kelurahan">Kelurahan</label>
                                <select class="select2 form-control" id="kelurahan" name="kelurahan">
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="kodePos">Postal code</label>
                                <input type="text" id="kodePos" name="kodePos" value="{{ old('kodePos') }}" class="form-control disabled-el" disabled />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="telepon">Telepon</label>
                                <input type="text" id="telepon" name="telepon" class="form-control angka" value="{{ old('telepon') }}" maxlength="20" />
                            </div>
                            <div class="form-group col-md-4">
                                <label class="form-label" for="fax">Fax</label>
                                <input type="text" id="fax" name="fax" class="form-control angka" value="{{ old('fax') }}" maxlength="20"/>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="form-label" for="hp">Mobile Phone</label>
                                <input type="text" id="hp" name="hp" class="form-control angka" value="{{ old('hp') }}" maxlength="15"/>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kontak">Contact person</label>
                                <input type="text" id="kontak" name="kontak" class="form-control" value="{{ old('kontak') }}" maxlength="20" />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" maxlength="50" />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="topBatas2">TOP</label>
                                <div class="input-group input-group-merge"> 
                                    <input type="text" id="termin" name="termin" class="form-control angka" value="{{ old('termin') }}" maxlength="4" required/>
                                    <div class="input-group-append">
                                        <span class="input-group-text">Days</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-3 align-self-end" >
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="pkp" name="pkp" {{ old('pkp') == 'Y' ? 'checked' : '' }} />
                                    <label class="custom-control-label" for="pkp">PKP</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="npwp">NPWP</label>
                                <input type="text" id="npwp" name="npwp" value="{{ old('npwp') }}" class="form-control masking-npwp angka" />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="alamatNpwp">Alamat NPWP</label>
                                <textarea type="text" id="alamatNpwp" name="alamatNpwp" class="form-control" rows="2" maxlength="100">{{ old('alamatNpwp') }}</textarea>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="kotaNpwp">Kota</label>
                                <select class="select2 form-control" id="kotaNpwp" name="kotaNpwp">
                                    <option value="">All</option>
                                    @foreach($cities as $val)
                                        <option value="{{$val->region_code}}">{{$val->region_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- <div class="form-row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="account">Account</label>
                                <select class="select2 form-control" id="account" name="account">
                                    <option value="">All</option>
                                    @foreach($accounts as $val)
                                        <option value="{{$val->account}}" {{ $val->account == old("account") ? "selected" : ""}} >{{$val->account}} | {{$val->description}} </option>
                                    @endforeach
                                </select>
                            </div>
                        </div> --}}
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="bankType">Type*</label>
                                <select class="select2 form-control" id="bankType" name="bankType" required>
                                    <option value="">Choose bank type</option>
                                    <option value="NONBCA" {{ old('bankType') == "NONBCA" ? "selected" : "" }}>NON BCA</option>
                                    <option value="BCA" {{ old('bankType') == "BCA" ? "selected" : "" }}>BCA</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="bankName">Bank name</label>
                                <select class="select2 form-control" id="bankName" name="bankName">
                                    <option value="">Choose bank</option>
                                    @foreach($banks as $val)
                                        <option value="{{ $val->bank_name }}" {{ $val->bank_name == old("bankName") ? "selected" : ""}} required >{{ $val->bank_name }} </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="accNumber">Acount Number</label>
                                <input type="text" id="accNumber" name="accNumber" class="form-control text-uppercase" value="{{ old('accNumber') }}" maxlength="100"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="branch">Branch</label>
                                <input type="text" id="branch" name="branch" class="form-control" value="{{ old('branch') }}"  maxlength="100"/>
                            </div>
                        </div>
                        {{-- <div class="form-row">
                            <div class="form-group col-md-6 align-self-end" >
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="bankBca" name="bankBca" {{ old('bankBca') == 'yes' ? 'checked' : '' }} />
                                    <label class="custom-control-label" for="bankBca">BANK BCA</label>
                                </div>
                            </div>
                        </div> --}}

                        
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="category">Category</label>
                                <select class="select2 form-control" id="category" name="category">
                                    <option value="">Choose Category...</option>
                                    <option value="raw_material" {{ old("category") == "raw_material" ? "selected" : ""}} >Raw Material</option>
                                    <option value="consumable" {{ old("category") == "consumable" ? "selected" : ""}} >Consumable</option>
                                    <option value="lainlain" {{ old("category") =="lainlain" ? "selected" : ""}} >Lain-lain</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label for="joinDate">Join Date</label>
                                <input type="text" id="joinDate" name="joinDate" class="form-control" value="{{ old('joinDate') }}" placeholder="DD-MM-YYYY" />
                            </div> 
                        </div>
                        <div class="form-row">
                            <div class="col-12">
                                <button class="btn btn-success" type="reset" id="cmdCancel" name="cmdCancel">New</button>
                                <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/jquery-ui.css') }}">
<style>
    textarea {
        resize: none;
    }
</style>
@endsection
@section('scripts')
<script src="{{ asset('assets/js/ui.1.13.0.jquery-ui.js') }}"></script>
<script type="text/javascript">
    let change_active = 'yes';
    $(document).ready(function(){     
        validateFormToast("frmAdd");
    });

    let availableTags ="{{ $suppliers }}";
    availableTags=availableTags.replace(/[[\]]/g,'');
    availableTags=availableTags.replace(/&quot;/g,'').split(",");
    $("#nama").autocomplete({
        source: availableTags,
        select: function( event, ui ) {
            acroFunction();
        }
    });

    $("#cmdSave").click(function(){    
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $('.disabled-el').removeAttr('disabled');
            $("#frmAdd").submit(); // Submit the form
        }
    });

    $("#cmdCancel").click(function() {
        // $(".select2").val('').trigger('change');
        $("#frmAdd").validate().resetForm();
    });

    getSuppAcronym = (aString) =>{
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
    
    acroFunction = () => {
        let aString = document.getElementById('nama').value;
        document.getElementById('inisial').value= getSuppAcronym(aString);
    }
    document.getElementById("nama").addEventListener("input", acroFunction);

    npwp = $('.masking-npwp');
    if (npwp.length) {
        // 99.999.999.9-999.999       
        new Cleave(npwp, {
        delimiters: ['.','.','.','-','.'],
        blocks: [2,3,3,1,3,3],
        uppercase: true
        });
    }

    $(document).on('change', '.dynamicSelect', function() {
        if (change_active === "yes"){
            let dependent = $(this).data('dependent');
            if($(this).val()!=''){
                let value = $(this).val();
                $.ajax({
                    url:"{{route('dynamic.dependent')}}",
                    method:"POST",
                    data:{
                        value:value,
                        dependent:dependent
                    },
                    success:function(result){
                        // console.log(result);
                        $('#'+dependent).html(result);
                        $('#'+dependent).val('').trigger('change');
                    }
                })
            }else{
                $('#'+dependent).val('').trigger('change');
                $('#'+dependent).empty().append('<option value="">...</option>');
            }
        }
    })

    function changeselect(dependent,value,selectval) {
      $.ajax({
        url:"{{route('dynamic.dependent')}}",
        method:"POST",
        data:{
            value:value,
            dependent:dependent
        },
        success:function(result){
          $('#'+dependent).html(result);
          $('#'+dependent).val(selectval).trigger('change');
        }
      })
    }

    $('#kelurahan').change(function(e) {
        let val = $(this).find(':selected').text().split(",");
        val.length > 0 ? $('#kodePos').val(val[1]) :'';
    })

    $('#bankType').change(function(e) {
        $(this).val()=='BCA' ? $('#bankName').val('BANK CENTRAL ASIA Tbk').trigger('change')  : $('#bankName').val('').trigger('change');
    });

    let joinDate = $('#joinDate');
    if (joinDate.length) {
        joinDate.flatpickr({
            dateFormat: "d-m-Y"
        });
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });


</script>
@endsection