@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: New</h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <input type="text" id="article" name="article" hidden>
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label for="prNumber">Request Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="prNumber" name="prNumber" class="form-control disabled-el" value="{{ $header->number }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Order Date*</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->date }}" disabled />
                                </div>
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="dept">Department*</label>
                                    <select class="select2 form-control" id="dept" name="dept" disabled>
                                        <option label=""></option>
                                        @foreach($depts as $val)
                                            <option value="{{$val->code}}" {{$val->code == $header->dept ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-10">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled>{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <div>
                        <table class="" style="width:98%;table-layout: fixed;">
                            <tbody>
                                <tr>
                                    <td class="isian-satu" style="width: 15%">
                                        <label>Purchase Order</label>
                                    </td>
                                    <td class="" style="width: 25%">
                                        <label>Article Code</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>QTY</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>UOM</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Note</label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;">
                        <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                        @foreach ($detail as $key =>$item)
                            <div id="new_row{{ $key }}" class="tanda-baris" >
                                <table class="table-bordered" style="width: 98%;table-layout: fixed;">
                                    <tbody>
                                        <tr>
                                            <td class="isian-satu" style="width: 15%">
                                                <input type="text" class="form-control-plaintext" id = "purchase_order" name="purchase_order[]" value="{{ $item->po_number }}"  disabled/>
                                            </td>
                                            <td class="" style="width: 25%">
                                                <select class="select2 dynamicSelect sku-select-system" id="article_id{{ $key }}" name="article_id[]" data-dependent="article_id" disabled>
                                                    @foreach($articles as $val)
                                                        <option value="{{ $val->article_code }}|{{ $val->uom }}|{{ $val->third_party }}" {{$val->article_code == $item->article_code ? "selected" : ""}}>{{$val->article_code}} - {{$val->article_desc}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="isian" style="width: 5%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" id = "qty_order" name="qty_order[]" value="{{ $item->qty }}" maxlength="9" disabled/>
                                            </td>
                                            <td class="isian disabled" style="width: 5%">
                                                <span class="" id = "uom" name="uom[]">{{ $item->uom }}</span>
                                            </td>
                                            <td class="isian" style="width: 10%">
                                                <input type="text" class="form-control-plaintext" id = "note" name="note[]" value="{{ $item->note }}" maxlength="100" disabled>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                        {{-- <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('purchaseRequest.addArticle')
@endsection
@section('styles')
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

</style>
@endsection
@section('scripts')
<script type="text/javascript">   
    $(document).ready(function(){           
    
    });
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection