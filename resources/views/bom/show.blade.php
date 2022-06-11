@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="edit-form">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="form-group row">
                        <label for="bomNumber" class="col-sm-4 col-form-label col-form-label-sm">BOM Number</label>
                        <div class="col-md-8">
                            <input type="text" id="bomNumber" name="bomNumber" class="form-control form-control-sm" value="{{ $header->bom_code }}" disabled />
                        </div>
                    </div>                    
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
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="articleCode">Article*</label>
                                    <select class="select2 form-control" id="articleCode" name="articleCode" disabled>
                                        <option value="">All</option>
                                        @foreach($articleHeader as $val)
                                            <option value="{{ $val->article_code }}|{{ $val->uom }}|{{ $val->cust_name }}|{{ $val->group }}|{{ $val->third_party }}|{{ $val->group_of_material }}" {{$val->article_code == old("articleCode",$header->article_code) ? "selected" : ""}}>{{ $val->article_alternative_code }} - {{ $val->article_desc }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="customer">Customer</label>
                                    <input type="text" id="customer" name="customer" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="group">Group of material</label>
                                    <input type="text" id="group" name="group" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-1">
                                    <label for="uom">UOM</label>
                                    <input type="text" id="uom" name="uom" class="form-control" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="tag">Tag</label>
                                    <input type="text" id="tag" name="tag" value="{{ old('tag',$header->tag) }}" class="form-control numeral-mask-digit" maxlength="5" disabled/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="passRate">Pass Rate</label>
                                    <input type="text" id="passRate" name="passRate" value="{{ old('passRate',$header->pass_rate) }}" class="form-control numeral-mask-digit" maxlength="5" disabled/>
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="passThru">Pass trough</label>
                                    <div class="input-group input-group-merge">
                                        <input type="text" id="passThru" name="passThru" value="{{ old('passThru',$header->pass_thru) }}" class="form-control numeral-mask-digit" maxlength="5" disabled/>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="cycleTime">Cycle time buffing</label>
                                    <input type="text" id="cycleTime" name="cycleTime" value="{{ old('cycleTime',$header->cycle_time) }}" class="form-control numeral-mask-digit" maxlength="5" disabled/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled>{{ old('note',$header->note) }}</textarea>
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
                    <div class="form-row">
                        <div class="col-md-6 col-12 d-none d-md-block">
                            <div class="form-group">
                                <label class="d-none d-md-block">Article Code</label>
                            </div>
                        </div>
                        <div class="col-md-2 col-12 d-none d-md-block">
                            <div class="form-group">
                                <label class="d-none d-md-block text-right">QTY</label>
                            </div>
                        </div>
                        <div class="col-md-1 col-12 d-none d-md-block">
                            <div class="form-group">
                                <label class="d-none d-md-block">Uom</label>
                            </div>
                        </div>
                        <div class="col-md-2 col-12 d-none d-md-block">
                            <div class="form-group">
                                <label class="d-none d-md-block">Type</label>
                            </div>
                        </div>
                    </div>
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;">
                        <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                        @foreach ($detail as $key =>$item)
                            <div id="new_row{{ $key }}" class="tanda-baris barisDetail" >
                                <div class="form-row d-flex align-items-center">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group margin-nol">
                                            <select class="form-control sku-select-system" id="article_id{{ $key }}" name="article_id[]" disabled>
                                                @foreach($articles as $val)
                                                    <option value="{{ $val->article_code }}|{{ $val->uom }}|{{ $val->costprice }}|{{ $val->article_type }}|{{ $val->type_name }}" data-uom-group={{ $val->uom_group }} {{ $val->article_code == $item->article_code ? "selected" : "" }}>{{$val->article_code}} - {{$val->article_desc}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-12">
                                        <div class="form-group margin-nol">
                                            <label for="qty_stock" class="d-block d-md-none">QTY</label>
                                            <input type="text" class="form-control text-right tombol-panah" data-nama-el-kiri="article_id" data-type-el-kiri="select" data-uom-group={{ $item->uom_group }} id = "qtyBom{{ $key }}" name="qtyBom[]" value="{{ $item->uom_group =='PIECE' ? $item->qty*1 : $item->qty }}" maxlength="6" disabled/>
                                        </div>
                                    </div>
                                    <div class="col-md-1 col-12">
                                        <div class="form-group margin-nol">
                                            <label for="uom" class="d-block d-md-none">Uom</label>
                                            <span class="" id = "uom" name="uom[]">{{ $item->uom }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-12">
                                        <div class="form-group margin-nol">
                                            <label for="uom" class="d-block d-md-none">Type</label>
                                            <span class="" id = "type" name="type[]">{{ $item->type_name }}</span>
                                        </div>
                                    </div>
                                </div>
                                <hr class="d-block d-md-none" />
                            </div>
                        @endforeach
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12 col-12">
                            <a href="{{ route('boms.index') }}" class="btn btn-success">Back</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('bom.addArticle')
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
    let currentDate = todayDate('dd-mm-yyyy');    
    $(document).ready(function(){           
        mask_thousand_digit(numberOfDecimalDigit);
        $('.sku-select-system').select2();
    });
       
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection