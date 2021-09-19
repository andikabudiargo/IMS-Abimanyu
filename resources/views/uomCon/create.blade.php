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
                    <form id="frmAdd" name="frmAdd" action="{{ route('uomCon.store') }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="dept">Unit From *</label>
                                <select class="select2 form-control dynamicSelect" id="unitFrom" name="unitFrom" data-dependent="unitTo" required>
                                    <option label=""></option>
                                    @foreach($uoms as $val)
                                        <option value="{{$val->code}}|{{$val->uom_group}}" {{ $val->code == old("unitFrom") ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="dept">Unit To *</label>
                                <select class="select2 form-control" id="unitTo" name="unitTo" required>                                
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unitFactor">Unit Factor *</label>
                                    <input type="text" id="unitFactor" name="unitFactor" class="form-control angka-decimal" value="{{ old('unitFactor') }}"  required  maxlength="20" required/>
                                </div>
                            </div>
                        </div>
                        <br>
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
@endsection
@section('scripts')
<script type="text/javascript">
    let change_active = 'yes'; 
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
        $("#frmAdd").submit(); // Submit the form
    });

    $("#cmdCancel").click(function() {
        reloadPage();
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
                        // console.log(dependent);
                        $('#'+dependent).html(result);
                        // $('#'+dependent).val('').trigger('change');
                    }
                })
            }else{
                $('#'+dependent).val('').trigger('change');
                $('#'+dependent).empty().append('<option value="">...</option>');
            }
        }
    })

    //get factor from unit conversion using fetch
    const hasil = $('#unitFactor');
    $('#unitTo').on('change', function() {
        let unitFrom = $('#unitFrom').val().split("|");
        let unitTo = $('#unitTo').val().split("|");
        let url = "{{ route('uomCon.get.factor',['unitFrom' => ':unitFrom','unitTo' => ':unitTo',]) }}";
        url = url.replace('%3AunitFrom', unitFrom[0]);
        url = url.replace('%3AunitTo', unitTo[0]);
        url = url.replace('amp;', '');        
        fetch(url)
            .then(response => response.json())
            .then(data => hasil.val(data.hasil));
    })
    
   

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection