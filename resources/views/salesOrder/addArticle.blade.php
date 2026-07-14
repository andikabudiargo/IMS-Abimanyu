<style>
</style>
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris">
        <div class="form-row">
            <div class="col-md-4 col-12">
                <div class="form-group margin-nol">
                    <label for="article_id" class="d-block d-md-none jumlahArticle">Article Code</label>
                    <select class="dynamicSelect form-control " id="article_id" name="article_id[]" data-dependent="article_id">
                    </select>
                    {{-- <small class="text-muted" ><span id = "group" name="group[]"></span></small></p> --}}
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
                        <input type="text" class="form-control numeral-mask-digit text-right" id = "qty_order" oninput='inputDecimal(this)' name="qty_order[]" maxlength="9" />
                        <div class="input-group-append">
                            <span class="input-group-text padding-nol" id ="uom" name="uom[]"></span>
                        </div>
                    </div>
                </div>
            </div>
           {{-- Ganti bagian col price yang lama --}}
<div class="col-md-1 col-12">
    <div class="form-group margin-nol">
        <label for="price" class="d-block d-md-none">Price</label>
        <div class="input-group input-group-merge">
            <input type="text" class="form-control numeral-mask-digit text-right" id="price" name="price[]" oninput='inputDecimal(this)' maxlength="14">
            <div class="input-group-append">
                <span class="input-group-text cursor-pointer" style="padding:0 4px;">
                    <a onmouseover="this.style.cursor='pointer'" id="listPriceMaterial" name="listPriceMaterial[]" data-toggle="tooltip" data-placement="right" title="History Price Material">
                        <i data-feather="info" class="feather-24"></i>
                    </a>
                </span>
            </div>
        </div>
    </div>
</div>
<div class="col-md-1 col-12">
    <div class="form-group margin-nol">
        <label for="priceJasa" class="d-block d-md-none">Price Jasa</label>
        <input type="text" class="form-control numeral-mask-digit text-right" id="priceJasa" name="priceJasa[]" oninput='inputDecimal(this)' maxlength="14">
    </div>
</div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="totalLine" class="d-block d-md-none">T.Material</label>
                    <input type="text" class="form-control numeral-mask-digit text-right" id="totalLine" name="totalLine[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="totalJasa" class="d-block d-md-none">T.Service</label>
                    <input type="text" class="form-control numeral-mask-digit text-right" id="totalJasa" name="totalJasa[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="totalAll" class="d-block d-md-none">Total</label>
                    <input type="text" class="form-control numeral-mask-digit text-right" id="totalAll" name="totalAll[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal()">
                        <i data-feather="trash-2" class="remove_button feather-24">
                        </i>
                    </a>
                </div>
            </div>
        </div>
        <hr class="d-block d-md-none" />
    </div>
</div>
<style>
    .margin-nol{
        margin-bottom:0.5rem;
    }
    .padding-nol{
        padding:0px 4px 0px 4px; 
    }
</style>

