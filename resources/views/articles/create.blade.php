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
                    <h4 class="card-title">accounts</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('article.store') }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="kode">Article Code</label>
                                    <input type="text" id="kode" name="kode" class="form-control disabled-el"  value="{{ old('kode') }}" disabled />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="cust">Customer</label>
                                <select class="select2 w-100" id="cust" name="cust" autofocus required>
                                    <option label=""></option>
                                    @foreach($custs as $val)
                                        <option value="{{$val->kode}}" {{ $val->kode == old("cust") ? "selected" : ""}}>{{$val->kode}} - {{$val->nama}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="nama">Description</label>
                                    <input type="text" id="nama" name="nama" class="form-control" value="{{ old('nama') }}"  required  maxlength="100"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="quality">Quality</label>
                                    <input type="text" id="quality" name="quality" class="form-control" value="{{ old('quality') }}"  maxlength="30"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="group">Group of material</label>
                                <select class="select2 w-100" id="group" name="group" required>
                                    <option label=""></option>
                                    @foreach($groups as $val)
                                        <option value="{{$val->code}}" {{ $val->code == old("group") ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="uom">UOM</label>
                                <select class="select2 w-100" id="uom" name="uom">
                                    <option label=""></option>
                                    @foreach($uoms as $val)
                                        <option value="{{$val->code}}" {{ $val->code == old("uom") ? "selected" : ""}} >{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="note">Notes</label>
                                <textarea type="text" id="note" name="note" class="form-control" rows="3" maxlength="100">{{ old('note') }}</textarea>
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

    $('#cust').on('change', function() {
        let customer = $(this).val();
        if(customer){
            $.ajax({
                url:"{{route('article.code.create')}}",
                method:"GET",
                data:{
                    customer:customer,
                },
                success:function(result){
                    $('#kode').val(result)
                },
                error:function(e){
                    console.log(e);
                }
            })
        }else{
            $('#kode').val('none');
        }
    })


    $("#cmdSave").click(function(){       
        $('.disabled-el').removeAttr('disabled');
        $("#frmAdd").submit(); // Submit the form
    });

    $("#cmdCancel").click(function() {
        $(".select2").val('').trigger('change');
        $("#frmAdd").validate().resetForm();
        $('#kode').focus();
    });
</script>
@endsection