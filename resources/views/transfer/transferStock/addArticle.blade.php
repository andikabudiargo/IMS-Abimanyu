{{-- table row untuk di clone--}}
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
           <div class="col-md-3 col-12">
    <div class="form-group margin-nol">
        <label for="articleId" class="d-block d-md-none">Article</label>
        <select class="form-control" id="articleId" name="articleId[]" data-dependent="articleId"></select>
    </div>
</div>
<div class="col-md-1 col-12">
    <div class="form-group margin-nol">
        <label for="stock" class="d-block d-md-none">Stock</label>
        <input type="text" class="form-control text-right font-weight-bold" id="stock" name="stock[]" readonly tabindex="-1" />
    </div>
</div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qty" class="d-block d-md-none">QTY</label>
                    <input type="text" class="form-control numeral-mask-digit text-right tombol-panah"
                        data-type-el-kiri="select"
                        data-nama-el-kiri='articleId'
                        data-type-el-kanan='input'
                        data-nama-el-kanan='note'
                        id ="qty" name="qty[]" maxlength="10" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="uom" class="d-block d-md-none">Uom</label>
                    <select class="form-control" id="uom" name="uom[]">
                    </select>
                </div>
            </div>
            {{-- Sisipkan sebelum col trash button --}}
<div class="col-md-2 col-12 fg-target-wrapper" style="display:none;">
    <div class="form-group margin-nol">
        <label class="d-block d-md-none">FG Target</label>
        <select class="form-control form-control" id="fgTarget" name="fg_target[]">
            <option value="">— Pilih FG —</option>
        </select>
    </div>
</div>
            <div class="col-md-3 col-12">
                <div class="form-group margin-nol">
                    <label for="note" class="d-block d-md-none">Note</label>
                    <input type="text" class="form-control tombol-panah"
                        data-type-el-kiri="input"
                        data-nama-el-kiri='qty'
                        id = "note" name="note[]"  maxlength="150">
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol text-center">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal();">
                        <i data-feather="trash-2" class="remove_button feather-24">
                        </i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- \.table row --}}

<div id="new_row_show" name="new_row_show[]" class="d-none">
    <div id="baru_show">
        <div class="form-row d-flex align-items-center">
            <div class="col-md-6 col-12">
                <div class="form-group margin-nol">
                    <label for="articleId" class="d-block d-md-none">Article</label>
                    <input type="text" class="form-control" id="articleIdShow" name="articleIdShow[]" disabled>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyShow" class="d-block d-md-none">QTY</label>
                    <div class="input-group input-group-merge">
                        <input type="text" class="form-control numeral-mask-digit text-right" id = "qtyShow" name="qtyShow[]" maxlength="9" />
                        <div class="input-group-append">
                            <span class="input-group-text" id ="uomShow" name="uomShow[]"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="noteShow" class="d-block d-md-none">Note</label>
                    <input type="text" class="form-control" id = "noteShow" name="noteShow[]"  maxlength="150">
                </div>
            </div>
        </div>
    </div>
</div>

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

    .qty-over-stock{
    background-color:#f8d7da !important;
    border-color:#f5c2c7 !important;
    color:#842029 !important;
}

</style>

<script type="text/javascript">
    function formatStock(v){
        let n = parseFloat(String(v).replace(/,/g, ''));
        if (isNaN(n)) return '';
        return parseFloat(n.toFixed(4)).toString();
    }

    const currentDate = "{{ $currentDateValue }}";
    const trDate = $('#trDate');
    let dataArticle = "";
    let dataLocationTo = "";
    let isLocationToBooth = false; // ← flag booth
    let isLocationFromRM  = false;  // ← Location From bertipe rm

/** True hanya kalau From=rm DAN To=booth */
function shouldShowFgTarget() {
    return isLocationFromRM && isLocationToBooth;
}

