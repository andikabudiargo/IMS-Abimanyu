@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">@yield('title')</h4>
                </div>
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('company.store') }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="code">Code*</label>
                                    <input type="text" id="code" name="code" class="form-control" value="{{ old('code', $companies ? $companies->code :'') }}" required maxlength="20" autofocus />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="name">Name*</label>
                                    <input type="text" id="name" name="name" class="form-control text-uppercase" value="{{ old('name',$companies ? $companies->name : '') }}" required  maxlength="200"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="address">Address*</label>
                                    <textarea type="text" id="address" name="address" class="form-control" rows="2" maxlength="200" required>{{ old('address', $companies ? $companies->address :'') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="provinsi">Provinsi</label>
                                <select class="select2 form-control w-100 dynamicSelect" id="provinsi" name="provinsi" data-dependent="kota">
                                    <option label=""></option>
                                    @foreach($provinces as $val)
                                    <option value="{{$val->region_code}}"  >{{$val->region_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kota">Kota</label>
                                <select class="select2 form-control w-100 dynamicSelect" id="kota" name="kota" data-dependent="kelurahan">
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kelurahan">Kelurahan</label>
                                <select class="select2 form-control w-100 dynamicSelect" id="kelurahan" name="kelurahan" data-dependent="kecamatan">
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kecamatan">Kecamatan</label>
                                <select class="select2 form-control w-100" id="kecamatan" name="kecamatan">
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="telepon">Telepon</label>
                                <input type="text" id="telepon" name="telepon" class="form-control angka" value="{{ old('telepon', $companies ? $companies->tlp :'') }}" maxlength="20" />
                            </div>
                            <div class="form-group col-md-4">
                                <label class="form-label" for="fax">Fax</label>
                                <input type="text" id="fax" name="fax" class="form-control angka" value="{{ old('fax', $companies ? $companies->fax :'') }}" maxlength="20"/>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="form-label" for="hp">HP</label>
                                <input type="text" id="hp" name="hp" class="form-control angka" value="{{ old('hp', $companies ? $companies->hp :'') }}" maxlength="15"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $companies ? $companies->email :'') }}" maxlength="50" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="npwp">NPWP</label>
                                <input type="text" id="npwp" name="npwp" value="{{ old('npwp', $companies ? $companies->npwp :'') }}" class="form-control masking-npwp angka" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="alamatNpwp">Alamat NPWP</label>
                                <textarea type="text" id="alamatNpwp" name="alamatNpwp" class="form-control" rows="2" maxlength="100">{{ old('alamatNpwp', $companies ? $companies->tax_address :'') }}</textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kotaNpwp">Kota</label>
                                <select class="select2 w-100" id="kotaNpwp" name="kotaNpwp">
                                    <option label=""></option>
                                    @foreach($cities as $val)
                                        <option value="{{$val->region_code}}" {{$val->region_code == $companies->tax_city  ? "selected" : "" }}>{{$val->region_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
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
    let change_active = 'yes'; //kalo status nya yes maka fungsi dependent akan jalan
    
    if ('{{ $companies ? $companies->code :'' }}'){
        change_active = 'No';
    }

    $(document).ready(function(){     
        // changeselect('provinsi',0,'400009');
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

        '{{ Request::old('provinsi', $companies ? $companies->province :'') }}' ? changeselect('provinsi',0,'{{ Request::old('provinsi',$companies ? $companies->province : '') }}') : ''; 
        '{{ Request::old('kota', $companies ? $companies->city :'') }}' ? changeselect('kota','{{ Request::old('provinsi',$companies ? $companies->province : '') }}','{{ Request::old('kota',$companies ? $companies->city : '') }}') : '';
        '{{ Request::old('kelurahan', $companies ? $companies->village :'') }}' ? changeselect('kelurahan','{{ Request::old('kota',$companies ? $companies->city : '') }}','{{ Request::old('kelurahan',$companies ? $companies->village : '') }}') : '';
        '{{ Request::old('kecamatan', $companies ? $companies->district :'') }}' ? changeselect('kecamatan','{{ Request::old('kelurahan',$companies ? $companies->village : '') }}','{{ Request::old('kecamatan',$companies ? $companies->district : '') }}') : '';

        setTimeout( function() {
            change_active = 'yes';
        }, 3000);

    });

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

    $("#cmdSave").click(function(){       
        $('.disabled-el').removeAttr('disabled');
        $("#frmAdd").submit(); // Submit the form
    });

    $("#cmdCancel").click(function() {
        $(".select2").val('').trigger('change');
        $("#frmAdd").validate().resetForm();
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

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });


</script>
@endsection