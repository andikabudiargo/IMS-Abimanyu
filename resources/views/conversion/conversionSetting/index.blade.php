@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">@yield('title')</h4>
                </div> --}}
                <div class="card-body">
                    <h4>Conversion : {{ $conversion->conversion_value ? number_format($conversion->conversion_value) : '' }}</h4>
                    <h4>Last Update : {{ $conversion->created_at }}</h4>
                    <h4>Updated By : {{ $conversion->created_by }}</h4>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('conversionSetting.store') }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-4 col-12">
                                <label for="cVal">Ubah Nilai Konversi</label>
                                <input type="text" id="cVal" name="cVal" class="form-control text-right numeral-mask" value="{{ old('cVal') }}" maxlength="50" autofocus required/>
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
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $('.disabled-el').removeAttr('disabled');
            $("#frmAdd").submit();
        }    
    });

    mask_thousand();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection