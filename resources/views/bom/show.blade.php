@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="edit-form">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusBom }}</span></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            @foreach( $headers as $key =>$header )
                                <li class="nav-item">
                                    <a class="nav-link {{ $key == 0 ? 'active':'' }}" 
                                    id="po-tab" 
                                    data-toggle="tab" 
                                    href="#rev{{ $key }}" 
                                    aria-controls="revisi{{ $key }}" 
                                    role="tab" 
                                    aria-selected="false" 
                                    data-ajax-detail="true" 
                                    data-po-number="{{ $header->bom_code }}">{{ $key == 0 ? 'Main':'Revision '.$key }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content">
                            @foreach( $headers as $key =>$header2 )
                                <div class="tab-pane {{ $key == 0 ? 'active':'' }}" id="rev{{ $key }}" aria-labelledby="revison{{ $key }}-tab" role="tabpanel">
                                    <form id="frmAdd{{ $key }}" name="frmAdd{{ $key }}" autocomplete="off">
                                        <div class="form-row">
                                            <div class="col-md-3">
                                                <label for="bomNumber" class="form-label">BOM Number</label>
                                                <input type="text" id="bomNumber" name="bomNumber" class="form-control form-control-sm" value="{{ $header2->bom_code }}" disabled />
                                            </div>
                                        </div>  
                                        <div class="form-row">
                                            <div class="form-group col-md-5">
                                                <label class="form-label" for="articleCode">Article*</label>
                                                <input type="text" id="customer" name="customer" class="form-control" value="{{ $header2->article }}"disabled />
                                                {{-- <select class="select2 form-control" id="articleCode" name="articleCode" disabled>
                                                    <option value="">All</option>
                                                    @foreach($articleHeader as $val)
                                                        <option value="{{ $header2->article_code }}|{{ $header2->uom }}|{{ $header2->cust_name }}|{{ $header2->group }}|{{ $header2->third_party }}|{{ $header2->group_of_material }}" {{$header2->article_code == old("articleCode",$header->article_code) ? "selected" : ""}}>{{ $header2->article_alternative_code }} - {{ $header2->article_desc }}</option>
                                                    @endforeach
                                                </select> --}}
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="customer">Customer</label>
                                                <input type="text" id="customer" name="customer" value="{{ $header2->cust_name }}"class="form-control" disabled />
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
                                                <input type="text" id="tag" name="tag" value="{{ old('tag',$header2->tag) }}" class="form-control numeral-mask-digit" maxlength="5" disabled/>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="passRate">Pass Rate</label>
                                                <input type="text" id="passRate" name="passRate" value="{{ old('passRate',$header2->pass_rate) }}" class="form-control numeral-mask-digit" maxlength="5" disabled/>
                                            </div>
            
                                            <div class="form-group col-md-2">
                                                <label for="passThru">Pass trough</label>
                                                <div class="input-group input-group-merge">
                                                    <input type="text" id="passThru" name="passThru" value="{{ old('passThru',$header2->pass_thru) }}" class="form-control numeral-mask-digit" maxlength="5" disabled/>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="cycleTime">Cycle time buffing</label>
                                                <input type="text" id="cycleTime" name="cycleTime" value="{{ old('cycleTime',$header2->cycle_time) }}" class="form-control numeral-mask-digit" maxlength="5" disabled/>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-8">
                                                <label class="form-label" for="note">Notes</label>
                                                <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled>{{ old('note',$header2->note) }}</textarea>
                                            </div>
                                        </div>
                                    </form>
                                    <hr>
                                    <h4 class="card-title">Article</h4>
                                    <div class="table-responsive main-table">
                                        <table class="table table-bordered w-100" >
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Article Code</th>
                                                    <th class="text-right">QTY</th>
                                                    <th class="text-right">Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach( $details as $key =>$item )
                                                @if($item->bom_code === $header2->bom_code )
                                                    <tr>
                                                        <td ></td>
                                                        <td >{{ $item->article }}</td>
                                                        {{-- <td class="text-right">{{ $item->uom_group =='PIECE' ? number_format($item->qty) : number_format($item->qty,3) }} {{ $item->uom }}</td> --}}
                                                        <td class="text-right">{{ number_format($item->qty,4) }} {{ $item->uom }}</td>
                                                        <td >{{ $item->article_type }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-end mt-75">
                                        <div class="col-md-4">
                                            <span>ROW : {{ $header2->sum_row }}</span> <br>
                                            <span>QTY : {{ $header2->sum_qty }}</span>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="table-responsive">
                                                <table class="table table-bordered w-100">
                                                    
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <a href="{{ route('boms.index') }}" class="btn btn-success">Back</a>
                                    <a href="{{ route('bom.print', ['id'=>Crypt::encryptString($header2->id)]) }}" target="_blank" type="button" class="btn btn-primary">
                                        <i data-feather="printer"></i>
                                        <span>{{ __("Print") }}</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        
                        
                    </div>
                </div>
            </div>
        </div>
        {{-- <div class="col-12">
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
                                                    <option value="{{ $header2->article_code }}|{{ $header2->uom }}|{{ $header2->costprice }}|{{ $header2->article_type }}|{{ $header2->type_name }}" data-uom-group={{ $header2->uom_group }} {{ $header2->article_code == $item->article_code ? "selected" : "" }}>{{$header2->article_code}} - {{$header2->article_desc}}</option>
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
        </div> --}}
    </div>
</section>
@include('bom.addArticle')
@endsection
@section('styles')
<style>
    textarea {
        resize: none;
    }   

    table {
        counter-reset: rowNumber+1;
    }

    table tr:not(:first-child) {
        counter-increment: rowNumber;
    }

    table tr td:first-child::before {
        content: counter(rowNumber);
        min-width: 1em;
        margin-right: 0.5em;
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