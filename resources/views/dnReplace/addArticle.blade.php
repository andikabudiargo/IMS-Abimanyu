<style>

    .mb-03{
        margin-bottom: 0.3rem;
    }

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

    .totalLine{
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    label.titik-dua::after{
        content : ":"; 
        position : absolute;
        right : 1px;
    }

    .margin-nol{
        margin-bottom:0.5rem;
    }

    .pointer-link {
        cursor: pointer;
        color: #33548a;
    }

    @media screen 
    and (min-device-width: 1200px) 
    and (max-device-width: 1600px) 
    and (-webkit-min-device-pixel-ratio: 1) { 
        .lebar-list-item{
            width:100%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }

    @media only screen and (min-width: 600px)
    and (max-width: 1200px){
        .lebar-list-item{
            width:200%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }
    
</style>
{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <table class="table-bordered" id="listData" style="width: 98%;table-layout: fixed;">
            <tbody>
                <tr>
                    <td class="isian disabled" style="width: 25%">
                        <input type="text" class="form-control-plaintext text-hitam" id = "articleCode" name="articleCode[]" data-code="" data-uom=""  data-price="" disabled>
                    </td>
                    <td class="isian disabled" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right" id = "totQtyReturn" name="totQtyReturn[]" disabled>
                    </td>
                    <td class="isian disabled" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right" id = "qtyReturn" name="qtyReturn[]" disabled>
                    </td>
                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right input-qty" autocomplete="off" id="qtyReplace" name="qtyReplace[]" maxlength="11" onkeyup="hitungTotal();">
                    </td>
                    <td class="isian disabled" style="width: 8%">
                        <input type="text" class="form-control-plaintext text-hitam"  id="uom" name="uom[]" disabled>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
{{-- \.table row --}}
<script type="text/javascript">
    let currentDate = "{{ $currentDateValue }}";
    let dariEdit="";
    
    function searchDn(obj,value) {
        $("#dnNumber").val('');
        $.ajax({
            url:"{{ route('dnReplace.list.return') }}",
            method:"GET",
            data:{
                value:value,
            },
            success:function(result){
                $('#'+obj).html(result);
                // $('#'+obj).val('').trigger('change');
            },
            error: function (response) {
                //Error here
                Swal.fire("Warning","Get list DN Return failed","warning");
            }
        })
    }

    function searchDnDet(value,dariEdit) {
        if(dariEdit=='false'){
            $.ajax({
                url:"{{ route('dnReplace.return.det') }}",
                method:"GET",
                data:{
                    value:value,
                },
                success:function(result){
                    // if (cloneCount > 1){
                        $("#articleRow").empty();
                        cloneCount=0;
                    // }
                    
                    if(result.length > 0 ){
                        for (let i = 0; i < result.length; i++) {
                            let article=result[i].article_code;
                            let articleCode=result[i].article_alternative_code;
                            let articleDesc=result[i].article_desc;
                            let qtyReturn=result[i].qty_return <= 0 ? 0 : result[i].qty_return;
                            let uom=result[i].uom;
                            let returnNumber=result[i].return_number;
                            let qty = 0;
                            let totQtyReturn=result[i].tot_qty_return <= 0 ? 0 : result[i].tot_qty_return;
                            addNewRow(article,articleCode,articleDesc,qtyReturn,uom,qty,returnNumber,totQtyReturn);
                        }
                    }
                },
                error: function (response) {
                    Swal.fire("Warning","Get detail DN Return failed","warning");
                }
            })
        }else{
            dariEdit='false';
        }
    }

    hitungTotal = () => {
        let objQtyReplace = $('#articleRow input[name="qtyReplace[]"]');
        let grandTotal = objQtyReplace.map(function(){return $(this).val().replace(/,/gi, '')}).get();
        let total = sumFromArray(grandTotal);
        $('#totalQTY').val(humanizeNumber(total));
        mask_thousand_digit(2);
    }

    hitungBaris = () => {
        let objArticle = $('#articleRow input[name="articleCode[]"]');
        $("#totalRow").val(objArticle.length);
    }  

    let cloneCount=0;
    function addNewRow(article,articleCode,articleDesc,qtyReturn,uom,qty,returnNumber,totQtyReturn) {
        returnNumber = returnNumber == null ? '':returnNumber;
        $("#articleRow").append($("#new_row").clone().html());
        cloneCount++;
        $("#articleRow").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleCode').attr('id', 'articleCode'+ cloneCount);
        $('#articleCode'+ cloneCount).attr('data-code', article);
        $('#articleCode'+ cloneCount).attr('data-uom', uom);
        $('#articleCode'+ cloneCount).attr('data-returnNumber', returnNumber);
        $('#articleCode'+ cloneCount).val(articleCode +" - " + articleDesc);
        $("#new_row"+ cloneCount).find('#qtyReturn').attr('id', 'qtyReturn'+ cloneCount);
        $("#new_row"+ cloneCount).find('#totQtyReturn').attr('id', 'totQtyReturn'+ cloneCount);
        $('#qtyReturn'+ cloneCount).val(qtyReturn*1);
        $('#totQtyReturn'+ cloneCount).val(totQtyReturn*1);
        $("#new_row"+ cloneCount).find('#qtyReplace').attr('id', 'qtyReplace'+ cloneCount);
        qty ? $('#qtyReplace'+ cloneCount).val(qty*1) : '';
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        $('#uom'+ cloneCount).val(uom);
        qtyReturn === 0 ? $('#qtyReplace'+ cloneCount).attr('disabled','disabled') : '';
        tombolPanah('qtyReplace');
        mask_thousand_digit(2);
        hitungBaris();
        qty ? hitungTotal() :'';
    }

    


</script>
