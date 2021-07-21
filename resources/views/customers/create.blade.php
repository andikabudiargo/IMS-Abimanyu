@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<div class="alert alert-warning alert-dismissible collapse" role="alert" id="alert-message">
    <div class="alert-body">
    </div>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

<section class="vertical-wizard">
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
            @if (count($errors) > 0)
				<div class="pgn-wrapper alert-message" data-position="top-right" >
					<div class="pgn push-on-sidebar-open pgn-simple">
						<div class="alert alert-danger" role="alert" >
							<button class="close" data-dismiss="alert"></button>
							<ul>
								@foreach ($errors->all() as $error)
									<li>
										{{ $error }}
									</li>
								@endforeach
							</ul>
						</div>
					</div>
				</div>
			@endif
            <form id="frmAdd" name="frmAdd" action="{{ route('customer.store') }}" method="post" autocomplete="off">
                @csrf                
                <div id="detilCustomer" class="content">
                    <div class="content-header">
                        <h5 class="mb-0">Detil customer</h5>
                        <small class="text-muted">Detil data customer</small>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label class="form-label" for="kode">Kode</label>
                            <input type="text" id="kode" name="kode" class="form-control" required maxlength="20" />
                            <div class="invalid-feedback">Kode tidak boleh kosong</div>
                        </div>
                        <div class="form-group col-md-4 align-self-end" >
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="epte" name="epte" />
                                <label class="custom-control-label" for="epte">EPTE</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="nama">Nama</label>
                            <input type="text" id="nama" name="nama" class="form-control" required maxlength="30"/>
                            <div class="invalid-feedback">Nama tidak boleh kosong</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="alamatTagih">Alamat Tagih</label>
                            <textarea type="text" id="alamatTagih" name="alamatTagih" class="form-control" rows="2" maxlength="100"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="alamatKirim1">Alamat Kirim 1</label>
                            <textarea type="text" id="alamatKirim1" name="alamatKirim1" class="form-control" rows="2"  maxlength="100"></textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label" for="alamatKirim2">Alamat Kirim 2</label>
                            <textarea type="text" id="alamatKirim2" name="alamatKirim2" class="form-control" rows="2"  maxlength="100"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label class="form-label" for="telepon">Telepon</label>
                            <input type="text" id="telepon" name="telepon" class="form-control angka" maxlength="20" />
                        </div>
                        <div class="form-group col-md-3">
                            <label class="form-label" for="fax">Fax</label>
                            <input type="text" id="fax" name="fax" class="form-control angka" maxlength="20"/>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="form-label" for="hp">HP</label>
                            <input type="text" id="hp" class="form-control angka" maxlength="15"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="kontak">Kontak</label>
                            <input type="text" id="kontak" class="form-control" maxlength="20" />
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label class="form-label" for="limitKredit">Limit kredit</label>
                            <input type="text" id="limitKredit" name="limitKredit" class="form-control angka" maxlength="12" />
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label" for="umur_kredit">Umur kredit</label>
                            <div class="input-group input-group-merge">
                                <input type="text" id="umur_kredit" name="umur_kredit" class="form-control angka" maxlength="4" />
                                <div class="input-group-append">
                                    <span class="input-group-text">Hari</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="syaratBayar">Syarat bayar</label>
                            <input type="text" id="syaratBayar" name="syaratBayar" class="form-control"  maxlength="100"/>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="syaratKirim">Syarat kirim</label>
                            <input type="text" id="syaratKirim" name="syaratKirim" class="form-control"  maxlength="100"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-2">
                            <label class="form-label" for="topBatas1">TOP Batas 1</label>
                            <div class="input-group input-group-merge">
                                <input type="text" id="topBatas1" name="topBatas1" class="form-control angka" maxlength="4"/>
                                <div class="input-group-append">
                                    <span class="input-group-text">Hari</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label" for="topBatas2">TOP Batas 2</label>
                            <div class="input-group input-group-merge">
                                <input type="text" id="topBatas2" name="topBatas2" class="form-control angka" maxlength="4"/>
                                <div class="input-group-append">
                                    <span class="input-group-text">Hari</span>
                                </div>
                            </div>
                        </div>
                    </div>        
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="sales">Sales</label>
                            <select class="select2 w-100" id="sales" name="sales">
                                <option label=""></option>
                                @foreach($employees as $val)
                                    <option value="{{$val->kode}}">{{$val->nama}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="areaKirim">Area kirim</label>
                            <select class="select2 w-100" id="areaKirim" name="areaKirim">
                                <option label=""></option>
                                @foreach($cities as $val)
                                    <option value="{{$val->region_code}}">{{$val->region_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="account">Account</label>
                            <select class="select2 w-100" id="account" name="account">
                                <option label=""></option>
                                @foreach($cities as $val)
                                    <option value="{{$val->region_code}}">{{$val->region_name}}</option>
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
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="npwp">NPWP</label>
                            <input type="text" id="npwp" name="npwp" class="form-control masking-npwp angka" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="alamatNpwp">Alamat NPWP</label>
                            <textarea type="text" id="alamatNpwp" name="alamatNpwp" class="form-control" rows="2" maxlength="100"></textarea>
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
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="nppkp">NPPKP</label>
                            <input type="text" id="nppkp" name="nppkp" class="form-control" maxlength="30"/>
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
                    <div class="row">
                        <div class="form-group col-md-8">
                            <label class="form-label" for="alamatEfaktur">Alamat Efaktur</label>
                            <textarea type="text" id="alamatEfaktur" name="alamat" class="form-control" rows="2" maxlength="100"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-2">
                            <label class="form-label" for="blok">Blok</label>
                            <input type="text" id="blok" name="blok" class="form-control"  maxlength="10"/>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label" for="nomor">No</label>
                            <input type="text" id="nomor" name="nomor" class="form-control"   maxlength="5"/>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label" for="rt">RT</label>
                            <input type="text" id="rt" name="rt" class="form-control angka"  maxlength="14"/>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label" for="rw">RW</label>
                            <input type="text" id="rw" name="rw" class="form-control angka"  maxlength="14" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label class="form-label" for="provinsi">Provinsi</label>
                            <select class="select2 w-100 dynamicSelect" id="provinsi" name="provinsi" data-dependent="kota">
                                <option label=""></option>
                                @foreach($provinces as $val)
                                <option value="{{$val->region_code}}">{{$val->region_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="form-label" for="kota">Kota</label>
                            <select class="select2 w-100 dynamicSelect" id="kota" name="kota" data-dependent="kelurahan">
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label class="form-label" for="kelurahan">Kelurahan</label>
                            <select class="select2 w-100 dynamicSelect" id="kelurahan" name="kelurahan" data-dependent="kecamatan">
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="form-label" for="kecamatan">Kecamatan</label>
                            <select class="select2 w-100" id="kecamatan" name="kecamatan">
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-2">
                            <label class="form-label" for="kodePos">Kodepos</label>
                            <input type="text" id="kodePos" name="kodePos" class="form-control disabled-el" disabled />
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-primary btn-prev" type="button">
                            <i data-feather="arrow-left" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Previous</span>
                        </button>
                        <div>
                            <button class="btn btn-outline-secondary" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button>
                            <button class="btn btn-success"  type="button"  id="cmdSave" name="cmdSave">Save</button>
                        </div>
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
    let change_active = 'yes';
    $(document).ready(function(){    
        changeselect('provinsi',0,'400009');
        emptySelect('kota');
        emptySelect('kecamatan');
        emptySelect('kelurahan');

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
        $('#'+dependent).val(selectval).trigger('change');
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

        // $(verticalTab)
        //     .find('.btn-submit')
        //     .on('click', function () {
        //     alert('Submitted..!!');
        //     });
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
                        // console.log(dependent);
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

    $('#kecamatan').change(function(e) {
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

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
@endsection