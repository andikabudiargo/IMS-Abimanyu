<style>
</style>
{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris">
        <div class="form-row">
            <div class="col-md-4 col-12">
                <div class="form-group margin-nol">
                    <label for="article_id" class="d-block d-md-none">Article Code</label>
                    <select class="dynamicSelect form-control sku-select-system" id="article_id" name="article_id[]" data-dependent="article_id">
                    </select>
                    <small class="text-muted" ><span id = "group" name="group[]"></span></small></p>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qty_stock" class="d-block d-md-none">QTY Stock</label>
                    <input type="text" class="form-control text-right" id = "qty_stock" name="qty_stock[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qty_order" class="d-block d-md-none">QTY Order</label>
                    <div class="input-group">
                        <input type="text" class="form-control numeral-mask text-right" id = "qty_order" name="qty_order[]" maxlength="9" />
                        <div class="input-group-append">
                            <span class="input-group-text" id ="uom" name="uom[]"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="price" class="d-block d-md-none">Price</label>
                    <input type="text" class="form-control numeral-mask text-right" id= "price" name="price[]"  maxlength="11">
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="priceJasa" class="d-block d-md-none">Price Jasa</label>
                    <input type="text" class="form-control numeral-mask text-right" id = "priceJasa" name="priceJasa[]"  maxlength="11">
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="totalLine" class="d-block d-md-none">T.Material</label>
                    <input type="text" class="form-control numeral-mask text-right" id="totalLine" name="totalLine[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="totalJasa" class="d-block d-md-none">T.Service</label>
                    <input type="text" class="form-control numeral-mask text-right" id="totalJasa" name="totalJasa[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="totalAll" class="d-block d-md-none">Total</label>
                    <input type="text" class="form-control numeral-mask text-right" id="totalAll" name="totalAll[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();">
                        <i data-feather="trash-2" class="remove_button feather-24">
                        </i>
                    </a>
                </div>
            </div>
        </div>
        <hr class="d-block d-md-none" />
    </div>
</div>
{{-- \.table row --}} 
<style>
    .margin-nol{
        margin-bottom:0.5rem;
    }
</style>

<script type="text/javascript">
       
    let cloneCount = {{ isset($detail) ? count($detail) :1 }};
    
    $('#cust').on('change', function() {
        let cust = $(this).val().split("|");
        let customer = cust[0];
    })

    $('#ppn').on('keyup', function() {
        hitungGrandTotal();
    });
    
    function reloadPage(){
        window.location.reload();
    }

    $("#cmdCancel,#cmdNew").click(function(){
        reloadPage();
    });
    
    function add_new_row() {
        let customer = $('#cust');
        let cust = customer.val().split("|");
        if (customer.val()){            
            $("#article_row").append($("#new_row").clone().html());
            cloneCount++;
            $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
            $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
            changeselect('article_id','article_id'+ cloneCount,cust[0],'FG');
            $("#article_id"+cloneCount).select2();
            tombolPanah('qty_order');
            tombolPanah('price');
            tombolPanah('priceJasa');
            activate_angka();
            mask_thousand();
            splitArticle();
            hitungTotal();
            hitungGrandTotal();
        }else{
            Swal.fire({
                title: 'Warning',
                text: "Choose customer",
                icon: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    customer.select2('open');
                }
            })
        }
    };

    function splitArticle(){
        // split article with delimiter |
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objGroup= $('#article_row span[name="group[]"]');
        let objStock= $('#article_row input[name="qty_stock[]"]');
        let objUom= $('#article_row span[name="uom[]"]'); 
        let objQty= $('#article_row input[name="qty_order[]"]');
        objArticle.change(function(e){        
            let objIndex = objArticle.index(this);
            let detail = objArticle.eq(objIndex).val();
            let arrDetail = detail.split("|");
            objGroup.eq(objIndex).text(arrDetail[1]);
            objStock.eq(objIndex).val(arrDetail[2]||0);
            objUom.eq(objIndex).text(arrDetail[3]);
            objArticle.eq(objIndex).select2('open'); //belum bisa jalan
            if (detail){
                setTimeout(() => {
                    objQty.eq(objIndex).focus().select();
                }, 5);
            }
		});
    }

    function hitungTotal(){
        let objQty= $('#article_row input[name="qty_order[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let objTotal= $('#article_row input[name="totalLine[]"]');
        let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
        let objTotalJasa= $('#article_row input[name="totalJasa[]"]');
        let objTotalAll= $('#article_row input[name="totalAll[]"]');
        
        objQty.keyup(function() {
            let indexnya= objQty.index(this);
            let qty = objQty.eq(indexnya).val().replace(/[^0-9]/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/[^0-9]/gi, '') ||0;
            let priceJasa = objPriceJasa.eq(indexnya).val().replace(/[^0-9]/gi, '') ||0;
            let total = qty*price;
            let totalJasa = qty*priceJasa;
            objTotal.eq(indexnya).val(humanizeNumber(total));
            objTotalJasa.eq(indexnya).val(humanizeNumber(totalJasa));
            objTotalAll.eq(indexnya).val(humanizeNumber(totalJasa+total));
            hitungGrandTotal();
        });    

        objPrice.keyup(function() {
            let indexnya= objPrice.index(this);
            let qty = objQty.eq(indexnya).val().replace(/[^0-9]/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/[^0-9]/gi, '')||0;
            let total = qty*price;
            let priceJasa = objPriceJasa.eq(indexnya).val().replace(/[^0-9]/gi, '')||0;
            let totalJasa = qty*priceJasa;
            objTotal.eq(indexnya).val(humanizeNumber(total));
            objTotalJasa.eq(indexnya).val(humanizeNumber(totalJasa));
            objTotalAll.eq(indexnya).val(humanizeNumber(totalJasa+total));
            hitungGrandTotal();
        });    

        objPriceJasa.keyup(function() {
            let indexnya= objPrice.index(this);
            let qty = objQty.eq(indexnya).val().replace(/[^0-9]/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/[^0-9]/gi, '')||0;
            let total = qty*price;
            let priceJasa = objPriceJasa.eq(indexnya).val().replace(/[^0-9]/gi, '')||0;
            let totalJasa = qty*priceJasa;
            objTotal.eq(indexnya).val(humanizeNumber(total));
            objTotalJasa.eq(indexnya).val(humanizeNumber(totalJasa));
            objTotalAll.eq(indexnya).val(humanizeNumber(totalJasa+total));
            hitungGrandTotal();
        });    
    }

    function hitungGrandTotal(){
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objQtyTiw= $('#article_row input[name="qty_order[]"]');
        let objQTY= $('#article_row input[name="qty_order[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
        let ppn= $('#ppn').val() ||0;
        let pph23= $('#pph23').val() ||0;
        let totalQty= 0;
        let totalAmount=0
        let totalAmountJasa=0
        let totalAmountMaterial=0

        var arr = objQtyTiw.map(function (i) {
            let qty = parseInt(objQTY.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            let price = parseInt(objPrice.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            let priceJasa = parseInt(objPriceJasa.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            totalQty+= qty;
            totalAmount+= (qty*price)+(qty*priceJasa);
            totalAmountMaterial+= (qty*price)+(qty*priceJasa);
            totalAmountJasa+= (qty*priceJasa);
        }).get();
        
        $("#totalRow").val(objArticle.length);
        $("#nilaiPPN").text(ppn+"%");
        $("#nilaiPPH23").text(pph23+"%");
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#totalAmount").val(humanizeNumber(totalAmount));
        $("#totalPPN").val(humanizeNumber((parseInt(ppn)*totalAmountMaterial)/100));
        $("#totalPPH").val(humanizeNumber((pph23*totalAmountJasa)/100));
        $("#totalNetto").val(humanizeNumber(totalAmount+((parseInt(ppn)*totalAmount)/100)-((pph23*totalAmountJasa)/100)));
    
    }

    function changeselect(dependent,obj,value,type) {
      $.ajax({
        url:"{{ route('dynamic.dependent') }}",
        method:"POST",
        data:{
            value:value,
            type:type,
            dependent:dependent
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val('').trigger('change');
        }
      })
    }

    function tombolPanah(objname){
        let obj = $('#article_row input[name="'+objname+'[]"]');
        obj.keyup(function(e) {
            indexnya = obj.index(this);
            indexnya = parseInt(indexnya);
            if (e.keyCode == 38) {
                //panah atas
                indexTarget = indexnya-1;
                obj.eq(indexTarget).focus().select();
                return false;
            }
            if (e.keyCode == 40) {
                //panah bawah
                indexTarget = indexnya+1;
                obj.eq(indexTarget).focus().select();
                return false;
            }
        });
    }
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>