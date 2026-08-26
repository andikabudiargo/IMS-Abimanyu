{{-- row template untuk di-clone --}}
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris">
        <div class="form-row d-flex align-items-center">
            <div class="col-md-4 col-12">
                <div class="form-group margin-nol">
                    <label for="articleId" class="d-block d-md-none">Article</label>
                    <select class="form-control" id="articleId" name="articleId[]"></select>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="stock" class="d-block d-md-none">Stock</label>
                    <input type="text" class="form-control text-right font-weight-bold"
                        id="stock" name="stock[]" readonly tabindex="-1" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qty" class="d-block d-md-none">QTY</label>
                    <input type="text" class="form-control numeral-mask-digit text-right tombol-panah"
                        data-type-el-kiri="select" data-nama-el-kiri='articleId'
                        data-type-el-kanan='input' data-nama-el-kanan='note'
                        id="qty" name="qty[]" maxlength="10" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="uom" class="d-block d-md-none">UoM</label>
                    <select class="form-control" id="uom" name="uom[]"></select>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="form-group margin-nol">
                    <label for="note" class="d-block d-md-none">Note</label>
                    <input type="text" class="form-control tombol-panah"
                        data-type-el-kiri="input" data-nama-el-kiri='qty'
                        id="note" name="note[]" maxlength="150">
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol text-center">
                    <a onmouseover="this.style.cursor='pointer'"
                        onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal();">
                        <i data-feather="trash-2" class="remove_button feather-24"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .margin-nol { margin-bottom: 0.5rem; }
    label.titik-dua::after { content: ":"; position: absolute; right: 1px; }
    .qty-over-stock {
        background-color: #f8d7da !important;
        border-color: #f5c2c7 !important;
        color: #842029 !important;
    }
</style>

<script type="text/javascript">
const QTY_DECIMAL = 2;

// ── Diisi dari server-side di masing-masing view (create/edit) ──
// Kalau tidak ada, fallback ke hari ini via moment/js (jaga-jaga)
if (typeof currentDate === 'undefined') {
    var currentDate = new Date().toLocaleDateString('id-ID', {
        day:'2-digit', month:'2-digit', year:'numeric'
    }).replace(/\//g, '-');
}

let dataArticle = "";
let cloneCount  = 0;

// ============================================================
// UTILITY
// ============================================================
function formatStock(v) {
    let n = parseFloat(String(v).replace(/,/g, ''));
    return isNaN(n) ? '' : parseFloat(n.toFixed(4)).toString();
}
function formatQty(v) {
    let n = parseFloat(String(v).replace(/,/g, ''));
    return isNaN(n) ? '' : parseFloat(n.toFixed(2)).toString();
}

btnLoading = ($btn, text) => {
    $btn.data('original-html', $btn.html()).prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm mr-50" role="status"></span>'
            + (text || 'Menyimpan...'));
};
btnReset = ($btn) => {
    $btn.prop('disabled', false).html($btn.data('original-html'));
    if (typeof feather !== 'undefined') feather.replace();
};

// ============================================================
// AMBIL ARTIKEL PER LOKASI — mengisi global dataArticle
// ============================================================
function isiArticleByLocation(locationCode) {
    dataArticle = "";   // reset dulu biar polling di create.blade.php bisa detect selesai
    $.ajax({
        url    : "{{ route('stockConsumption.articleByLocation') }}",
        method : "GET",
        data   : { location: locationCode },
        success: function (result) {
            let options = '<option value=""></option>';
            $.each(result, function (i, item) {
                options += `<option value="${item.article_code}"
                    data-uom="${item.uom}"
                    data-stock="${item.qty}"
                    data-article-type="${item.article_type}"
                    data-uom-member="${item.uom_member ?? item.uom ?? ''}"
                >${item.article_alternative_code} - ${item.article_desc}</option>`;
            });
            dataArticle = options;   // set SETELAH response tiba → polling di create.blade detect ini
        },
        error: function (e) {
            console.error('isiArticleByLocation error', e);
            dataArticle = '<option value=""></option>';   // set supaya polling tidak hang
        }
    });
}

// ── Masukkan opsi artikel ke select dgn id tertentu ──
function changeselect(obj, article) {
    let $sel = $('#' + obj);
    $sel.attr('disabled', 'disabled').html(dataArticle).select2();
    $sel.val(article).trigger('change');
    $sel.removeAttr('disabled');
}

// ============================================================
// ADD ROW (create baru, tanpa nilai default)
// ============================================================
function add_new_row() {
    $("#article_row").append($("#new_row").clone().html());
    cloneCount++;

    let $newRow = $("#article_row").find('#baru').last();
    $newRow.attr('id', 'new_row' + cloneCount);

    $newRow.find('#articleId').attr('id', 'articleId' + cloneCount);
    changeselect('articleId' + cloneCount, '');

    $newRow.find('#qty').attr('id', 'qty' + cloneCount);
    $newRow.find('#note').attr('id', 'note' + cloneCount);
    $newRow.find('#stock').attr('id', 'stock' + cloneCount);
    $newRow.find('#uom').attr('id', 'uom' + cloneCount);

    splitArticle();
    hitungTotal();
    hitungGrandTotal();
    if (typeof mask_thousand_digit === 'function') mask_thousand_digit(numberOfDecimalDigit);
    if (typeof feather !== 'undefined') feather.replace();
    $('[data-toggle="tooltip"]').tooltip();
}

