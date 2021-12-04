<style>
    .jarak-antar-attr{
        padding-left: 0.3rem;
        margin-bottom: 0.3rem;
        padding-right: 0.3rem;
    }

    .jarak-antar-attr-qtyBom-order{
        padding-left: 0.3rem;
        margin-bottom: 1.8rem;
        padding-right: 0.3rem;
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
    
</style>
{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris barisDetail" >
        <table class="table-bordered" style="width: 98%;table-layout: fixed;">
            <tbody>
                <tr>
                    <td class="isian-satu" style="width: 25%">
                        <select class="form-control" id="article_id" name="article_id[]">
                        </select>
                    </td>
                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext numeral-mask-digit text-right tombol-panah" id = "qtyBom" name="qtyBom[]" maxlength="10" />
                    </td>
                    <td class="isian disabled" style="width: 5%">
                        <span class="" id = "uom" name="uom[]"></span>
                    </td>
                    <td class="isian disabled" style="width: 10%">
                        <span class="" id = "type" name="type[]"></span>
                    </td>
                    <td class="isian text-center" style="width: 5%">
                        <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();">
                            <i data-feather="trash-2" class="remove_button feather-24">
                            </i>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
{{-- \.table row --}} 