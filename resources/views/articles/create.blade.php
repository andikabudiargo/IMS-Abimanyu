@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('article.store') }}" method="post"  autocomplete="off" enctype="multipart/form-data" >
                        @csrf
                        <div class="row d-none">
                            <div class="form-group col-md-6">
                                <label for="kode">Article Code 1</label>
                                <input type="text" id="kode" name="kode" class="form-control disabled-el"  value="{{ old('kode') }}" disabled />
                            </div>
                            <div class="form-group col-md-6">
                                <label for="kode2">Article Code 2</label>
                                <input type="text" id="kode2" name="kode2" class="form-control disabled-el"  value="{{ old('kode2') }}" disabled />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="articleType">Article Type*</label>
                                <select class="select2 form-control" id="articleType" name="articleType" autofocus required>
                                    <option label=""></option>
                                    @foreach($types as $val)
                                        <option value="{{$val->code}}" {{ $val->code == old("articleType") ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>              
                            <div class="form-group col-md-6">
                                <label class="form-label" for="group">Group of material</label>
                                <select class="select2 form-control" id="group" name="group">
                                    <option label=""></option>
                                    @foreach($groups as $val)
                                        <option value="{{$val->code}}" {{ $val->code == old("group") ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>              
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="cust" id="custLable">Customer*</label>
                                <select class="select2 form-control" id="cust" name="cust" autofocus required>         
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="nama">Description*</label>
                                    <input type="text" id="nama" name="nama" class="form-control" value="{{ old('nama') }}"  required  maxlength="100"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="price">Price</label>
                                <input type="text" id="price" name="price" class="form-control numeral-mask text-right" value="{{ old('price') }}" maxlength="10"/>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="uom">Smallest Unit*</label>
                                <select class="select2 form-control" id="uom" name="uom" required>
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
                        <div id="fileUpload" class="d-none">
                        </div>
                    </form>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Product image</h4>
                                </div>
                                <form class="dropzone dropzone-area" id="dropzone" action="{{ route('article.image.store') }}" 
                                        method="post" 
                                        autocomplete="off" 
                                        enctype="multipart/form-data">
                                    @csrf
                                    <div class="dz-message">Drop files here or click to upload.</div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button class="btn btn-outline-secondary" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button>
                            <button class="btn btn-success" type="button" id="cmdSave" name="cmdSave">Save</button>
                        </div>
                    </div>
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
<script src="{{asset('app-assets/vendors/js/extensions/dropzone.min.js')}}"></script>
<script type="text/javascript">

    $(document).ready(function(){           
        let $form = $("#frmAdd");
        $form.validate({
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

        mask_thousand();
        
    });
    
    // Dropzone.autoDiscover = false;
    Dropzone.options.dropzone = {
        maxFilesize: 3, // MB
        acceptedFiles: ".jpeg,.jpg,.png,.gif",
        addRemoveLinks: true,
        dictRemoveFile: 'Delete',
        parallelUploads:10,
        uploadMultiple:true,
        timeout: 5000,
        autoProcessQueue: false,
        init: function () {
            let myDropzone = this;
            $("#cmdSave").click(function (e) {
                e.preventDefault();
                // let jumFile = myDropzone.getAcceptedFiles().length;
                let jumFile = myDropzone.getQueuedFiles().length
                if (jumFile > 0){
                    myDropzone.processQueue();
                }else{
                    $('.disabled-el').removeAttr('disabled');
                    $("#frmAdd").submit();
                }
            });
            // Update selector to match your button
            // this.on('sending', function(file, xhr, formData) {
            //     // Append all form inputs to the formData Dropzone will POST
            //     var data = $('#frmAdd').serializeArray();
            //     $.each(data, function(key, el) {
            //         // console.log(el.name);
            //         // console.log(el.value);
            //         formData.append(el.name, el.value);
            //     });
            // });
        },
        success: function( file, response ){
            // obj = JSON.parse(response);
            // console.log(response.message); // <---- here is your filename

            jQuery.each( response.files, function( i, val ) {
                if(!$('#files_'+i).length){
                    $('#fileUpload').append('<input type="text" id="files_'+ i+'" name="files[]" value="'+ val +'">');
                }
            });
            
            $('.disabled-el').removeAttr('disabled');
            $("#frmAdd").submit();
        }
    };
      
    $("#cmdCancel").click(function() {
        $(".select2").val('').trigger('change');
        $("#frmAdd").validate().resetForm();
        $('#kode').focus();
    });

    $('#articleType').on('change', function() {
        let type = $(this).val();
        console.log(type);
        let obj = "cust";
        $.ajax({
            url:"{{route('get.supplier')}}",
            method:"POST",
            data:{
                type:type,
                dependent:obj
            },
            success:function(result){
                type === 'FG' || type === 'RM' ?$('#custLable').text("Customer"):$('#custLable').text("Supplier");
                $('#'+obj).html(result);
                $('#'+obj).val('').trigger('change');
            }
        })
    })

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });


</script>
@endsection