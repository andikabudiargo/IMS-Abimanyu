@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<section id="add-bank">
    <div class="form-row">
        <div class="col-6">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">accounts</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('bank.store') }}" method="post" autocomplete="off">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="form-label" for="bankType">Type</label>
                                <select class="select2 form-control w-100" id="bankType" name="bankType" required>
                                    <option value="BCA" {{ old('bankType') == "BCA" ? "selected" : "" }}>BCA</option>
                                    <option value="NONBCA" {{ old('bankType') == "NONBCA" ? "selected" : "" }}>NON BCA</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="bankName">Name</label>
                                <input type="text" id="bankName" name="bankName" class="form-control"  value="{{ old('bankName') }}" maxlength="100"  required />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="accNumber">Acount Number</label>
                                <input type="text" id="accNumber" name="accNumber" class="form-control text-uppercase" value="{{ old('accNumber') }}" maxlength="100" required/>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="branch">Branch</label>
                                <input type="text" id="branch" name="branch" class="form-control" value="{{ old('branch') }}"  maxlength="100" required/>
                            </div>
                        </div>
                        <div class="form-row">
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
    });
</script>
@endsection