/** Toggle header kolom FG Target sesuai kondisi gabungan */
function toggleFgTargetHeader() {
    shouldShowFgTarget() ? $('#headerFgTarget').show() : $('#headerFgTarget').hide();
}
    // ── batas ketat qty hanya berlaku untuk gudang Consumable ──
const CONSUMABLE_LOCATION = '006';
function isStrictStockLocation() {
    return (typeof locationFrom !== 'undefined' && locationFrom.val() === CONSUMABLE_LOCATION);
}

    if (trDate.length) {
        trDate.flatpickr({ dateFormat: "d-m-Y" });
    }

    function reloadPage(){
        window.location.reload();
    }

    $("#uploadExcel").click(function(){
        if (!$("#frmExcel")[0].checkValidity()){
            $("#frmExcel").submit();
        } else {
            $(".loading-spinner-container").addClass("-show");
            $("#uploadExcel").attr('disabled','disabled');
            $('.disabled-el').removeAttr('disabled');
            $("#frmExcel").submit();
        }
    });

    $("#cmdCancel,#cmdNew").click(function(){
        reloadPage();
    });


    /**
 * Cek location type untuk Location From, set flag isLocationFromRM,
 * lalu refresh header + semua row.
 */
function checkAndSetFromRmFlag(locCode) {
    if (!locCode) {
        isLocationFromRM = false;
        toggleFgTargetHeader();
        refreshAllFgTarget();
        return;
    }
    $.ajax({
        url: "{{ route('transferStock.checkLocationType') }}",
        method: "GET",
        data: { location_code: locCode },
        dataType: "json",
        success: function(res) {
            isLocationFromRM = (res.location_type === 'rm');
            toggleFgTargetHeader();
            refreshAllFgTarget();
        },
        error: function() {
            isLocationFromRM = false;
            toggleFgTargetHeader();
            refreshAllFgTarget();
        }
    });
}
    // ============================================================
    // FG TARGET HELPERS
    // ============================================================

    /**
     * Cek location type ke server, set flag isLocationToBooth,
     * lalu refresh semua row.
     */
    function checkAndSetBoothFlag(locCode) {
    if (!locCode) {
        isLocationToBooth = false;
        toggleFgTargetHeader();
        refreshAllFgTarget();
        return;
    }
    $.ajax({
        url: "{{ route('transferStock.checkLocationType') }}",
        method: "GET",
        data: { location_code: locCode },
        dataType: "json",
        success: function(res) {
            isLocationToBooth = (res.location_type === 'booth');
            toggleFgTargetHeader();
            refreshAllFgTarget();
        },
        error: function() {
            isLocationToBooth = false;
            toggleFgTargetHeader();
            refreshAllFgTarget();
        }
    });
}

    /** Refresh semua row setelah flag booth berubah */
    function refreshAllFgTarget() {
        let cloneIdx = 1;
        while ($("#new_row" + cloneIdx).length) {
            evaluateFgTargetForRow(cloneIdx);
            cloneIdx++;
        }
    }

    /**
     * Evaluasi 1 row: tampilkan/sembunyikan FG Target,
     * load list FG dari BOM jika perlu.
     * @param {number} rowNum - cloneCount row
     */
    function evaluateFgTargetForRow(rowNum) {
        const $select   = $("#articleId" + rowNum);
        const $fgSelect = $("#fgTarget"  + rowNum);
        const $wrapper  = $fgSelect.closest('.fg-target-wrapper');

        if (!$fgSelect.length) return;

        const articleCode = $select.val();
        const articleType = ($select.find(":selected").data("article-type") || '').toUpperCase();
        // data-article-type wajib ada di <option> dari server (RMP / RMNP / FG / dll)

        // ← TAMBAH INI SEMENTARA UNTUK DEBUG
    console.log('rowNum:', rowNum);
    console.log('articleCode:', articleCode);
    console.log('articleType:', articleType);
    console.log('isLocationToBooth:', isLocationToBooth);
    console.log('shouldShow:', isLocationToBooth && !!articleCode && ['RMP','RMNP'].includes(articleType));

       const shouldShow = shouldShowFgTarget() &&
                   !!articleCode &&
                   ['RMP', 'RMNP'].includes(articleType);

        if (shouldShow) {
            $wrapper.show();
            // Load FG dari BOM hanya jika belum ada pilihan (hindari reload berulang)
            if ($fgSelect.find('option').length <= 1) {
                loadFgByRm(articleCode, $fgSelect);
            }
        } else {
            $wrapper.hide();
            $fgSelect.val('');
        }
    }

    /**
     * Fetch FG list dari BOM berdasarkan article_code RM,
     * populate ke $selectEl.
     */
    function loadFgByRm(articleCode, $selectEl) {
        $selectEl.prop('disabled', true)
                 .html('<option value="">Memuat...</option>');

        $.ajax({
            url: "{{ route('transferStock.fgByRm') }}",
            method: "GET",
            data: { article_code: articleCode },
            dataType: "json",
            success: function(data) {
                $selectEl.html('<option value="">— Pilih FG —</option>');
                if (data.length > 0) {
                   $.each(data, function(i, fg) {
    $selectEl.append(
        $('<option>', {
            value: fg.fg_code,
            text:  fg.fg_alt_code + ' — ' + fg.fg_name  // ← pakai fg_alt_code
        })
    );
});
                } else {
                    $selectEl.append('<option value="" disabled>Tidak ada FG di BOM</option>');
                }
                $selectEl.prop('disabled', false);
            },
            error: function() {
                $selectEl.html('<option value="">— Pilih FG —</option>')
                         .prop('disabled', false);
            }
        });
    }

    // ============================================================
    // SIMPAN DATA
    // ============================================================

    simpanData = (oEdit) => {
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        } else {
            $('.disabled-el').removeAttr('disabled');
            let objQty  = $('#article_row input[name="qty[]"]');
            let objUom  = $('#article_row select[name="uom[]"]');
            let objNote = $('#article_row input[name="note[]"]');
            let arrArticles = [];
            let articles;
            let flag  = 0;
            let pesan = "";

            let locFrom = $('#locationFrom').val();
            let locTo   = $('#locationTo').val();
            if (!locFrom) { pesan += "Location From harus dipilih <br>"; flag = 1; }
            if (!locTo)   { pesan += "Location To harus dipilih <br>";   flag = 1; }
            if (locFrom && locTo && locFrom === locTo) {
                pesan += "Location From dan Location To tidak boleh sama <br>"; flag = 1;
            }

            $("#article_row select[name='articleId[]']").map(function(i) {
                let $this = $(this);
                if ($this.val()) {
                    let articleName = $this.select2('data')[0].text;
                    let plu   = $this.val();
                    let qty   = objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let note  = objNote.eq(i).val();
                    let uom   = objUom.eq(i).val();

                    // ── ambil fg_target dari row yang sama ──────────
                    let $row     = $this.closest('.tanda-baris');
                    let fgTarget = $row.find('select[name="fg_target[]"]').val() || null;
                    // ────────────────────────────────────────────────

                   // let stock = parseFloat(objQty.eq(i).attr('data-stock'));
                    //if (!isNaN(stock) && parseFloat(qty) > stock) {
                      //  pesan += "Qty " + articleName + " (" + qty + ") melebihi stock tersedia (" + stock + ") <br>";
                        //flag = 1;
                    //}

                    let stock = parseFloat(objQty.eq(i).attr('data-stock'));
if (!isNaN(stock) && parseFloat(qty) > stock) {
    if (isStrictStockLocation()) {
        // gudang Consumable (006): tetap ketat, blok save
        pesan += "Qty " + articleName + " (" + qty + ") melebihi stock tersedia (" + stock + ") <br>";
        flag = 1;
    }
    // gudang lain: dibiarkan lolos (over-stock diperbolehkan), tidak menaikkan flag
}

                    // Validasi: jika booth & artikel RMP/RMNP, fg_target wajib diisi
                    let articleType = ($this.find(":selected").data("article-type") || '').toUpperCase();
                    if (isLocationToBooth && ['RMP','RMNP'].includes(articleType) && !fgTarget) {
                        pesan += "FG Target untuk artikel <b>" + articleName + "</b> wajib dipilih <br>";
                        flag = 1;
                    }

                    if ((plu !== '') && (qty > 0)) {
                        arrArticles.push({
                            "article_code" : plu,
                            "qty"          : parseFloat(qty),
                            "uom"          : uom,
                            "note"         : note,
                            "fg_target"    : fgTarget, // ← tambahan
                        });
                    }

                    if (qty == 0) {
                        pesan += "QTY of items " + articleName + " cannot be 0 <br>";
                        flag = 1;
                    }
                }
            });

            if (arrArticles.length == 0) {
                pesan += "Articles must be filled in completely <br>";
                flag = 1;
            } else {
                let obj = {};
                arrArticles.forEach((item) => {
                    // key gabungkan article_code + fg_target agar beda FG tidak digabung
                    let key = item.article_code + '|' + (item.fg_target || '');
                    if (obj[key]) {
                        obj[key].qty += item.qty;
                    } else {
                        obj[key] = { ...item };
                    }
                });
                articles = Object.values(obj);
            }

            if (flag == 0) {
                let trNumber = "";
                let url;
                if (oEdit) {
                    trNumber = $('#trNumber').val();
                    url = "{{ route('transferStock.update') }}";
                } else {
                    url = "{{ route('transferStock.store') }}";
                }

                let trDate       = $('#trDate').val();
                let note         = $('#note').val();
                let locationFrom = $('#locationFrom').val();
                let locationTo   = $('#locationTo').val();

                $.ajax({
                    type: "post",
                    url: url,
                    data: {
                        articles     : JSON.stringify(articles),
                        trNumber     : trNumber,
                        trDate       : trDate,
                        note         : note,
                        locationFrom : locationFrom,
                        locationTo   : locationTo
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0) {
                            for (let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#trNumber').attr('disabled','disabled');
                        } else {
                            show_msg(data.title, data.message, data.alert);
                            $('#trNumber').attr('disabled','disabled');
                            $('#trNumber').val(data.trNumber);
                            $('#oEdit').val(data.oEdit);
                            if (oEdit == false) {
                                window.location.href = "{{ route('transferStock.create') }}";
                            }
                        }
                    },
                    error: function(error) { console.log(error); }
                });

            } else {
                Swal.fire('Warning..', pesan, 'warning');
            }
        }
    }

    approve = (trNumber, objButton) => {
        $('#' + objButton).attr('disabled','disabled');
        $.ajax({
            type: "GET",
            url: "{{ route('transferStock.approve') }}",
            data: { trNumber: trNumber },
            dataType: "json",
            success: function(data) {
                if (data.status == 0) {
                    for (let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#trNumber').attr('disabled','disabled');
                } else {
                    show_msg(data.title, data.message, data.alert);
                    $('#trNumber').attr('disabled','disabled');
                    $('#cmdApprove').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');
                    window.location.reload();
                }
            },
            error: function(error) { console.log(error); }
        });
    }

    // ============================================================
    // ADD ROW
    // ============================================================

    let cloneCount = 0;

    add_new_row_edit = (article, qty, uom, uomMember, note, locationTo) => {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row' + cloneCount);
        $("#new_row" + cloneCount).find('#articleId').attr('id', 'articleId' + cloneCount);
        changeselect('trArticle', 'articleId' + cloneCount, article);
        $("#new_row" + cloneCount).find('#qty').attr('id', 'qty' + cloneCount);
        $("#new_row" + cloneCount).find('#note').attr('id', 'note' + cloneCount);
        $("#note" + cloneCount).val(note);

        let selStock = $("#articleId" + cloneCount).find(":selected").data("stock");
if (selStock !== undefined && selStock !== null && selStock !== '') {
    $("#qty" + cloneCount).attr('data-stock', selStock);
    // hanya clamp otomatis kalau gudang Consumable (006)
    if (isStrictStockLocation() && parseFloat(qty) > parseFloat(selStock)) {
        qty = selStock;
    }
}
$("#qty" + cloneCount).val(qty);

$("#new_row" + cloneCount).find('#stock').attr('id', 'stock' + cloneCount);
if (selStock !== undefined && selStock !== null && selStock !== '') {
    $("#stock" + cloneCount).val(formatStock(selStock));
    // tetap tandai visual kalau over stock, di gudang manapun (informasi, bukan blokir)
    if (parseFloat(qty) > parseFloat(selStock)) {
        $("#qty" + cloneCount).addClass('qty-over-stock');
    }
}

       // let selStock = $("#articleId" + cloneCount).find(":selected").data("stock");
        //if (selStock !== undefined && selStock !== null && selStock !== '') {
          //  $("#qty" + cloneCount).attr('data-stock', selStock);
           // if (parseFloat(qty) > parseFloat(selStock)) qty = selStock;
        //}
        //$("#qty" + cloneCount).val(qty);

        //$("#new_row" + cloneCount).find('#stock').attr('id', 'stock' + cloneCount);
        //if (selStock !== undefined && selStock !== null && selStock !== '') {
          //  $("#stock" + cloneCount).val(formatStock(selStock));
            //if (parseFloat(qty) > parseFloat(selStock)) {
              //  $("#qty" + cloneCount).addClass('qty-over-stock');
            //}
        //}

        let uomOption = "";
        if (uomMember) {
            let arrUomMember = uomMember.split(',');
            $.each(arrUomMember, function(index, val) { uomOption += `<option>${val}</option>`; });
        } else {
            if (uom) uomOption += `<option>${uom}</option>`;
        }

        $("#new_row" + cloneCount).find('#uom').attr('id', 'uom' + cloneCount);
        $("#uom" + cloneCount).html(uomOption);
        $("#uom" + cloneCount).val(uom).trigger('change');

        // ── FG TARGET ──────────────────────────────────────
        $("#new_row" + cloneCount).find('#fgTarget').attr('id', 'fgTarget' + cloneCount);
        evaluateFgTargetForRow(cloneCount);
        // ───────────────────────────────────────────────────

        $("#remove_button").tooltip();
        hitungTotal();
        hitungGrandTotal();
        mask_thousand_digit(numberOfDecimalDigit);
    }

    function add_new_row() {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row' + cloneCount);
        $("#new_row" + cloneCount).find('#articleId').attr('id', 'articleId' + cloneCount);
        changeselect('trArticle', 'articleId' + cloneCount, '');

        // ── FG TARGET ──────────────────────────────────────
        $("#new_row" + cloneCount).find('#fgTarget').attr('id', 'fgTarget' + cloneCount);
        evaluateFgTargetForRow(cloneCount);
        // ───────────────────────────────────────────────────

        $('#remove_button').tooltip();
        splitArticle();
        hitungTotal();
        hitungGrandTotal();
        mask_thousand_digit(numberOfDecimalDigit);
        $('[data-toggle="tooltip"]').tooltip();
    }

    // ============================================================
    // ARTICLE & LOCATION HELPERS
    // ============================================================

    function isiArticleByLocation(dependent, location) {
        $.ajax({
            url: "{{ route('dynamic.dependent') }}",
            method: "POST",
            data: { dependent: dependent, value: location },
            success: function(result) {
                dataArticle = result;
            }
        });
    }

    function changeselect(dependent, obj, article) {
        $('#' + obj).attr('disabled','disabled');
        $('#' + obj).html(dataArticle);
        $('#' + obj).select2();
        $('#' + obj).val(article).trigger('change');
        $('#' + obj).removeAttr('disabled');
    }

    function splitArticle(){
        let objArticle = $('#article_row select[name="articleId[]"]');
        let objQty     = $('#article_row input[name="qty[]"]');
        let objUom     = $('#article_row select[name="uom[]"]');
        let objStock   = $('#article_row input[name="stock[]"]');

        objArticle.change(function(e) {
            if ($(this).val()) {
                let objIndex  = objArticle.index(this);
                let uomMember = objArticle.eq(objIndex).find(":selected").data("uom-member");
                let uom       = objArticle.eq(objIndex).find(":selected").data("uom");
                let stock     = objArticle.eq(objIndex).find(":selected").data("stock");

                if (stock !== undefined && stock !== null && stock !== '') {
                    objQty.eq(objIndex).attr('data-stock', stock);
                    objStock.eq(objIndex).val(formatStock(stock));
                } else {
                    objQty.eq(objIndex).removeAttr('data-stock');
                    objStock.eq(objIndex).val('');
                }
                objQty.eq(objIndex).val('').removeClass('qty-over-stock');

                let uomOption = "";
                if (uomMember) {
                    $.each(uomMember.split(','), function(i, val) { uomOption += `<option>${val}</option>`; });
                } else if (uom) {
                    uomOption += `<option>${uom}</option>`;
                }
                objUom.eq(objIndex).html(uomOption);
                objUom.eq(objIndex).val(uom).trigger('change');

                if (uomMember) {
                    setTimeout(() => { objQty.eq(objIndex).focus().select(); }, 5);
                }

                // ── re-evaluasi FG Target saat artikel diganti ──────
                let rowId = $(this).attr('id').replace('articleId', '');
                evaluateFgTargetForRow(rowId);
                // ────────────────────────────────────────────────────
            }
        });
    }

    // ============================================================
    // VALIDASI QTY OVER STOCK
    // ============================================================

    $(document).on('input', '#article_row input[name="qty[]"]', function() {
    let stock = parseFloat($(this).attr('data-stock'));
    let raw   = ($(this).val() || '0').toString().replace(/,/g, '');
    let val   = parseFloat(raw) || 0;

    if (!isNaN(stock) && val > stock) {
        $(this).addClass('qty-over-stock')
               .attr('title', 'Qty melebihi stock tersedia (' + formatStock(stock) + ')');

        if (isStrictStockLocation()) {
            show_msg('Warning', 'Qty transfer melebihi stock tersedia (' + stock + ') di gudang ini.', 'warning');
        }
        // gudang selain Consumable: silent, cukup border merah sebagai penanda, tanpa toast berulang
    } else {
        $(this).removeClass('qty-over-stock').removeAttr('title');
    }
    hitungGrandTotal();
});

   // $(document).on('input', '#article_row input[name="qty[]"]', function() {
     //   let stock = parseFloat($(this).attr('data-stock'));
       // let raw   = ($(this).val() || '0').toString().replace(/,/g, '');
        //let val   = parseFloat(raw) || 0;
        //if (!isNaN(stock) && val > stock) {
          //  $(this).addClass('qty-over-stock')
            //       .attr('title', 'Qty melebihi stock tersedia (' + formatStock(stock) + ')');
            //show_msg('Warning', 'Qty transfer melebihi stock tersedia (' + stock + ') di gudang ini.', 'warning');
        //} else {
          //  $(this).removeClass('qty-over-stock').removeAttr('title');
        //}
        //hitungGrandTotal();
    //});

    // ============================================================
    // HITUNG TOTAL
    // ============================================================

    hitungTotal = () => {
        let objQty = $('#article_row input[name="qty[]"]');
        objQty.keyup(function() { hitungGrandTotal(); });
    }

    hitungGrandTotal = () => {
        let objArticle = $('#article_row select[name="articleId[]"]');
        let objQTY     = $('#article_row input[name="qty[]"]');
        let qty        = objQTY.map(function(){ return $(this).val(); }).get();
        $("#totalRow").val(objArticle.length);
        $("#totalQty").val(humanizeNumber(sumFromArray(qty)));
    }

    $("input[type='text']").click(function() { $(this).select(); });

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
</script>