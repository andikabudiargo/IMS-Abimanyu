@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">accounts</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" autocomplete="off">
                        @csrf
                        <input type="text" id="article" name="article" hidden>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label for="orderNum">Order Number</label>
                                <input type="text" id="orderNum" name="orderNum" class="form-control disabled-el" value="{{ $header->po_number }}" disabled />
                            </div>
                            <div class="form-group col-md-5">
                                <label class="form-label" for="supplier">Supplier*</label>
                                <select class="select2 form-control" id="supplier" name="supplier" required disabled>
                                    <option label=""></option>
                                    @foreach($supps as $val)
                                        <option value="{{$val->kode}}" {{$val->kode == $header->supplier_id ? "selected" : ""}} >{{$val->kode}} - {{$val->nama}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="tax">Tax*</label>
                                <select class="select2 form-control" id="tax" name="tax" required disabled>
                                    <option value="PKP" {{$val->pkp == 'PKP' ? "selected" : ""}} >PKP</option>
                                    <option value="NONPKP" {{$val->pkp == 'NONPKP' ? "selected" : ""}}>NON PKP</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="ppn">PPN</label>
                                <div class="input-group">
                                    <input type="text" class="form-control angka text-right" id = "ppn" name="ppn" value="{{ $header->ppn }}" maxlength="2" disabled/>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-2">
                                <label for="orderDate">Order Date</label>
                                <input type="text" id="orderDate" name="orderDate" class="form-control flatpickr-basic" value="{{ $header->po_date }}" placeholder="DD-MM-YYYY" disabled/>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="deliveryDate">Delivery Date</label>
                                <input type="text" id="deliveryDate" name="deliveryDate" class="form-control flatpickr-basic" value="{{ $header->delivery_date }}" placeholder="DD-MM-YYYY" disabled/>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="term">TERM</label>
                                <div class="input-group">
                                    <input type="text" class="form-control angka text-right" id = "term" name="term" value="{{ $header->termin }}" maxlength="2" disabled/>
                                    <div class="input-group-append">
                                        <span class="input-group-text">DAYS</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="currency">Currency*</label>
                                <select class="select2 form-control" id="currency" name="currency" required disabled>
                                    @foreach($currency as $val)
                                    <option value="{{$val}}" {{$val == $header->currency ? "selected" : ""}} >{{$val}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="kurs">Kurs</label>
                                    <input type="text" id="kurs" name="kurs" class="form-control angka" value="{{ $header->kurs }}" maxlength="6" disabled />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="note">Notes</label>
                                <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled> {{ $header->note }} </textarea>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <div class="row clearfix">
                        <div class="col-md-4">
                            <label>Article Code</label>
                        </div>
                        <div class="col-md-2 jarak-antar-attr">
                            <label>Group</label>
                        </div>
                        <div class="col-md-1 jarak-antar-attr">
                            <label>Stock</label>
                        </div>
                        <div class="col-md-2 jarak-antar-attr">
                            <label>Order</label>
                        </div>
                        <div class="col-md-2 jarak-antar-attr">
                            <label>Price</label>
                        </div>
                        <div class="col-md-1 jarak-antar-attr text-center">
                            <label>Action</label>
                        </div>
                    </div>
                    <hr  style="margin-top: 0.1rem;">
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;">
                        <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                        @foreach ($detail as $key =>$item)
                            <div id="new_row{{ $key }}" class="row clearfix tanda-baris" >
                                <div class="form-group col-md-4" style="margin-bottom: 0.3rem;padding-right: 0.3rem;margin-bottom: 0rem">
                                    <select class="select2 dynamicSelect sku-select-system" id="article_id{{ $key }}" name="article_id[]" data-dependent="article_id" disabled>
                                        @foreach($articles as $val)
                                            <option value="{{$val->article_code}}|{{$val->group}}|{{$val->qty}}|{{$val->uom1}}" {{$val->article_code ==$item->article_code ? "selected" : ""}}>{{$val->article_alternative_code}} | {{$val->article_desc}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2 jarak-antar-attr">
                                    <input type="text" class="form-control" id = "group" name="group[]" value="{{ $item->group }}"  disabled>
                                </div>
                                <div class="form-group col-md-1 jarak-antar-attr">
                                    <input type="text" class="form-control text-right" id = "qty_stock" name="qty_stock[]" value="{{ $item->qty_stock ==0 ? 0 :$item->qty_stock }}" disabled>
                                </div>
                                <div class="input-group col-md-2 jarak-antar-attr-qty-order">
                                    <input type="text" class="form-control angka text-right" id = "qty_order" name="qty_order[]" value="{{ $item->qty }}" maxlength="9" disabled/>
                                    <div class="input-group-append">
                                        <span class="input-group-text" id = "uom" name="uom[]">{{ $item->uom }}</span>
                                    </div>
                                </div>
                                <div class="form-group col-md-2 jarak-antar-attr">
                                    <input type="text" class="form-control numeral-mask text-right" id = "price" name="price[]" value="{{ $item->price }}"  maxlength="11" disabled>
                                    <div class="text-right"><span id="totalLine" name="totalLine[]">{{'Total: '. number_format($item->qty * $item->price) }}</span></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control-plaintext text-right font-weight-bold" id="totalRow" />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalQTY" class="col-sm-4 col-form-label titik-dua">Total QTY</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control-plaintext text-right font-weight-bold" id="totalQTY" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                <label for="totalAmount" class="col-sm-3 col-form-label titik-dua">Bruto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control-plaintext text-right font-weight-bold" id="totalAmount" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPN" class="col-sm-3 col-form-label titik-dua">Discount </label>
                                <div class="col-sm-2" style="padding-right: 0rem;">
                                    <input type="text" class="form-control text-right font-weight-bold" id="persenDiscount" value="{{ $header->discount }}"  maxlength="2"/>
                                </div>
                                <div class="col-sm-4" style="padding-left: 0rem;">
                                    <input type="text" class="form-control-plaintext text-right font-weight-bold" id="totalDiscount" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPN" class="col-sm-3 col-form-label titik-dua">PPN <span id="nilaiPPN"></span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control-plaintext text-right font-weight-bold" id="totalPPN" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPH" class="col-sm-3 col-form-label titik-dua">PPH <span>22</span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control-plaintext text-right font-weight-bold" id="totalPPH" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalNetto" class="col-sm-3 col-form-label titik-dua">Netto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control-plaintext text-right font-weight-bold" id="totalNetto" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('salesOrder.addArticle')
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
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');    
    $(document).ready(function(){           
        activate_angka();
        mask_thousand();
        hitungGrandTotal();
    });

    function hitungGrandTotal(){
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objQtyTiw= $('#article_row input[name="qty_order[]"]');
        let objQTY= $('#article_row input[name="qty_order[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let persenDiscount = $('#persenDiscount').val() || 0;
        let ppn= $('#ppn').val();
        let totalQty= 0;
        let totalAmount=0

        var arr = objQtyTiw.map(function (i) {
            let qty = parseInt(objQTY.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            let price = parseInt(objPrice.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            totalQty+= qty;
            totalAmount+= qty*price;
        }).get();
        
        $("#totalRow").val(objArticle.length);
        $("#nilaiPPN").text(ppn);
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#totalAmount").val(humanizeNumber(totalAmount));
        $("#totalDiscount").val(humanizeNumber((totalAmount*parseInt(persenDiscount))/100));
        $("#totalPPN").val(humanizeNumber((parseInt(ppn)*totalAmount)/100));
        $("#totalPPH").val(0);
        $("#totalNetto").val(humanizeNumber((totalAmount+((parseInt(ppn)*totalAmount)/100))-((totalAmount*parseInt(persenDiscount))/100)));

    }

       
</script>
@endsection