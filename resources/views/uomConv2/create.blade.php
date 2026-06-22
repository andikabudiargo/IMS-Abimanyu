@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-6">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">yield('title')</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('uomConv2.store') }}" method="post" autocomplete="off">
                        @csrf
                        <div class="form-row">
    <div class="form-group col-md-12">
        <label>Article *</label>
        <select class="select2 form-control dynamicSelect"
        id="article"
        name="article"
        data-dependent="supplier">
    <option value="">Choose Article</option>
    @foreach($articles as $val)
        <option value="{{ $val->article_code }}">
            {{ $val->article_alternative_code }} - {{ $val->article_desc }}
        </option>
    @endforeach
</select>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label>Supplier *</label>
        <select class="select2 form-control dynamicSelect"
        id="supplier"
        name="supplier">
    <option value="">Choose Supplier</option>
</select>
    </div>
</div>

<div class="form-row">
   <div class="form-group col-md-6">
    <label>Unit From *</label>

    <select class="select2 form-control"
        id="unitFrom"
        name="unitFrom">
    <option value="">Choose Unit</option>
    @foreach($uoms as $val)
        <option value="{{$val->code}}">
            {{$val->code}} - {{$val->name}}
        </option>
    @endforeach
</select>
</div>

<div class="form-group col-md-6">
    <label>Unit To *</label>

    <select class="select2 form-control"
            id="unitTo"
            name="unitTo">
        <option value="">Choose Unit</option>
        @foreach($uoms as $val)
            <option value="{{$val->code}}">
                {{$val->code}} - {{$val->name}}
            </option>
        @endforeach
    </select>
</div>
</div>

<div class="form-row">
    <div class="col-md-12">
        <div class="form-group">
            <label>Unit Factor *</label>
            <input type="text" id="unitFactor" name="unitFactor"
                   class="form-control angka-decimal"
                   required maxlength="20"/>
        </div>
    </div>
</div>
                        <br>
                        <div class="form-row">
                            <div class="col-ms-12">
                                <button class="btn btn-success" type="reset" id="cmdCancel" name="cmdCancel">New</button>
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
@endsection
@section('scripts')
<script type="text/javascript">
    let change_active = 'yes'; 
    $(document).ready(function(){           
        validateFormToast("frmAdd");
    });

    $("#cmdSave").click(function(){       
        $("#frmAdd").submit(); // Submit the form
    });

    $("#cmdCancel").click(function() {
        window.location.reload();
    });

   $(document).on('change', '#article', function () {

    let articleCode = $(this).val();

    if (articleCode !== '') {

        $.ajax({
            url: "{{ route('dynamic.dependent') }}",
            method: "POST",
            data: {
                value: articleCode,
                dependent: 'supplier'
            },
            success: function (result) {
                $('#supplier').html(result).trigger('change.select2');
                $('#unitFrom').val('').trigger('change');
                $('#unitTo').val('').trigger('change');
                $('#unitFactor').val('');
            }
        });

        $.ajax({
            url: "{{ route('uomConv2.uom') }}",
            method: "POST",
            data: {
                article_code: articleCode
            },
            success: function (res) {

                $('#unitTo').val('').trigger('change');
                $('#unitFrom').val('').trigger('change');
                $('#unitTo').prop('disabled', false);

                if (res.has_conversion) {

                    // sudah ada conversion -> unitTo terkunci ke hasil conversion
                    $('#unitTo')
                        .val(res.uom)
                        .trigger('change')
                        .prop('disabled', true);

                } else {

                    // belum ada conversion -> default unitFrom & unitTo dari article.uom
                    $('#unitFrom')
                        .val(res.uom)
                        .trigger('change');

                    $('#unitTo')
                        .val(res.uom)
                        .trigger('change')
                        .prop('disabled', false);
                }
            }
        });

    }

});
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection