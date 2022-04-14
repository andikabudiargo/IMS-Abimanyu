@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('article.store') }}" method="post"  autocomplete="off" enctype="multipart/form-data" >
                        @csrf
                        <div class="form-row d-none">
                            <div class="form-group col-md-6">
                                <label for="kode">Article Code 1</label>
                                <input type="text" id="kode" name="kode" class="form-control disabled-el"  value="{{ old('kode') }}" disabled />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="articleType">Article Type*</label>
                                <select class="select2 form-control" id="articleType" name="articleType" autofocus required>
                                    <option value=""></option>
                                    @foreach($types as $val)
                                        <option value="{{$val->code}}" {{ $val->code == old("articleType") ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>            
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="group">Group of material</label>
                                <select class="select2 form-control" id="group" name="group">
                                    <option value=""></option>
                                    @foreach($groups as $val)
                                        <option value="{{$val->code}}" {{ $val->code == old("group") ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>              
                        </div>
                        <div class="form-row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="colorCode">Color Code</label>
                                    <input type="text" id="colorCode" name="colorCode" class="form-control text-uppercase" value="{{ old('colorCode') }}" maxlength="10"/>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="variant">Variant</label>
                                    <input type="text" id="variant" name="variant" class="form-control text-uppercase" value="{{ old('variant') }}" maxlength="10"/>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="cust" id="custLable">Customer/Supplier*</label>
                                <select class="select2 form-control" id="cust" name="cust[]" autofocus required multiple>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="nama">Description*</label>
                                    <input type="text" id="nama" name="nama" class="form-control text-uppercase" value="{{ old('nama') }}" maxlength="100" required/>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="price">Price</label>
                                <input type="text" id="price" name="price" class="form-control numeral-mask text-right" value="{{ old('price') }}" maxlength="12"/>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="uom">Smallest Unit*</label>
                                <select class="select2 form-control" id="uom" name="uom" required>
                                    <option value=""></option>
                                    @foreach($uoms as $val)
                                        <option value="{{$val->code}}" {{ $val->code == old("uom") ? "selected" : ""}} >{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="note">Notes</label>
                                <textarea type="text" id="note" name="note" class="form-control" rows="3" maxlength="100">{{ old('note') }}</textarea>
                            </div>
                        </div>
                        <div id="fileUpload" class="d-none">
                        </div>
                    </form>
                    <div class="form-row">
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
                    <div class="form-row">
                        <div class="col-12">
                            <button class="btn btn-success" type="reset" id="cmdNew" name="cmdNew">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                        </div>
                    </div>
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
<script src="{{asset('app-assets/vendors/js/extensions/dropzone.min.js')}}"></script>
<script type="text/javascript">

    $(document).ready(function(){    
        validateFormToast("frmAdd");
        mask_thousand();
    });

    let availableTags ="{{ $articles }}";
    availableTags=availableTags.replace(/[[\]]/g,'');
    availableTags=availableTags.replace(/&quot;/g,'').split(",");
    $("#nama").autocomplete({
        source: availableTags
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
      
    $("#cmdNew").click(function() {
        window.location.reload();
    });

    $('#articleType').on('change', function() {
        let type = $(this).val();
        let obj = "cust";
        $.ajax({
            url:"{{route('get.supplier')}}",
            method:"POST",
            data:{
                type:type,
                dependent:obj
            },
            success:function(result){
                type === 'FG' ? $('#custLable').text("Customer*"):"";
                type != 'FG' && type != 'RM' ? $('#custLable').text("Supplier*"):type === 'RM'?$('#custLable').text("Customer/Supplier*"):"";
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