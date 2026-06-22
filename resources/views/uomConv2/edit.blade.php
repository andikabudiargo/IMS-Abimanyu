@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-6">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">yield('title')</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd"
      action="{{ route('uomConv2.update',['id' => Crypt::encryptString($uomConv2->id)]) }}"
      method="post"
      autocomplete="off">
    @csrf

    <div class="form-row">
        <div class="form-group col-md-12">
            <label>Article</label>
            <input type="text"
                   class="form-control"
                   value="{{ $uomConv2->article_alternative_code }} - {{ $uomConv2->article_desc }}"
                   readonly>

            <input type="hidden"
                   name="article"
                   value="{{ $uomConv2->article_code }}">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-12">
            <label>Supplier</label>
            <input type="text"
                   class="form-control"
                   value="{{ $uomConv2->supplier_code }} - {{ $uomConv2->supplier_name }}"
                   readonly>

            <input type="hidden"
                   name="supplier"
                   value="{{ $uomConv2->supplier_code }}">
        </div>
    </div>

    <div class="form-row">

        <div class="form-group col-md-6">
            <label>Unit From</label>

            <input type="text"
                   class="form-control"
                   value="{{ $uomConv2->unit_from }}"
                   readonly>

            <input type="hidden"
                   name="unitFrom"
                   value="{{ $uomConv2->unit_from }}">
        </div>

        <div class="form-group col-md-6">
            <label>Unit To</label>

            <input type="text"
                   class="form-control"
                   value="{{ $uomConv2->unit_to }}"
                   readonly>

            <input type="hidden"
                   name="unitTo"
                   value="{{ $uomConv2->unit_to }}">
        </div>

    </div>

    <div class="form-row">
        <div class="form-group col-md-6">
            <label>Unit Factor *</label>
            <input type="text"
                   id="unitFactor"
                   name="unitFactor"
                   class="form-control angka-decimal"
                   value="{{ old('unitFactor',$uomConv2->unit_factor) }}"
                   required
                   maxlength="20">
        </div>
    </div>

    <br>

    <div class="form-row">
        <div class="col-md-12">

            <a href="{{ route('uomConsv2.index') }}"
               class="btn btn-success">
                Back
            </a>

            <button class="btn btn-primary"
                    type="button"
                    id="cmdSave">
                Save
            </button>

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
    $(document).ready(function(){           
        validateFormToast("frmAdd");
    });

    $("#cmdSave").click(function(){       
        $('.disabled-el').removeAttr('disabled');
        $("#frmAdd").submit(); // Submit the form
    });

</script>
@endsection