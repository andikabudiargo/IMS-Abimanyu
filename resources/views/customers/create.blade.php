@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section class="add-customer">
    <div class="bs-stepper vertical vertical-input-tab">
        <div class="bs-stepper-header">
            <div class="step" data-target="#detilCustomer">
                <button type="button" class="step-trigger">
                    <span class="bs-stepper-box">1</span>
                    <span class="bs-stepper-label">
                        <span class="bs-stepper-title">Detil Customer</span>
                        <span class="bs-stepper-subtitle">Detil data customer</span>
                    </span>
                </button>
            </div>
            <div class="step" data-target="#dataPajak">
                <button type="button" class="step-trigger">
                    <span class="bs-stepper-box">2</span>
                    <span class="bs-stepper-label">
                        <span class="bs-stepper-title">Data Pajak</span>
                        <span class="bs-stepper-subtitle">Detil pajak</span>
                    </span>
                </button>
            </div>
            <div class="step" data-target="#dataEfaktur">
                <button type="button" class="step-trigger">
                    <span class="bs-stepper-box">3</span>
                    <span class="bs-stepper-label">
                        <span class="bs-stepper-title">Data Efaktur</span>
                        <span class="bs-stepper-subtitle">Detil Efaktur</span>
                    </span>
                </button>
            </div>
        </div>
        <div class="bs-stepper-content">
            <form id="frmAdd" name="frmAdd" action="{{ route('customer.store') }}" method="post" autocomplete="off">
                @csrf                
                <div id="detilCustomer" class="content">
                    <div class="content-header">
                        <h5 class="mb-0">Detil customer</h5>
                        <small class="text-muted">Detil data customer</small>
                    </div>
                    <div class="form-row">
                        {{-- <div class="form-group col-md-4">
                            <label class="form-label" for="kode">Kode</label>
                            <input type="text" id="kode" name="kode" value="{{ old('kode') }}" class="form-control" maxlength="20" autofocus/>
                        </div> --}}
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="nama">Nama</label>
                            <input type="text" id="nama" name="nama" value="{{ old('nama') }}" class="form-control text-uppercase" required maxlength="100" autofocus/>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="form-label" for="inisial">Inisial</label>
                            <input type="text" id="inisial" name="inisial" value="{{ old('inisial') }}" class="form-control text-uppercase" required maxlength="3"/>
                        </div>
                        <div class="form-group col-md-2 align-self-end" >
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="epte" name="epte" {{ old('epte') == 't' ? 'checked' : '' }} />
                                <label class="custom-control-label" for="epte">EPTE</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="account">COA Piutang</label>
                            <select class="select2 w-100" id="account" name="account">
                                <option value=""></option>
                                @foreach($accounts as $val)
                                    <option value="{{$val->account}}" {{ $val->account == old("account") ? "selected" : ""}} >{{$val->account}} | {{$val->description}} </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="alamatTagih">Alamat Tagih</label>
                            <textarea type="text" id="alamatTagih" name="alamatTagih" class="form-control" rows="2" maxlength="100">{{ old('alamatTagih') }}</textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="alamatKirim1">Alamat Kirim 1</label>
                            <textarea type="text" id="alamatKirim1" name="alamatKirim1" class="form-control" rows="2"  maxlength="100">{{ old('alamatKirim1') }}</textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label" for="alamatKirim2">Alamat Kirim 2</label>
                            <textarea type="text" id="alamatKirim2" name="alamatKirim2" class="form-control" rows="2"  maxlength="100">{{ old('alamatKirim2') }}</textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label class="form-label" for="telepon">Telepon</label>
                            <input type="text" id="telepon" name="telepon" value="{{ old('telepon') }}" class="form-control angka" maxlength="20" />
                        </div>
                        <div class="form-group col-md-3">
                            <label class="form-label" for="fax">Fax</label>
                            <input type="text" id="fax" name="fax" value="{{ old('fax') }}" class="form-control angka" maxlength="20"/>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="form-label" for="hp">HP</label>
                            <input type="text" id="hp" name="hp" value="{{ old('hp') }}" class="form-control angka" maxlength="15"/>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="kontak">Kontak</label>
                            <input type="text" id="kontak" name="kontak" value="{{ old('kontak') }}" class="form-control" maxlength="20" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control" maxlength="50" />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label class="form-label" for="limitKredit">Limit kredit</label>
                            <input type="text" id="limitKredit" name="limitKredit" value="{{ old('limitKredit') }}" class="form-control angka" maxlength="12" />
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label" for="umurKredit">Umur kredit</label>
                            <div class="input-group input-group-merge">
                                <input type="text" id="umurKredit" name="umurKredit" value="{{ old('umurKredit') }}" class="form-control angka" maxlength="4" />
                                <div class="input-group-append">
                                    <span class="input-group-text">Hari</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="syaratBayar">Syarat bayar</label>
                            <input type="text" id="syaratBayar" name="syaratBayar" value="{{ old('syaratBayar') }}" class="form-control"  maxlength="100"/>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="syaratKirim">Syarat kirim</label>
                            <input type="text" id="syaratKirim" name="syaratKirim" value="{{ old('syaratKirim') }}" class="form-control"  maxlength="100"/>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label class="form-label" for="topBatas1">TOP Batas 1</label>
                            <div class="input-group input-group-merge">
                                <input type="text" id="topBatas1" name="topBatas1" value="{{ old('topBatas1') }}" class="form-control angka" maxlength="4"/>
                                <div class="input-group-append">
                                    <span class="input-group-text">Hari</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label" for="topBatas2">TOP Batas 2</label>
                            <div class="input-group input-group-merge">
                                <input type="text" id="topBatas2" name="topBatas2" value="{{ old('topBatas2') }}" class="form-control angka" maxlength="4"/>
                                <div class="input-group-append">
                                    <span class="input-group-text">Hari</span>
                                </div>
                            </div>
                        </div>
                    </div>        
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="sales">Sales</label>
                            <select class="select2 w-100" id="sales" name="sales">
                                <option value="">All</option>
                                @foreach($employees as $val)
                                    <option value="{{$val->employee_id}}" {{ $val->employee_id == old("sales") ? "selected" : ""}} >{{$val->employee_id}} | {{$val->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row d-none">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="areaKirim">Area kirim</label>
                            <select class="select2 w-100" id="areaKirim" name="areaKirim">
                                <option value="">All</option>
                                @foreach($cities as $val)
                                    <option value="{{$val->region_code}}" {{ $val->region_code == old("areaKirim") ? "selected" : ""}} >{{$val->region_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                   
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-outline-secondary btn-prev" disabled>
                            <i data-feather="arrow-left" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Previous</span>
                        </button>
                        <button class="btn btn-primary btn-next" type="button">
                            <span class="align-middle d-sm-inline-block d-none">Next</span>
                            <i data-feather="arrow-right" class="align-middle ml-sm-25 ml-0"></i>
                        </button>
                    </div>
                </div>
                <div id="dataPajak" class="content">
                    <div class="content-header">
                        <h5 class="mb-0">Data pajak</h5>
                        <small>Input detail untuk pajak</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="npwp">NPWP</label>
                            <input type="text" id="npwp" name="npwp" value="{{ old('npwp') }}" class="form-control masking-npwp angka" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="alamatNpwp">Alamat NPWP</label>
                            <textarea type="text" id="alamatNpwp" name="alamatNpwp" class="form-control" rows="2" maxlength="100">{{ old('alamatNpwp') }}</textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="kotaNpwp">Kota</label>
                            <select class="select2 w-100" id="kotaNpwp" name="kotaNpwp">
                                <option value="">All</option>
                                @foreach($cities as $val)
                                    <option value="{{$val->region_code}}">{{$val->region_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="nppkp">NPPKP</label>
                            <input type="text" id="nppkp" name="nppkp" value="{{ old('nppkp') }}" class="form-control" maxlength="30"/>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-primary btn-prev" type="button">
                            <i data-feather="arrow-left" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Previous</span>
                        </button>
                        <button class="btn btn-primary btn-next" type="button">
                            <span class="align-middle d-sm-inline-block d-none">Next</span>
                            <i data-feather="arrow-right" class="align-middle ml-sm-25 ml-0"></i>
                        </button>
                    </div>
                </div>
                <div id="dataEfaktur" class="content">
                    <div class="content-header">
                        <h5 class="mb-0">Efaktur</h5>
                        <small>Data Efaktur</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label class="form-label" for="alamatEfaktur">Alamat Efaktur</label>
                            <textarea type="text" id="alamatEfaktur" name="alamatEfaktur" class="form-control" rows="2" maxlength="100">{{ old('alamatEfaktur') }}</textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label class="form-label" for="blok">Blok</label>
                            <input type="text" id="blok" name="blok" value="{{ old('blok') }}" class="form-control"  maxlength="10"/>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label" for="nomor">No</label>
                            <input type="text" id="nomor" name="nomor" value="{{ old('nomor') }}" class="form-control"   maxlength="5"/>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label" for="rt">RT</label>
                            <input type="text" id="rt" name="rt" value="{{ old('rt') }}"  class="form-control angka"  maxlength="14"/>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label" for="rw">RW</label>
                            <input type="text" id="rw" name="rw" value="{{ old('rw') }}" class="form-control angka"  maxlength="14" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="form-label" for="provinsi">Provinsi</label>
                            <select class="select2 w-100 dynamicSelect" id="provinsi" name="provinsi" data-dependent="kota">
                                <option value="">All</option>
                                @foreach($provinces as $val)
                                <option value="{{$val->region_code}}" >{{$val->region_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="form-label" for="kota">Kota/kabupaten</label>
                            <select class="select2 w-100 dynamicSelect" id="kota" name="kota" data-dependent="kecamatan">
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="form-label" for="kecamatan">Kecamatan</label>
                            <select class="select2 w-100 dynamicSelect" id="kecamatan" name="kecamatan" data-dependent="kelurahan">
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="form-label" for="kelurahan">Kelurahan</label>
                            <select class="select2 w-100" id="kelurahan" name="kelurahan">
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label class="form-label" for="kodePos">Kodepos</label>
                            <input type="text" id="kodePos" name="kodePos" value="{{ old('kodePos') }}" class="form-control disabled-el" disabled />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12">
                            <button class="btn btn-outline-secondary" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button>
                            <button class="btn btn-success" type="button" id="cmdSave" name="cmdSave">Save</button>
                        </div>
                    </div>
                    <br>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-primary btn-prev" type="button">
                            <i data-feather="arrow-left" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Previous</span>
                        </button>
                    </div>
                </div>
            </form>
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
    let change_active = 'yes'; //kalo status nya yes maka fungsi dependent akan jalan
    $(document).ready(function(){    
        changeselect('provinsi',0,'400009');
        emptySelect('kota');
        emptySelect('kecamatan');
        emptySelect('kelurahan');

        validateFormToast("frmAdd");

        if ('{{ $errors->any() }}'){
            change_active = 'no'; 
            setTimeout(() => { 
                change_active = 'yes';
            }, 2000);
        }
        
        '{{ Request::old('provinsi') }}' ? changeselect('provinsi',0,'{{ Request::old('provinsi') }}') : '';   
        '{{ Request::old('kota') }}' ? changeselect('kota','{{ Request::old('provinsi') }}','{{ Request::old('kota') }}') : '';
        '{{ Request::old('kecamatan') }}' ? changeselect('kecamatan','{{ Request::old('kota') }}','{{ Request::old('kecamatan') }}') : '';
        '{{ Request::old('kelurahan') }}' ? changeselect('kecamatan','{{ Request::old('kecamatan') }}','{{ Request::old('kelurahan') }}') : '';
    });

    $("#cmdSave").click(function(){       
        $('.disabled-el').removeAttr('disabled');
        $("#frmAdd").submit();
    });

    $("#cmdCancel").click(function() {
        $(".select2").val('').trigger('change');
        $("#frmAdd").validate().resetForm();
    });

    verticalTab = document.querySelector('.vertical-input-tab');
    if (typeof verticalTab !== undefined && verticalTab !== null) {
        let verticalStepper = new Stepper(verticalTab, {
            linear: false
        });

        $(verticalTab)
            .find('.btn-next')
            .on('click', function () {
                verticalStepper.next();
            });
        $(verticalTab)
            .find('.btn-prev')
            .on('click', function () {
                verticalStepper.previous();
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

    function emptySelect(obj){
      $('#'+obj)
        .find('option')
        .remove()
        .end()
        .append('<option value="">...</option>')
        .val('')
      ;
    }

    $('#kelurahan').change(function(e) {
        let val = $(this).find(':selected').text().split(",");
        val.length > 0 ? $('#kodePos').val(val[1]) :'';
    })

    npwp = $('.masking-npwp');
    if (npwp.length) {
        // 99.999.999.9-999.999       
        new Cleave(npwp, {
        delimiters: ['.','.','.','-','.'],
        blocks: [2,3,3,1,3,3],
        uppercase: true
        });
    }

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

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
@endsection