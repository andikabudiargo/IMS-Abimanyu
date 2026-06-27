<style>    
    #article_row .form-group {
        margin-bottom: 0.5rem;
    }

    @media screen 
    and (min-device-width: 1200px) 
    and (max-device-width: 1600px) 
    and (-webkit-min-device-pixel-ratio: 1) { 
        .lebar-list-item{
            width:120%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }

    @media only screen and (min-width: 600px)
    and (max-width: 1200px)
    {
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

<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris barisDetail">
        <div class="form-row d-flex align-items-center">
            <div class="col-md-1 col-12" style="padding-right:1px">
                <div class="form-group">
                    <label for="aTone" class="d-block d-md-none">Tone</label>
                    <select class="form-control" id="aTone" name="aTone[]">
                        <option value=""></option>
                        <option value="t1">Tone 1</option>
                        <option value="t2">Tone 2</option>
                        <option value="t3">Tone 3</option>
                        <option value="t4">Tone 4</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-12" style="max-width:12.33333%;padding-right:1px">
                <div class="form-group">
                    <label for="pos" class="d-block d-md-none">POS</label>
                    <select class="form-control" id="pos" name="pos[]"></select>
                </div>
            </div>
            <div class="col-md-4 col-12" style="padding-right:1px">
                <div class="form-group">
                    <label for="article_id" class="d-block d-md-none">Article Code</label>
                    <select class="dynamicSelect form-control" id="article_id" name="article_id[]" data-dependent="article_id"></select>
                </div>
            </div>
            <div class="col-md-1 col-12" style="padding-right:1px">
                <div class="form-group">
                    <label for="qtyBom" class="d-block d-md-none">QTY</label>
                    <input type="text" class="form-control numeral-mask-digit text-right tombol-panah" id="qtyBom" name="qtyBom[]" maxlength="10" />
                </div>
            </div>
            <div class="col-md-1 col-12" style="padding-right:1px">
                <div class="form-group">
                    <label for="uom" class="d-block d-md-none">Uom</label>
                    <select class="form-control" id="uom" name="uom[]"></select>
                </div>
            </div>
            <div class="col-md-1 col-12" style="padding-right:1px">
                <div class="form-group">
                    <label for="uomCon" class="d-block d-md-none">Uom Con.</label>
                    <select class="form-control" id="uomCon" name="uomCon[]"></select>
                </div>
            </div>
            <div class="col-md-1 col-12" style="padding-right:1px">
                <div class="form-group">
                    <label for="qtyCon" class="d-block d-md-none">QTY Con.</label>
                    <input type="text" class="form-control numeral-mask-digit text-right tombol-panah" id="qtyCon" name="qtyCon[]" maxlength="10" disabled/>
                </div>
            </div>
            <div class="col-md-1 col-12" style="padding-right:1px">
                <div class="form-group" style="padding-left:5px">
                    <label for="brand" class="d-block d-md-none">Brand</label>
                    <span class="" id="brand" name="brand[]" style="font-size:10px"></span>
                </div>
            </div>
            <div class="col-md-1 col-12" style="max-width: 4.33333%;padding-right:1px">
                <div class="form-group text-center">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();">
                        <i data-feather="trash-2" class="remove_button feather-24"></i>
                    </a>
                </div>
            </div>
        </div>
        <hr class="d-block d-md-none" />
    </div>
</div>

<script type="text/javascript">
    let bomPosOption = "";

    $(document).ready(function () {
        let bomPos = {!! $posts !!};

        validateFormToast("frmAdd");
        mask_thousand_digit(numberOfDecimalDigit);

        bomPosOption = `<option value=""></option>`;
        for (let i = 0; i < bomPos.length; i++) {
            bomPosOption += `<option value="${bomPos[i].pos_code}">${bomPos[i].pos_name}</option>`;
        }

        // init select2 untuk single mode RM
        if ($('#articleCodeRm').length) {
            $('#articleCodeRm').select2({
                placeholder: "Choose Raw Material",
                allowClear: true
            });

            // update UOM saat RM single mode dipilih
            $('#articleCodeRm').on('change', function () {
                let detailStr = $(this).find(":selected").data("detail");
                let detail = detailStr ? detailStr.split("|") : [];
                $('#uomRm').val(detail[3] || '');
            });
        }
    });

    let cloneCount = 0;

    // ====================== ADD ROW (NEW) ======================
    add_new_row = () => {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row' + cloneCount);
        $("#new_row" + cloneCount).find('#article_id').attr('id', 'article_id' + cloneCount);
        $("#new_row" + cloneCount).find('#aTone').attr('id', 'aTone' + cloneCount);
        $("#new_row" + cloneCount).find('#pos').attr('id', 'pos' + cloneCount);
        fillPos('pos' + cloneCount);
        $("#new_row" + cloneCount).find('#qtyBom').attr('id', 'qtyBom' + cloneCount);
        $("#new_row" + cloneCount).find('#uom').attr('id', 'uom' + cloneCount);
        $("#new_row" + cloneCount).find('#uomCon').attr('id', 'uomCon' + cloneCount);
        changeselect('article_bom', 'article_id' + cloneCount);
        $("#article_id" + cloneCount).select2();
        $("#uom" + cloneCount).select2();
        $("#uomCon" + cloneCount).select2();
        $("#pos" + cloneCount).select2();
        $("#aTone" + cloneCount).select2();
        $('#remove_button').tooltip();
        splitArticle('new');
        hitungTotal();
        mask_thousand_digit(numberOfDecimalDigit);
    };

    // ====================== ADD ROW (EDIT) ======================
    add_new_row_edit = (article, qty, uom, uomCon, typeName, uomMember, uoms, factor, pos, tone, brand) => {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row' + cloneCount);

        const $newRow = $("#new_row" + cloneCount);

        ['article_id', 'pos', 'aTone', 'qtyBom', 'brand', 'type', 'qtyCon', 'uom', 'uomCon'].forEach(id => {
            $newRow.find('#' + id).attr('id', id + cloneCount);
        });

        changeselect('article_bom', 'article_id' + cloneCount, article);
        $("#brand" + cloneCount).text(brand);
        $("#qtyBom" + cloneCount).val(qty);
        $("#type" + cloneCount).text(typeName);
        $("#qtyCon" + cloneCount).val(parseFloat(qty) * parseFloat(factor));

        ['article_id', 'pos', 'aTone', 'uom', 'uomCon'].forEach(id => {
            $("#" + id + cloneCount).select2();
        });

        fillPos('pos' + cloneCount);

        $("#uom" + cloneCount).html(createUomOptions(uoms, uom)).val(uom).trigger('change');
        $("#uomCon" + cloneCount).html(createUomConOptions(uomMember, uomCon)).val(uomCon).trigger('change');
        $("#pos" + cloneCount).val(pos).trigger('change');
        $("#aTone" + cloneCount).val(tone).trigger('change');

        $('#remove_button').tooltip();
        hitungTotal();
        mask_thousand_digit(numberOfDecimalDigit);
    };

    splitArticle = () => {
        let objArticle  = $('#article_row select[name="article_id[]"]');
        let objBrand    = $('#article_row span[name="brand[]"]');
        let objQty      = $('#article_row input[name="qtyBom[]"]');
        let objUom      = $('#article_row select[name="uom[]"]');
        let objUomCon   = $('#article_row select[name="uomCon[]"]');

        objArticle.change(function (e) {
            let objIndex = objArticle.index(this);
            let article  = objArticle.eq(objIndex).val();
            let detail   = "";
            if (article) {
                detail = objArticle.eq(objIndex).find(":selected").data("detail");
            }
            let arrDetail = detail.split("|");

            let idUom = objUom.eq(objIndex).attr('id');
            $("#" + idUom).html(`<option>${arrDetail[1]}</option>`).val(arrDetail[1]).trigger('change');

            let brand = objArticle.eq(objIndex).find(":selected").data("brand");
            objBrand.eq(objIndex).text(brand);

            let uomMember = objArticle.eq(objIndex).find(":selected").data("uom-member");
            let uomOption = "";
            if (uomMember) {
                uomMember.split(',').forEach(val => {
                    let uomDet = val.split(';');
                    uomOption += `<option data-factor="${uomDet[1]}">${uomDet[0]}</option>`;
                });
            } else if (arrDetail[1]) {
                uomOption += `<option data-factor='1'>${arrDetail[1]}</option>`;
            }

            let idObjUomCon = objUomCon.eq(objIndex).attr('id');
            $("#" + idObjUomCon).select2();
            objUomCon.eq(objIndex).html(uomOption);
            objUomCon.eq(objIndex).val(arrDetail[1]).trigger('change');


            if (detail) {
                setTimeout(() => { objQty.eq(objIndex).focus().select(); }, 5);
            }
        });
    }  // ← INI YANG KURANG

    // ====================== HELPER ======================
    listUom = (obj, value, uom) => {
        $.ajax({
            url: "{{ route('receiving.list.uom') }}",
            method: "GET",
            data: { value: value },
            success: function (result) {
                $('#' + obj).html(result);
                $('#' + obj).select2();
                $('#' + obj).val(uom).trigger('change');
            },
            error: function () {
                Swal.fire("Warning", "Get list UOM failed", "warning");
            }
        });
    }

    changeselect = (dependent, obj, article) => {
    $('#' + obj).attr('disabled', 'disabled');
    $.ajax({
        url: "{{route('dynamic.dependent')}}",
        method: "POST",
        data: { dependent: dependent },
        success: function (result) {
            $('#' + obj).html(result);
            $('#' + obj).val(article).trigger('change');
            $('#' + obj).removeAttr('disabled');
            $('#' + obj).select2();  // ← pindah ke sini
        }
    });
}

    fillPos = (obj) => {
        $('#' + obj).append(bomPosOption);
    }

    function createUomOptions(uoms, defaultUom) {
        if (uoms) return uoms.split(',').map(val => `<option>${val}</option>`).join('');
        return defaultUom ? `<option>${defaultUom}</option>` : '';
    }

    function createUomConOptions(uomMember, defaultUomCon) {
    if (uomMember) {
        return uomMember.split(',').map(val => {
            const [uomName, factor] = val.split(';');
            return `<option data-factor="${factor}" value="${uomName}">${uomName}</option>`;
        }).join('');
    }
    return defaultUomCon ? `<option value="${defaultUomCon}">${defaultUomCon}</option>` : '';
}

    function hitungTotal() {
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objQty     = $('#article_row input[name="qtyBom[]"]');
        let objCon     = $('#article_row input[name="qtyCon[]"]');
        let objUomCon  = $('#article_row select[name="uomCon[]"]');

        objQty.keyup(function () {
            let i = objQty.index(this);
            if (objArticle.eq(i).val()) {
                let factor = objUomCon.eq(i).find(":selected").data("factor") || 1;
                let qty    = objQty.eq(i).val().replace(/,/gi, '') || 0;
                objCon.eq(i).val(parseFloat(qty) * parseFloat(factor));
                mask_thousand_digit(numberOfDecimalDigit);
            }
        });

        objUomCon.change(function () {
            let i = objUomCon.index(this);
            if (objArticle.eq(i).val()) {
                let factor = objUomCon.eq(i).find(":selected").data("factor") || 1;
                let qty    = objQty.eq(i).val() || 0;
                objCon.eq(i).val(parseFloat(qty) * parseFloat(factor));
                mask_thousand_digit(numberOfDecimalDigit);
            }
        });
    }

    function allSelectsAreFilledjQuery(obj) {
        let allFilled = true;
        $(obj).each(function () {
            if (!$(this).val()) { allFilled = false; return false; }
        });
        return allFilled;
    }

    function removeAllChildDivs(objId) {
        const el = document.getElementById(objId);
        if (el) el.innerHTML = "";
    }

    // ====================== SAVE DATA ======================
    saveData = (oEdit) => {
        if (!$("#frmAdd")[0].checkValidity()) {
            $("#frmAdd").submit();
            return;
        }

        $('.disabled-el').removeAttr('disabled');

        let objArticle   = $("#article_row select[name='article_id[]']");
        let objQty       = $('#article_row input[name="qtyBom[]"]');
        let objUom       = $('#article_row select[name="uom[]"]');
        let objUomCon    = $('#article_row select[name="uomCon[]"]');
        let objPos       = $('#article_row select[name="pos[]"]');
        let objAtone     = $('#article_row select[name="aTone[]"]');
        let objSprayBooth = $('#article_row_sb select[name="sprayBooth[]"]');
        let objTone      = $('#article_row_sb select[name="tone[]"]');
        let objTack      = $('#article_row_sb input[name="tack[]"]');
        let objPassRate  = $('#article_row_sb input[name="passRate[]"]');
        let objPassThru  = $('#article_row_sb input[name="passThru[]"]');
        let objCycleTime = $('#article_row_sb input[name="cycleTime[]"]');

        // ====================== AMBIL DATA HEADER ======================
        let articleCode, uomHdr, customer, group;
        if (oEdit) {
            articleCode = $('#articleCode').data('article-code');
            uomHdr      = $('#uomHdr').val();
            customer    = $('#customer').data('customer-code');
            group       = $('#group').data('group');
        } else {
            articleCode  = $('#articleCode').val();
            let detail1  = $('#articleCode').find(":selected").data("detail").split("|");
            uomHdr       = detail1[1];
            customer     = detail1[4];
            group        = detail1[5];
        }

        // ====================== AMBIL RM LIST ======================
        let articleCodeRmList = (typeof getArticleCodeRmList === 'function')
            ? getArticleCodeRmList()
            : [];

        let partNo  = $('#partNo').val();
        let model   = $('#model').val();  // fix: bukan #partModel
        let note    = $('#note').val();
        let arrArticles  = [];
        let sprayBooths  = [];
        let flag = 0;
        let pesan = "";
        let urutan = 1;
        let urutanSb = 1;

        // ====================== KUMPULKAN ARTICLES ======================
        objArticle.map(function (i) {
            let $this = $(this);
            if ($this.val()) {
                let plu     = $this.val();
                let uom     = objUom.eq(i).val();
                let uomCon  = objUomCon.eq(i).val();
                let detail  = $this.find(":selected").data("detail").split("|");
                let type    = detail[3];
                let qty     = objQty.eq(i).val().replace(/,/gi, '') || 0;
                let pos     = objPos.eq(i).val();
                let aTone   = objAtone.eq(i).val();

                if (qty == 0) {
                    pesan += "QTY of items " + $this.select2('data')[0].text + " cannot be 0 <br>";
                    flag = 1;
                }

                if (plu !== '' && qty > 0) {
                    arrArticles.push({
                        "urutan":       urutan++,
                        "article_code": plu,
                        "qty":          parseFloat(qty),
                        "uom":          uom,
                        "uom_con":      uomCon,
                        "customer_code":customer,
                        "type":         type,
                        "pos":          pos,
                        "tone":         aTone
                    });
                }
            }
        });

        // ====================== KUMPULKAN SPRAY BOOTH ======================
        objSprayBooth.map(function (i) {
            let $this = $(this);
            if ($this.val()) {
                let sprayBooth = $this.val();
                let tone       = objTone.eq(i).val();
                let tack       = objTack.eq(i).val().replace(/,/gi, '') || 0;
                let passRate   = objPassRate.eq(i).val().replace(/,/gi, '') || 0;
                let passThru   = objPassThru.eq(i).val().replace(/,/gi, '') || 0;
                let cycleTime  = objCycleTime.eq(i).val().replace(/,/gi, '') || 0;

                let duplicate = sprayBooths.find(o => o.spray_booth + o.tone === sprayBooth + tone);
                if (duplicate) {
                    pesan += "Spray booth " + sprayBooth.toUpperCase() + " and Tone " + tone + " entered more than once !! <br>";
                    flag = 1;
                } else if (sprayBooth !== '') {
                    sprayBooths.push({
                        "urutan":     urutanSb++,
                        "spray_booth":sprayBooth,
                        "tone":       tone,
                        "tack":       tack,
                        "pass_rate":  passRate,
                        "pass_thru":  passThru,
                        "cycle_time": cycleTime
                    });
                }
            }
        });

        // ====================== VALIDASI ======================
        if (!customer) {
            pesan += "Customer must be filled in <br>";
            flag = 1;
        }
        if (objSprayBooth.length === 0) {
            pesan += "Spray booth must be filled in completely <br>";
            flag = 1;
        }
        if (arrArticles.length === 0) {
            pesan += "Articles must be filled in completely <br>";
            flag = 1;
        }
        if (articleCodeRmList.length === 0) {
            pesan += "Article Raw material must be filled in <br>";
            flag = 1;
        }

        let supplierToto = 'false';

        if (flag !== 0) {
            Swal.fire('Warning..', pesan, 'warning');
            return;
        }

        // ====================== AJAX ======================
        let bomNumber = "";
        let url = "";
        if (oEdit) {
            bomNumber = $('#bomNumber').val();
            url = "{{ route('bom.update') }}";
        } else {
            url = "{{ route('bom.storev2') }}";
        }

        $.ajax({
            type: "POST",
            url: url,
            data: {
                articles:       JSON.stringify(arrArticles),
                sprayBooths:    JSON.stringify(sprayBooths),
                articleCode:    articleCode,
                articleCodeRm:  JSON.stringify(articleCodeRmList),
                customer:       customer,
                note:           note,
                group:          group,
                uom:            uomHdr,
                bomNumber:      bomNumber,
                partNo:         partNo,
                model:          model,
                supplierToto:   supplierToto
            },
            dataType: "json",
            success: function (data) {
                if (data.status == 0) {
                    for (let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#bomNumber').attr('disabled', 'disabled');
                } else {
                    show_msg(data.title, data.message, data.alert);
                    $('#bomNumber,#customer,#group,#uomHdr,#articleCode,#articleCodeRm').attr('disabled', 'disabled');
                    $('#bomNumber').val(data.bomNumber);
                    $('#oEdit').val(data.oEdit);
                }
            },
            error: function (error) {
                console.log(error);
            }
        });
    }

    // ====================== APPROVE ======================
    approve = (bomNumber) => {
        $.ajax({
            type: "GET",
            url: "{{ route('bom.approve') }}",
            data: { bomNumber: bomNumber },
            dataType: "json",
            success: function (data) {
                if (data.status == 0) {
                    for (let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                } else {
                    show_msg(data.title, data.message, data.alert);
                    $('#bomNumber').attr('disabled', 'disabled');
                    $('#cmdApprove').attr('disabled', 'disabled');
                    $('#addNewRow').attr('disabled', 'disabled');
                    window.location.reload();
                }
            },
            error: function (error) { console.log(error); }
        });
    }

    $("#cmdCancel,#cmdNew").click(function () {
        $('#bomNumber').val('');
        window.location.reload();
    });

</script>