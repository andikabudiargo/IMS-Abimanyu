<style>
    .jarak-antar-attr{
        padding-left: 0.3rem;
        margin-bottom: 0.3rem;
        padding-right: 0.3rem;
    }

    .jarak-antar-attr-qty-order{
        padding-left: 0.3rem;
        margin-bottom: 1.8rem;
        padding-right: 0.3rem;
    }

    .isian{
        padding-right:5px;
        padding-left:5px;
    }

    .isian-utama{
        padding-right:5px;
    }

    .isian-satu{
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

    #article_row .form-group {
        margin-bottom: 0.5rem;
    }
    
</style>
{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-end">
            <div class="col-md-5 col-12">
                <div class="form-group">
                    <label for="article_id" class="d-block d-md-none">Article Code</label>
                    <select class="dynamicSelect sku-select-system" id="article_id" name="article_id[]" data-dependent="article_id">
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label for="qty_order" class="d-block d-md-none">Qty</label>
                    <input type="text" class="form-control numeral-mask text-right" id = "qty_order" name="qty_order[]" maxlength="6" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="uom" class="d-block d-md-none">Uom</label>
                    <span class="" id = "uom" name="uom[]"></span>
                </div>
            </div>
            <div class="col-md-3 col-12">
                <div class="form-group">
                    <label for="note" class="d-block d-md-none">Note</label>
                    <input type="text" class="form-control" id = "note" name="note[]"  maxlength="100">
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();">
                        <i data-feather="trash-2" class="remove_button feather-24">
                        </i>
                    </a>
                </div>
            </div>
        </div>
        <hr class="d-block d-md-none" />
        {{-- <table class="table-bordered" style="width: 98%;table-layout: fixed;">
            <tbody>
                <tr>
                    <td class="isian-satu" style="width: 25%">
                        <select class="dynamicSelect sku-select-system" id="article_id" name="article_id[]" data-dependent="article_id">
                        </select>
                    </td>
                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext numeral-mask text-right" id = "qty_order" name="qty_order[]" maxlength="6" />
                    </td>
                    <td class="isian disabled" style="width: 5%">
                        <span class="" id = "uom" name="uom[]"></span>
                    </td>
                    <td class="isian" style="width: 10%">
                        <input type="text" class="form-control-plaintext" id = "note" name="note[]"  maxlength="100">
                    </td>
                    <td class="isian text-center" style="width: 5%">
                        <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();">
                            <i data-feather="trash-2" class="remove_button feather-24">
                            </i>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table> --}}
    </div>
    
</div>
{{-- \.table row --}} 