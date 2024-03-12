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
                        <input type="text" class="form-control-plaintext text-hitam" id = "article_id" name="article_id[]" data-code="" data-uom=""  data-price="" disabled>
                    </td>
                    <td class="isian disabled" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam text-right" id = "qty_po" name="qty_po[]" disabled>
                    </td>
                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right" autocomplete="off" id="qty_rec" name="qty_rec[]" maxlength="11">
                    </td>
                    <td class="isian" style="width: 8%">
                        <select class="form-control text-hitam" id="uom" name="uom[]">
                        </select>
                    </td>
                    <td class="isian" style="width: 5%">
                        <input type="text" class="form-control-plaintext text-hitam numeral-mask-digit text-right" autocomplete="off" id="qty_free" name="qty_free[]" maxlength="11" />
                    </td>
                    <td class="isian" style="width: 8%">
                        <select class="form-control text-hitam" id="uomFree" name="uomFree[]">
                        </select>
                    </td>
                    <td class="isian disabled text-right" style="width: 5%">
                        <span class="text-hitam numeral-mask-digit text-hitam" id="totalQty" name="totalQty[]"></span>
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
    
    function searchPo(obj,value) {
      $.ajax({
        url:"{{ route('receiving.list.po') }}",
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
            Swal.fire("Warning","Get list PO failed","warning");
        }
      })
    }

    function searchPoDet(value,dariEdit) {
        if(dariEdit=='false'){
            $.ajax({
                url:"{{ route('receiving.po.det') }}",
                method:"GET",
                data:{
                    value:value,
                },
                success:function(result){
                    // if (cloneCount > 1){
                        $("#article_row").empty();
                        cloneCount=1;
                    // }
                    
                    if(result.length > 0 ){
                        for (let i = 0; i < result.length; i++) {
                            let article=result[i].article_code;
                            let articleCode=result[i].article_alternative_code;
                            let articleDesc=result[i].article_desc;
                            let qtyPo=result[i].qty_order;
                            let qty=qtyPo <= 0 ? 0 :'';
                            let uomGroup=result[i].uom_group;
                            let uom=result[i].uom;
                            let price=result[i].price;
                            let poPrNumber=result[i].pr_number;
                            add_new_row(article,articleCode,articleDesc,qtyPo,uomGroup,uom,price,qty,poPrNumber);
                        }
                    }

                    
                    
                },
                error: function (response) {
                    Swal.fire("Warning","Get detail PO failed","warning");
                }
            })
        }else{
            dariEdit='false';
        }
    }

    function listUom(obj,obj2,value,uom) {
      $.ajax({
        url:"{{ route('receiving.list.uom') }}",
        method:"GET",
        data:{
            value:value,
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj2).html(result);
            $('#'+obj).select2();
            $('#'+obj2).select2();
            $('#'+obj).val(uom).trigger('change');
            $('#'+obj2).val(uom).trigger('change');
        },
        error: function (response) {
            Swal.fire("Warning","Get list UOM failed","warning");
        }
      })
    }

    function uomChange(){
        // split article with delimiter
        let objUom= $('#article_row select[name="uom[]"]');
        let objUomFree= $('#article_row select[name="uomFree[]"]');
        let objQty= $('#article_row input[name="qty_rec[]"]');
        let objQtyFree= $('#article_row input[name="qty_free[]"]');

        objUom.change(function(e){   
            // let objIndex = objUom.index(this);
            // let uomGroup = objUom.eq(objIndex).find(":selected").data("uom-group");

            // if ( uomGroup === 'PIECE' ){
            //     objQty.eq(objIndex).removeClass("numeral-mask-digit");
            //     objQty.eq(objIndex).addClass("numeral-mask-satuan");
            //     mask_thousand_satuan();
            // }else{
            //     objQty.eq(objIndex).removeClass("numeral-mask-satuan");
            //     objQty.eq(objIndex).addClass("numeral-mask-digit");
            //     mask_thousand_digit(numberOfDecimalDigit);
            // }
            mask_thousand_digit(numberOfDecimalDigit);
		});

        objUomFree.change(function(e){   
            // let objIndex = objUomFree.index(this);
            // let uomGroup = objUomFree.eq(objIndex).find(":selected").data("uom-group");

            // if ( uomGroup === 'PIECE' ){
            //     objQtyFree.eq(objIndex).removeClass("numeral-mask-digit");
            //     objQtyFree.eq(objIndex).addClass("numeral-mask-satuan");
            //     mask_thousand_satuan();
            // }else{
            //     objQtyFree.eq(objIndex).removeClass("numeral-mask-satuan");
            //     objQtyFree.eq(objIndex).addClass("numeral-mask-digit");
            //     mask_thousand_digit(numberOfDecimalDigit);
            // }

            mask_thousand_digit(numberOfDecimalDigit);
		});
    }

    approve = (recNumber,objButton) => {
        $('#'+objButton).attr('disabled','disabled');
        $.ajax({
            type: "POST",
            url: "{{ route('receiving.approve') }}",
            data: {
                recNumber:recNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#recNumber').attr('disabled','disabled');
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#recNumber').attr('disabled','disabled');
                    $('#cmdApprove').attr('disabled','disabled');
                    window.location.reload();      
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

</script>
