@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">Status: New</h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div> --}}
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <input type="text" id="article" name="article" hidden>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="cNumber">Conversion Number</label>
                                    <input type="text" id="cNumber" name="cNumber" class="form-control" value="{{ $header->conversion_code }}" disabled/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="cValue">Conversion Value</label>
                                    <input type="text" id="cValue" name="cValue"  value="{{ number_format($conversionVal) }}" class="form-control numeral-mask-digit text-right" 
                                    data-toggle="tooltip" 
                                    data-placement="bottom" 
                                    title="Ubah nilai konversi di menu conversion -> setting"
                                    readonly/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label for="cName">Conversion Name*</label>
                                    <input type="text" id="cName" name="cName" class="form-control" value="{{ $header->conversion_name }}" readonly/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="cNote">Notes</label>
                                    <textarea type="text" id="cNote" name="cNote" class="form-control" rows="1" readonly>{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">List Article</h4>
                </div>
                <div class="card-body" >
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('conversion.conversion.headerColumn')
                            <div class="" id="item_row" style="max-height: 25rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                <label for="totalConversion" class="col-sm-5 col-form-label titik-dua">Total harga konversi</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalConversion" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('conversion.index') }}" class="btn btn-light">Back</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('conversion.conversion.addArticle')
@endsection
@section('styles')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/jquery-ui.css') }}">
<style>

    textarea {
        resize: none;
    }

    .mb-03{
        margin-bottom: 0.3rem;
    }
    
    label.titik-dua::after{
        content : ":"; 
        position : absolute;
        right : 1px;
    }

    td.isian{
        padding-right:10px;
        padding-left:10px;
    }

    td.nopadding{
        padding-right:0px;
        padding-left:0px;
    }

    td.isian-satu{
        padding-right:5px;
        padding-left:15px;
        width: 25%;border-top: 1px solid #ffffff !important;
        border-bottom: 1px solid #ffffff !important;
        border-left: 1px solid #ffffff !important;
    }

    td.disabled{
        background-color:#f8f8f8;
        color:black;
    }

    label.tanpa-padding{
        padding-top: 5px;
        padding-bottom: 0px;
    }

    .totalLine{
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }


</style>
@endsection
@section('scripts')
<script src="{{ asset('assets/js/ui.1.13.0.jquery-ui.js') }}"></script>
<script type="text/javascript">
    const dnSelect = $("#deliveryNumber")
    const deliveryDate = $('#deliveryDate');
    let currentDate = todayDate('dd-mm-yyyy');
    let inEdit = 'true';
    let inShow = 'true';

    if (deliveryDate.length) {
     deliveryDate.flatpickr({
            dateFormat: "d-m-Y",
            // maxDate: "today"
        });
    }

    $(document).ready(function(){           
        validateFormToast('frmAdd');
        let details = {!! $details !!};
        for(let i=0;i<details.length;i++){
            add_new_row(details[i].delivery_number,details[i].customer_id,details[i].customer_name,details[i].artikel_code,details[i].article_description,details[i].purchase_price,details[i].selling_price,details[i].conversion_total);
        }

        feather.replace({
            width: 14,
            height: 14
        });    

        // $("#totalConversion").val('');
        mask_thousand_digit(2);
    });   
           
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection