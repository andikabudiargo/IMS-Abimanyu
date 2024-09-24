@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">@yield('title')</h4>
                </div>
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('setting.store') }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-3 col-12">
                                <label for="ppn">PPN*</label>
                                <input type="text" id="ppn" name="ppn" class="form-control text-right angka" value="{{ old('ppn', $attribute ? $attribute['ppn'] :'') }}" required maxlength="20" autofocus />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-3 col-12">
                                <label for="pph23">PPH23*</label>
                                <input type="text" id="pph23" name="pph23" class="form-control text-right angka" value="{{ old('code', $attribute ? $attribute['pph23'] :'') }}" required maxlength="20" autofocus />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-3 col-12">
                                <label class="form-label" for="decimalPLaces">Decimal Places*</label>
                                <select class="select2 form-control w-100" id="decimalPLaces" name="decimalPLaces" required>
                                    <option value="1" {{ old('decimalPLaces',$attribute['decimalPlaces'] == '1'  ? "selected" : "") }}>1</option>
                                    <option value="2" {{ old('decimalPLaces',$attribute['decimalPlaces'] == '2'  ? "selected" : "") }}>2</option>
                                    <option value="3" {{ old('decimalPLaces',$attribute['decimalPlaces'] == '3'  ? "selected" : "") }}>3</option>
                                    <option value="4" {{ old('decimalPLaces',$attribute['decimalPlaces'] == '4'  ? "selected" : "") }}>4</option>
                                    <option value="5" {{ old('decimalPLaces',$attribute['decimalPlaces'] == '5'  ? "selected" : "") }}>5</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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
        validateFormToast("frmAdd");
    });   

    $("#cmdSave").click(function(){       
        $('.disabled-el').removeAttr('disabled');
        $("#frmAdd").submit(); // Submit the form
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection