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
                    <form id="frmAdd" name="frmAdd" action="{{ route('supplier.update',['id'=> $suppliers->id]) }}"  method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="kode">Kode</label>
                                    <input type="text" id="kode" name="kode" class="form-control disabled-el" value="{{ old('kode',$suppliers->kode) }}" required maxlength="20" autofocus disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="nama">Name</label>
                                    <input type="text" id="nama" name="nama" class="form-control" value="{{ old('nama',$suppliers->nama) }}" required  maxlength="100"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="alamat">Address</label>
                                    <textarea type="text" id="alamat" name="alamat" class="form-control" rows="2" maxlength="100">{{ old('alamat',$suppliers->alamat_tagih) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="telepon">Telepon</label>
                                <input type="text" id="telepon" name="telepon" class="form-control angka" value="{{ old('telepon',$suppliers->telepon) }}" maxlength="20" />
                            </div>
                            <div class="form-group col-md-4">
                                <label class="form-label" for="fax">Fax</label>
                                <input type="text" id="fax" name="fax" class="form-control angka" value="{{ old('fax',$suppliers->fax) }}" maxlength="20"/>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="form-label" for="hp">HP</label>
                                <input type="text" id="hp" name="hp" class="form-control angka" value="{{ old('hp',$suppliers->hp) }}" maxlength="15"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="kontak">Kontak*</label>
                                <input type="text" id="kontak" name="kontak" class="form-control" value="{{ old('kontak',$suppliers->nama_kontak) }}" maxlength="20" required />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="{{ old('email',$suppliers->email) }}" maxlength="50" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="topBatas2">Term</label>
                                <div class="input-group input-group-merge">
                                    <input type="text" id="termin" name="termin" class="form-control angka" value="{{ old('termin',$suppliers->top_batas_1) }}" maxlength="4"/>
                                    <div class="input-group-append">
                                        <span class="input-group-text">Hari</span>
                                    </div>
                                </div>
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
                                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
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
    });

    $("#cmdSave").click(function(){       
        $('.disabled-el').removeAttr('disabled');
        $("#frmAdd").submit(); // Submit the form
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

</script>
@endsection