<script type="text/javascript">
    let sNilaiPpnPembilang= "{{ $ppnPembilang }}";
    let sNilaiPpnPenyebut= "{{ $ppnPenyebut }}";
    let cloneCount = {{ isset($detail) ? count($detail) :1 }};
    let statusSo  = '';

    let delayTimer;
    function inputDecimal(ele) {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(function() {
            let nilai = ele.value.replace(/,/gi, '') || 0;;
            ele.value = humanizeNumber(parseFloat(nilai).toFixed(2)).toString();
        }, 1100); 
    }
    
    $('#cust').on('change', function() {
        let cust = $(this).val().split("|");
        let customer = cust[0];
    })

    $('#ppn,#pph23').on('keyup', function() {
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
    if (customer.val()) {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row' + cloneCount);

        $("#new_row" + cloneCount).find('#article_id').attr('id', 'article_id' + cloneCount);
        $("#new_row" + cloneCount).find('#price').attr('id', 'price' + cloneCount);
        $("#new_row" + cloneCount).find('#priceJasa').attr('id', 'priceJasa' + cloneCount);
        $("#new_row" + cloneCount).find('#listPriceMaterial').attr('id', 'listPriceMaterial' + cloneCount);
        // listPriceService dihapus, tidak perlu di-rename lagi
        changeselect('article_id','article_id'+ cloneCount,cust[0],'FG');
        var $art = $("#article_id"+cloneCount);
        $art.wrap('<div class="position-relative"></div>');
        $art.select2({
            dropdownAutoWidth: true,
            width: '100%',
            dropdownParent: $art.parent()
        });
        tombolPanah('qty_order');
        tombolPanah('price');
        tombolPanah('priceJasa');
        activate_angka();
        mask_thousand();
        mask_thousand_digit(2);
        splitArticle();
        hitungTotal();
        hitungGrandTotal();
    } else {
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

  function splitArticle() {
    let objArticle = $('#article_row select[name="article_id[]"]');
    let objGroup = $('#article_row span[name="group[]"]');
    let objStock = $('#article_row input[name="qty_stock[]"]');
    let objUom = $('#article_row span[name="uom[]"]');
    let objQty = $('#article_row input[name="qty_order[]"]');

    objArticle.change(function(e) {
        let objIndex = objArticle.index(this);
        let detail = objArticle.eq(objIndex).val();
        let arrDetail = detail.split("|");
        let articleCode = arrDetail[0];
        let articleName = objArticle.eq(objIndex).select2('data')[0].text;

        objGroup.eq(objIndex).text(arrDetail[1]);
        objStock.eq(objIndex).val(arrDetail[2] * 1 || 0);
        objUom.eq(objIndex).text(arrDetail[3]);

        // Hanya satu tombol info, di kolom Price Material
        let btnMaterial = $('#article_row a[name="listPriceMaterial[]"]').eq(objIndex);
        btnMaterial.attr("onclick", `listPriceSo('${articleCode}','${articleName}',${objIndex});`);

        if (detail) {
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
            hitungTotalPerBaris(indexnya);
            // let qty = objQty.eq(indexnya).val().replace(/,/gi, '') || 0; 
            // let price = objPrice.eq(indexnya).val().replace(/,/gi, '') ||0;
            // let priceJasa = objPriceJasa.eq(indexnya).val().replace(/,/gi, '') ||0;
            // let total = qty*price;
            // let totalJasa = qty*priceJasa;
            // objTotal.eq(indexnya).val(humanizeNumber(parseFloat(total).toFixed(2)));
            // objTotalJasa.eq(indexnya).val(humanizeNumber(parseFloat(totalJasa).toFixed(2)));
            // objTotalAll.eq(indexnya).val(humanizeNumber(parseFloat((totalJasa+total)).toFixed(2)));
            // hitungGrandTotal();
        });    

        objPrice.keyup(function() {
            let indexnya= objPrice.index(this);
            hitungTotalPerBaris(indexnya);
            // let qty = objQty.eq(indexnya).val().replace(/,/gi, '') || 0; 
            // let price = objPrice.eq(indexnya).val().replace(/,/gi, '')||0;
            // let total = qty*price;
            // let priceJasa = objPriceJasa.eq(indexnya).val().replace(/,/gi, '')||0;
            // let totalJasa = qty*priceJasa;
            // objTotal.eq(indexnya).val(humanizeNumber(parseFloat(total).toFixed(2)));
            // objTotalJasa.eq(indexnya).val(humanizeNumber(parseFloat(totalJasa).toFixed(2)));
            // objTotalAll.eq(indexnya).val(humanizeNumber(parseFloat((totalJasa+total)).toFixed(2)));
            // hitungGrandTotal();
        });    

        objPriceJasa.keyup(function() {
            let indexnya= objPrice.index(this);
            hitungTotalPerBaris(indexnya);
            // let qty = objQty.eq(indexnya).val().replace(/,/gi, '') || 0; 
            // let price = objPrice.eq(indexnya).val().replace(/,/gi, '')||0;
            // let total = qty*price;
            // let priceJasa = objPriceJasa.eq(indexnya).val().replace(/,/gi, '')||0;
            // let totalJasa = qty*priceJasa;
            // objTotal.eq(indexnya).val(humanizeNumber(parseFloat(total).toFixed(2)));
            // objTotalJasa.eq(indexnya).val(humanizeNumber(parseFloat(totalJasa).toFixed(2)));
            // objTotalAll.eq(indexnya).val(humanizeNumber(parseFloat((totalJasa+total)).toFixed(2)));
            // hitungGrandTotal();
        });    
    }

    function listPriceSo(article, desc, rowIndex) {
    Swal.fire({
        html: '<div id="swalPriceRoot">'
            + '<div id="swalPriceBody" style="min-height:120px;text-align:center;padding:20px;">'
            + '<div class="spinner-border text-primary" role="status"></div>'
            + '<p class="mt-2 mb-0 text-muted">Memuat data harga...</p></div></div>',
        width: 780,
        padding: 0,
        showConfirmButton: false,
        showCloseButton: true,
        customClass: { popup: 'price-history-popup' },
        didOpen: function() {
            $.ajax({
                dataType: 'json',
                type: 'GET',
                url: "{{ route('salesOrder.price.list') }}",
                data: { article: article },
                success: function(data) {
                    let header = `
                        <div style="background:linear-gradient(135deg,#7367f0 0%,#9e95f5 100%);
                                    padding:18px 24px;text-align:left;width:100%;box-sizing:border-box;margin:0;">
                            <div style="color:#fff;font-size:1.05rem;font-weight:600;letter-spacing:.3px;">
                                <i data-feather="trending-up" style="width:18px;height:18px;vertical-align:-3px;margin-right:6px;"></i>
                                Price History
                            </div>
                            <div style="color:rgba(255,255,255,.85);font-size:.82rem;margin-top:2px;">${desc}</div>
                        </div>`;

                    if (data.length > 0) {
                        let rows = '';
                        for (let i = 0; i < data.length; i++) {
                            let pm = parseFloat(data[i].price || 0);
                            let ps = parseFloat(data[i].price_service || 0);
                            let tt = parseFloat(data[i].total || 0);

                            rows += `<tr>
                                <td class="text-center text-muted">${i + 1}</td>
                                <td><span class="badge badge-light-primary">${data[i].so_code}</span></td>
                                <td class="text-center">${data[i].so_date}</td>
                                <td class="text-center text-muted">${data[i].updated_at}</td>
                                <td class="text-right">${humanizeNumber(pm.toFixed(2))}</td>
                                <td class="text-right">${humanizeNumber(ps.toFixed(2))}</td>
                                <td class="text-right">
                                    <button class="btn btn-sm price-pick-btn"
                                        onclick="definePriceSo(${rowIndex}, ${pm}, ${ps})">
                                        ${humanizeNumber(tt.toFixed(2))}
                                    </button>
                                </td>
                            </tr>`;
                        }

                        let body = `
                            <div style="padding:16px 24px 24px;">
                                <div style="font-size:.78rem;color:#6e6b7b;margin-bottom:10px;text-align:left;">
                                    Klik nilai <b>Total</b> untuk mengisi Price Material dan Price Jasa pada baris ini.
                                </div>
                                <table class="table price-history-table mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;">#</th>
                                            <th>SO Number</th>
                                            <th class="text-center">SO Date</th>
                                            <th class="text-center">Updated</th>
                                            <th class="text-right">Price Material</th>
                                            <th class="text-right">Price Jasa</th>
                                            <th class="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>${rows}</tbody>
                                </table>
                            </div>`;
                        $('#swalPriceRoot').html(header + body);
                    } else {
                        $('#swalPriceRoot').html(header +
                            `<div style="padding:40px 24px;text-align:center;color:#b9b9c3;">
                                <i data-feather="inbox" style="width:38px;height:38px;"></i>
                                <p class="mt-2 mb-0">Tidak ada perubahan harga sebelumnya.</p>
                             </div>`);
                    }
                    if (window.feather) feather.replace();
                },
                error: function() {
                    $('#swalPriceBody').html('<p class="text-danger mb-0 py-3">Error mengambil data harga.</p>');
                }
            });
        }
    });
}

function definePriceSo(rowIndex, priceMaterial, priceJasa) {
    let objPrice     = $('#article_row input[name="price[]"]');
    let objPriceJasa = $('#article_row input[name="priceJasa[]"]');

    objPrice.eq(rowIndex).val(humanizeNumber(parseFloat(priceMaterial).toFixed(2)));
    objPriceJasa.eq(rowIndex).val(humanizeNumber(parseFloat(priceJasa).toFixed(2)));

    // Total, T.Material, T.Service dihitung ulang otomatis berdasarkan qty yang sedang diinput di baris ini
    hitungTotalPerBaris(rowIndex);

    Swal.close();
}

    hitungTotalPerBaris=(indexnya)=>{
        let objQty= $('#article_row input[name="qty_order[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let objTotal= $('#article_row input[name="totalLine[]"]');
        let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
        let objTotalJasa= $('#article_row input[name="totalJasa[]"]');
        let objTotalAll= $('#article_row input[name="totalAll[]"]');
        
        let qty = objQty.eq(indexnya).val().replace(/,/gi, '') || 0; 
        let price = objPrice.eq(indexnya).val().replace(/,/gi, '')||0;
        let total = qty*price;
        let priceJasa = objPriceJasa.eq(indexnya).val().replace(/,/gi, '')||0;
        let totalJasa = qty*priceJasa;
        objTotal.eq(indexnya).val(humanizeNumber(parseFloat(total).toFixed(2)));
        objTotalJasa.eq(indexnya).val(humanizeNumber(parseFloat(totalJasa).toFixed(2)));
        objTotalAll.eq(indexnya).val(humanizeNumber(parseFloat((totalJasa+total)).toFixed(2)));
        hitungGrandTotal();
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
        sNilaiPPN = ppn;

        let countOfArticle = objArticle.length;

        console.log(statusSo);

        if(statusSo == 'NEW'){
            if (countOfArticle > 0) {
                $('#cust').attr('disabled', 'disabled');
            }else{
                $('#cust').removeAttr('disabled');
            }
        }

        let arr = objQtyTiw.map(function (i) {
            let qty = parseFloat(objQTY.eq(i).val().replace(/,/gi, '')) || 0;
            let price = parseFloat(objPrice.eq(i).val().replace(/,/gi, '')) || 0;
            let priceJasa = parseFloat(objPriceJasa.eq(i).val().replace(/,/gi, '')) || 0;
            totalQty+= qty;
            totalAmount+= (qty*price)+(qty*priceJasa);
            totalAmountMaterial+= (qty*price)+(qty*priceJasa);
            totalAmountJasa+= (qty*priceJasa);
        }).get();

        $("#totalPPN").val(humanizeNumber(parseFloat(((parseInt(ppn)*totalAmountMaterial)/100)).toFixed(2)));

        if ($("#nilaiLainCheck").is(':checked')) {
            let zDppNilaiLain = totalAmountMaterial * (sNilaiPpnPembilang/sNilaiPpnPenyebut);
            $("#totalDppNilaiLain").val(humanizeNumber(parseFloat(zDppNilaiLain).toFixed(2)));
            let qTotalPpn = Math.round(zDppNilaiLain * (sNilaiPPN/100));
            $("#totalPPN").val(humanizeNumber(parseFloat(qTotalPpn).toFixed(2)));
        }else{
            $("#totalDppNilaiLain").val('');
        }

        let iTotalPpn = $("#totalPPN").val().replace(/,/gi, '') || 0;
        
        $("#totalRow").val(objArticle.length);
        $("#nilaiPPN").text(ppn+"%");
        $("#nilaiPPH23").text(pph23+"%");
        $("#totalQTY").val(humanizeNumber(parseFloat(totalQty).toFixed(2)));
        $("#totalAmount").val(humanizeNumber(parseFloat(totalAmount).toFixed(2)));
        $("#totalPPH").val(humanizeNumber(parseFloat(((pph23*totalAmountJasa)/100)).toFixed(2)));
        $("#totalNetto").val(humanizeNumber(parseFloat((totalAmount+(parseFloat(iTotalPpn))-((pph23*totalAmountJasa)/100))).toFixed(2)));
        mask_thousand_digit(2);

    }

    function changeselect(dependent,obj,value,type) {
    $.ajax({
        url:"{{ route('dynamic.dependent') }}",
        method:"POST",
        data:{ value:value, type:type, dependent:dependent },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val('').trigger('change');
        }
    })
}

    getActivePpn = (tanggal) => {
        return $.ajax({
            async: false,
            url:"{{route('setting.lastPpn')}}",
            method:"GET",
            data:{
                tanggal:tanggal,
            },
            success:function(result){
            }
        });
    }

    hitungNilaiLain = () =>{
        let aOrderDate = $('#orderDate').val();
        if(aOrderDate){
            getActivePpn(aOrderDate).done(function (result) {
                if(result){
                    sNilaiPPN = result.ppnValue;
                    sNilaiPpnPembilang = result.pembilang;
                    sNilaiPpnPenyebut = result.penyebut;
                    $("#ppn").val(sNilaiPPN);
                    $("#pembilangNumber").val(sNilaiPpnPembilang);
                    $("#penyebutNumber").val(sNilaiPpnPenyebut);
                }
            })
        }
        
        /*
            jika ada DPP nilai lain maka perhituangan DPP lain-lain
            rumus 11/12* 
            dan untuk PPN 12% nya dihitung dari DPP Nilai Lain * 12%
        */

        let totalAmount = parseFloat($('#totalAmount').val().replace(/,/gi, '')) || 0;
        let zDppNilaiLain = totalAmount * (sNilaiPpnPembilang/sNilaiPpnPenyebut);

        $("#totalDppNilaiLain").val(parseFloat(zDppNilaiLain).toFixed(2));
        $("#nilaiDppLain").text(`${sNilaiPpnPembilang}/${sNilaiPpnPenyebut}`);
        totalAmount = zDppNilaiLain;
        let zTotalPPn = Math.round(totalAmount * (sNilaiPPN/100));
        // console.log(`BA Tanpa pembulatan dari nilai lain:${totalAmount * (sNilaiPPN/100)}`);
        $("#totalPPN").val(parseFloat(zTotalPPn).toFixed(2)).trigger("input");
        $("#nilaiPPN").text(sNilaiPPN+'%');
        mask_thousand();
        mask_thousand_digit(2);
        hitungGrandTotal()
    }

    $("#nilaiLainCheck").change(function() {
        let aOrderDate = $('#orderDate').val();
        if (aOrderDate){
            if(this.checked) {
                hitungNilaiLain();
            }else{
                $("#totalDppNilaiLain").val('');
                $("#nilaiDppLain").text('');
                hitungGrandTotal()
            }
        }else{
            swal.fire('Warning',"Invoice date belum diisi !!",'warning');
            $("#nilaiLainCheck").prop('checked', false);
        }
    });

    let orderDateFp = $('#orderDate');
    if (orderDateFp.length) {
        orderDateFp.flatpickr({
            dateFormat: "d-m-Y"
        });
    }

    orderDateFp.change(function() {
        let aOrderDate = $('#orderDate').val();
        if (aOrderDate){
            $("#nilaiLainCheck").prop('checked',true).change();
        }
    });
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>