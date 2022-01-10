@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-6">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">Suppliers</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('supplier.update',['id'=> $suppliers->id]) }}"  method="post" autocomplete="off">
                        @csrf
                        <div class="form-row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="kode">Code</label>
                                    <input type="text" id="kode" name="kode" class="form-control disabled-el" value="{{ old('kode',$suppliers->kode) }}" required maxlength="20" autofocus disabled />
                                </div>
                            </div>
                            <div class="form-group col-md-2">
                                <label class="form-label" for="inisial">Initial</label>
                                <input type="text" id="inisial" name="inisial" class="form-control text-uppercase" value="{{ old('inisial',$suppliers->inisial) }}" required maxlength="3" disabled />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="nama">Name</label>
                                    <input type="text" id="nama" name="nama" class="form-control" value="{{ old('nama',$suppliers->nama) }}" required  maxlength="100" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="alamat">Address</label>
                                    <textarea type="text" id="alamat" name="alamat" class="form-control" rows="2" maxlength="100" disabled>{{ old('alamat',$suppliers->alamat_tagih) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="provinsi">Provinsi</label>
                                <select class="select2 w-100 dynamicSelect" id="provinsi" name="provinsi" data-dependent="kota" disabled>
                                    <option value="">All</option>
                                    @foreach($provinces as $val)
                                    <option value="{{$val->region_code}}" >{{$val->region_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kota">Kota</label>
                                <select class="select2 w-100 dynamicSelect" id="kota" name="kota" data-dependent="kelurahan" disabled>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kelurahan">Kelurahan</label>
                                <select class="select2 w-100 dynamicSelect" id="kelurahan" name="kelurahan" data-dependent="kecamatan" disabled>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kecamatan">Kecamatan</label>
                                <select class="select2 w-100" id="kecamatan" name="kecamatan" disabled>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label class="form-label" for="kodePos">Kodepos</label>
                                <input type="text" id="kodePos" name="kodePos" value="{{ old('kodePos',$suppliers->kode_pos) }}" class="form-control disabled-el" disabled />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="telepon">Telepon</label>
                                <input type="text" id="telepon" name="telepon" class="form-control angka" value="{{ old('telepon',$suppliers->telepon) }}" maxlength="20" disabled />
                            </div>
                            <div class="form-group col-md-4">
                                <label class="form-label" for="fax">Fax</label>
                                <input type="text" id="fax" name="fax" class="form-control angka" value="{{ old('fax',$suppliers->fax) }}" maxlength="20" disabled />
                            </div>
                            <div class="form-group col-md-4">
                                <label class="form-label" for="hp">Mobile Phone</label>
                                <input type="text" id="hp" name="hp" class="form-control angka" value="{{ old('hp',$suppliers->hp) }}" maxlength="15" disabled />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kontak">Contact person*</label>
                                <input type="text" id="kontak" name="kontak" class="form-control" value="{{ old('kontak',$suppliers->nama_kontak) }}" maxlength="20" required disabled />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="{{ old('email',$suppliers->email) }}" maxlength="50" disabled />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="topBatas2">Term</label>
                                <div class="input-group input-group-merge">
                                    <input type="text" id="termin" name="termin" class="form-control angka" value="{{ old('termin',$suppliers->top_batas_1) }}" maxlength="4" disabled />
                                    <div class="input-group-append">
                                        <span class="input-group-text">Days</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="npwp">NPWP</label>
                                <input type="text" id="npwp" name="npwp" value="{{ old('npwp',$suppliers->npwp) }}" class="form-control masking-npwp" disabled/>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="alamatNpwp">Alamat NPWP</label>
                                <textarea type="text" id="alamatNpwp" name="alamatNpwp" class="form-control" rows="2" maxlength="100" disabled>{{ old('alamatNpwp',$suppliers->alamat_npwp) }}</textarea>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="kotaNpwp">Kota</label>
                                <select class="select2 w-100" id="kotaNpwp" name="kotaNpwp" disabled>
                                    <option value="">All</option>
                                    @foreach($cities as $val)
                                        <option value="{{$val->region_code}}" {{ $val->region_code == old('kotaNpwp',$suppliers->kota_npwp) ? "selected" : ""}}>{{$val->region_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="account">Account</label>
                                <select class="select2 w-100" id="account" name="account" disabled>
                                    <option value="">All</option>
                                    @foreach($accounts as $val)
                                        <option value="{{$val->account}}" {{ $val->account == old('account',$suppliers->account) ? "selected" : ""}} >{{$val->account}} | {{$val->description}} </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6 align-self-end" >
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="bankBca" name="bankBca" {{ old('bankBca',$suppliers->bank_bca) == 'yes' ? 'checked' : '' }} />
                                    <label class="custom-control-label" for="bankBca">BANK BCA</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-12">
                                <a href="{{ route('suppliers.index') }}" class="btn btn-primary">
                                    Back
                                </a>
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
    let change_active = 'no';
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

        '{{ Request::old('provinsi', $suppliers ? $suppliers->provinsi :'') }}' ? changeselect('provinsi',0,'{{ Request::old('provinsi',$suppliers ? $suppliers->provinsi : '') }}') : ''; 
        '{{ Request::old('kota', $suppliers ? $suppliers->kota :'') }}' ? changeselect('kota','{{ Request::old('provinsi',$suppliers ? $suppliers->provinsi : '') }}','{{ Request::old('kota',$suppliers ? $suppliers->kota : '') }}') : '';
        '{{ Request::old('kelurahan', $suppliers ? $suppliers->kelurahan :'') }}' ? changeselect('kelurahan','{{ Request::old('kota',$suppliers ? $suppliers->kota : '') }}','{{ Request::old('kelurahan',$suppliers ? $suppliers->kelurahan : '') }}') : '';
        '{{ Request::old('kecamatan', $suppliers ? $suppliers->kecamatan :'') }}' ? changeselect('kecamatan','{{ Request::old('kelurahan',$suppliers ? $suppliers->kelurahan : '') }}','{{ Request::old('kecamatan',$suppliers ? $suppliers->kecamatan : '') }}') : '';

        setTimeout( function() {
            change_active = 'yes';
        }, 3000);
    });

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

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection