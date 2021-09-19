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
                    <h4 class="card-title">yield('title')</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('uom.store') }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="kode">Code</label>
                                    <input type="text" id="kode" name="kode" class="form-control text-uppercase"  value="{{ old('kode') }}" required maxlength="4" autofocus />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="nama">Name</label>
                                    <input type="text" id="nama" name="nama" class="form-control" value="{{ old('nama') }}"  required  maxlength="20"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="uomType">Type</label>
                                <select class="select2 form-control" id="uomType" name="uomType" required>
                                    <option value="PIECE" {{ 'PIECE' == old("uom") ? "selected" : ""}} >Piece</option>
                                    <option value="MASS" {{ 'MASS' == old("uom") ? "selected" : ""}} >Mass</option>
                                    <option value="LENGTH" {{ 'LENGTH' == old("uom") ? "selected" : ""}}>Length</option>
                                    <option value="VOLUME" {{ 'VOLUME' == old("uom") ? "selected" : ""}}>Volume</option>
                                </select>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-12">
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
                let message = errors == 1
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
        $("#frmAdd").validate().resetForm();
        $('#kode').focus();
    });
</script>
@endsection