// ============================================================
// ADD ROW (mode edit — isi nilai dari DB)
// ============================================================
add_new_row_edit = function (article, qty, uom, uomMember, note) {
    $("#article_row").append($("#new_row").clone().html());
    cloneCount++;

    let $newRow = $("#article_row").find('#baru').last();
    $newRow.attr('id', 'new_row' + cloneCount);

    $newRow.find('#articleId').attr('id', 'articleId' + cloneCount);
    changeselect('articleId' + cloneCount, article);

    $newRow.find('#qty').attr('id', 'qty' + cloneCount);
    $newRow.find('#note').attr('id', 'note' + cloneCount);
    $newRow.find('#stock').attr('id', 'stock' + cloneCount);
    $newRow.find('#uom').attr('id', 'uom' + cloneCount);

    $("#note" + cloneCount).val(note);

    // Ambil stok dari option yang sudah terselect
    let selStock = $("#articleId" + cloneCount).find(":selected").data("stock");
    if (selStock !== undefined && selStock !== null && selStock !== '') {
        $("#qty" + cloneCount).attr('data-stock', selStock);
        $("#stock" + cloneCount).val(formatStock(selStock));
        if (parseFloat(qty) > parseFloat(selStock)) {
            $("#qty" + cloneCount).addClass('qty-over-stock');
        }
    }
    $("#qty" + cloneCount).val(formatQty(qty));

    // UOM options
    let uomOption = "";
    if (uomMember) {
        $.each(uomMember.split(','), function (i, val) { uomOption += `<option>${val}</option>`; });
    } else if (uom) {
        uomOption += `<option>${uom}</option>`;
    }
    $("#uom" + cloneCount).html(uomOption).val(uom).trigger('change');

    splitArticle();
    hitungTotal();
    hitungGrandTotal();
    if (typeof mask_thousand_digit === 'function') mask_thousand_digit(numberOfDecimalDigit);
    if (typeof feather !== 'undefined') feather.replace();
};

// ============================================================
// SPLIT ARTICLE — delegated change handler untuk kolom artikel
// ============================================================
function splitArticle() {
    // off dulu supaya tidak double-bind setiap kali add_new_row dipanggil
    $(document).off('change.sc-article', '#article_row select[name="articleId[]"]')
               .on('change.sc-article',  '#article_row select[name="articleId[]"]', function () {
        if (!$(this).val()) return;

        let $allArticle = $('#article_row select[name="articleId[]"]');
        let idx         = $allArticle.index(this);
        let $qtyEq      = $('#article_row input[name="qty[]"]').eq(idx);
        let $stockEq    = $('#article_row input[name="stock[]"]').eq(idx);
        let $uomEq      = $('#article_row select[name="uom[]"]').eq(idx);

        let uomMember = $(this).find(":selected").data("uom-member");
        let uom       = $(this).find(":selected").data("uom");
        let stock     = $(this).find(":selected").data("stock");

        if (stock !== undefined && stock !== null && stock !== '') {
            $qtyEq.attr('data-stock', stock);
            $stockEq.val(formatStock(stock));
        } else {
            $qtyEq.removeAttr('data-stock');
            $stockEq.val('');
        }
        $qtyEq.val('').removeClass('qty-over-stock');

        let uomOption = "";
        if (uomMember) {
            $.each(uomMember.split(','), function (i, val) { uomOption += `<option>${val}</option>`; });
        } else if (uom) {
            uomOption += `<option>${uom}</option>`;
        }
        $uomEq.html(uomOption).val(uom).trigger('change');

        // Auto-fokus ke qty setelah pilih artikel
        if (uomMember) setTimeout(() => { $qtyEq.focus().select(); }, 5);
    });
}

// ============================================================
// QTY OVER STOCK — delegated
// ============================================================
$(document).off('input.sc-qty', '#article_row input[name="qty[]"]')
           .on('input.sc-qty',  '#article_row input[name="qty[]"]', function () {
    let stock = parseFloat($(this).attr('data-stock'));
    let val   = parseFloat(($(this).val() || '0').toString().replace(/,/g, '')) || 0;
    if (!isNaN(stock) && val > stock) {
        $(this).addClass('qty-over-stock')
               .attr('title', 'Qty melebihi stock tersedia (' + formatStock(stock) + ')');
    } else {
        $(this).removeClass('qty-over-stock').removeAttr('title');
    }
    hitungGrandTotal();
});

// ============================================================
// HITUNG TOTAL
// ============================================================
hitungTotal = function () {
    // tidak perlu bind ulang; sudah pakai delegated di atas
};

hitungGrandTotal = function () {
    let objArticle = $('#article_row select[name="articleId[]"]');
    let objQTY     = $('#article_row input[name="qty[]"]');
    let qty        = objQTY.map(function () { return $(this).val(); }).get();
    $("#totalRow").val(objArticle.length);
    if (typeof sumFromArray === 'function' && typeof humanizeNumber === 'function') {
        $("#totalQty").val(humanizeNumber(sumFromArray(qty)));
    }
};

// ============================================================
// SIMPAN — dipanggil dari tombol Save di masing-masing view
// ============================================================
simpanData = function (oEdit) {
    let $btn = $('#cmdSave');
    if (!$("#frmAdd")[0].checkValidity()) { $("#frmAdd").submit(); return; }

    btnLoading($btn, 'Menyimpan...');
    $('.disabled-el').removeAttr('disabled');

    let objQty  = $('#article_row input[name="qty[]"]');
    let objUom  = $('#article_row select[name="uom[]"]');
    let objNote = $('#article_row input[name="note[]"]');

    let arr  = [];
    let flag = 0;
    let pesan = "";

    // FIX: baca nilai location dari #location (fallback) ATAU #locSelect —
    // pakai $('#location, #locSelect').first().val() supaya kompatibel dua nama id
    let locVal = $('#locSelect').length ? $('#locSelect').val() : $('#location').val();
    let coa    = $('#coa').val();

    if (!locVal) { pesan += "Location harus dipilih <br>"; flag = 1; }
    if (!coa)    { pesan += "COA harus dipilih <br>"; flag = 1; }

    $("#article_row select[name='articleId[]']").each(function (i) {
        let $this = $(this);
        if ($this.val()) {
            let articleName = $this.select2('data')[0].text;
            let plu  = $this.val();
            let qty  = (objQty.eq(i).val() || '0').replace(/,/gi, '') || 0;
            let uom  = objUom.eq(i).val();
            let note = objNote.eq(i).val();

            let stock = parseFloat(objQty.eq(i).attr('data-stock'));
            if (!isNaN(stock) && parseFloat(qty) > stock) {
                pesan += `Qty ${articleName} (${qty}) melebihi stock tersedia (${stock}) <br>`;
                flag = 1;
            }
            if (parseFloat(qty) <= 0) {
                pesan += `QTY ${articleName} tidak boleh 0 <br>`;
                flag = 1;
            }
            if (plu !== '' && parseFloat(qty) > 0) {
                arr.push({ article_code: plu, qty: parseFloat(qty), uom: uom, note: note });
            }
        }
    });

    if (arr.length === 0) { pesan += "Artikel harus diisi <br>"; flag = 1; }

    if (flag !== 0) { btnReset($btn); Swal.fire('Warning..', pesan, 'warning'); return; }

    // Gabung artikel+uom yang sama
    let obj = {};
    arr.forEach(function (item) {
        let key = item.article_code + '|' + item.uom;
        if (obj[key]) obj[key].qty += item.qty; else obj[key] = Object.assign({}, item);
    });
    let articles = Object.values(obj);

    let isEdit = (oEdit === true || oEdit === 'true');
    let url    = isEdit
        ? "{{ route('stockConsumption.update') }}"
        : "{{ route('stockConsumption.store') }}";

    $.ajax({
        type    : "POST",
        url     : url,
        data    : {
            articles   : JSON.stringify(articles),
            scNumber   : $('#scNumber').val(),
            scDate     : $('#scDate').val(),
            location   : locVal,
            coa        : coa,
            note       : $('#note').val(),
            editReason : $('#editReason').val(),
        },
        dataType: "json",
        success : function (data) {
            if (data.status == 0) {
                btnReset($btn);
                let msgs = Array.isArray(data.message) ? data.message : [data.message];
                msgs.forEach(m => show_msg(data.title, m, data.alert));
                return;
            }
            show_msg(data.title, data.message, data.alert);
            $('#scNumber').val(data.scNumber);

            if (data.redirect_url) {
                window.location.href = data.redirect_url;
            } else if (!isEdit) {
                window.location.href = "{{ route('stockConsumption.create') }}";
            } else {
                btnReset($btn);
            }
        },
        error: function (error) {
            btnReset($btn);
            console.error(error);
            show_msg('Error', 'Terjadi kesalahan saat menyimpan, cek console.', 'error');
        }
    });
};

// ============================================================
// APPROVE
// ============================================================
approve = function (scNumber, objButton) {
    $('#' + objButton).attr('disabled', 'disabled');
    $.ajax({
        type    : "GET",
        url     : "{{ route('stockConsumption.approve') }}",
        data    : { scNumber: scNumber },
        dataType: "json",
        success : function (data) {
            let msg = Array.isArray(data.message) ? data.message.join(', ') : data.message;
            show_msg(data.title, msg, data.alert);
            if (data.status == 1) {
                window.location.reload();
            } else {
                $('#' + objButton).removeAttr('disabled');
            }
        },
        error: function (e) { console.error(e); $('#' + objButton).removeAttr('disabled'); }
    });
};

$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>