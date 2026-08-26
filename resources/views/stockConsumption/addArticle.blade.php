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
                    <input type="text" class="form-control text-right font-weight-bold" id="stock" name="stock[]" readonly tabindex="-1" />
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
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal();">
                        <i data-feather="trash-2" class="remove_button feather-24"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .margin-nol{ margin-bottom:0.5rem; }
    label.titik-dua::after{ content:":"; position:absolute; right:1px; }
    .qty-over-stock{ background-color:#f8d7da !important; border-color:#f5c2c7 !important; color:#842029 !important; }
</style>

<script type="text/javascript">
const QTY_DECIMAL = 2;

function formatStock(v){
    let n = parseFloat(String(v).replace(/,/g,''));
    if (isNaN(n)) return '';
    return parseFloat(n.toFixed(4)).toString();
}
function formatQty(v){
    let n = parseFloat(String(v).replace(/,/g,''));
    if (isNaN(n)) return '';
    return parseFloat(n.toFixed(2)).toString();
}

const currentDate = "{{ $currentDateValue ?? date('d-m-Y') }}";
let dataArticle = "";
let cloneCount  = 0;

btnLoading = ($btn, text) => {
    $btn.data('original-html', $btn.html()).prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm mr-50" role="status"></span>' + (text || 'Menyimpan...'));
}
btnReset = ($btn) => {
    $btn.prop('disabled', false).html($btn.data('original-html'));
    if (typeof feather !== 'undefined') feather.replace();
}

// ============================================================
// AMBIL ARTIKEL PER LOKASI
// ============================================================
function isiArticleByLocation(location) {
    $.ajax({
        url: "{{ route('stockConsumption.articleByLocation') }}",
        method: "GET",
        data: { location: location },
        success: function(result) {
            let options = '<option value=""></option>';
            $.each(result, function(i, item) {
                options += `<option value="${item.article_code}"
                    data-uom="${item.uom}"
                    data-stock="${item.qty}"
                    data-article-type="${item.article_type}"
                    data-uom-member="${item.uom_member ?? item.uom ?? ''}"
                >${item.article_alternative_code} - ${item.article_desc}</option>`;
            });
            dataArticle = options;
        }
    });
}

function changeselect(obj, article) {
    $('#'+obj).attr('disabled','disabled').html(dataArticle).select2();
    $('#'+obj).val(article).trigger('change');
    $('#'+obj).removeAttr('disabled');
}

// ============================================================
// ADD ROW
// ============================================================
function add_new_row() {
    $("#article_row").append($("#new_row").clone().html());
    cloneCount++;
    $("#article_row").find('#baru').attr('id','new_row'+cloneCount);
    $("#new_row"+cloneCount).find('#articleId').attr('id','articleId'+cloneCount);
    changeselect('articleId'+cloneCount, '');
    $('#remove_button').tooltip();
    splitArticle();
    hitungTotal();
    hitungGrandTotal();
    if (typeof mask_thousand_digit === 'function') mask_thousand_digit(numberOfDecimalDigit);
    $('[data-toggle="tooltip"]').tooltip();
}

add_new_row_edit = (article, qty, uom, uomMember, note) => {
    $("#article_row").append($("#new_row").clone().html());
    cloneCount++;
    $("#article_row").find('#baru').attr('id','new_row'+cloneCount);
    $("#new_row"+cloneCount).find('#articleId').attr('id','articleId'+cloneCount);
    changeselect('articleId'+cloneCount, article);

    $("#new_row"+cloneCount).find('#qty').attr('id','qty'+cloneCount);
    $("#new_row"+cloneCount).find('#note').attr('id','note'+cloneCount);
    $("#note"+cloneCount).val(note);

    let selStock = $("#articleId"+cloneCount).find(":selected").data("stock");
    if (selStock !== undefined && selStock !== null && selStock !== '') {
        $("#qty"+cloneCount).attr('data-stock', selStock);
    }
    $("#qty"+cloneCount).val(formatQty(qty));

    $("#new_row"+cloneCount).find('#stock').attr('id','stock'+cloneCount);
    if (selStock !== undefined && selStock !== null && selStock !== '') {
        $("#stock"+cloneCount).val(formatStock(selStock));
        if (parseFloat(qty) > parseFloat(selStock)) $("#qty"+cloneCount).addClass('qty-over-stock');
    }

    let uomOption = "";
    if (uomMember) {
        $.each(uomMember.split(','), function(i,val){ uomOption += `<option>${val}</option>`; });
    } else if (uom) {
        uomOption += `<option>${uom}</option>`;
    }
    $("#new_row"+cloneCount).find('#uom').attr('id','uom'+cloneCount);
    $("#uom"+cloneCount).html(uomOption);
    $("#uom"+cloneCount).val(uom).trigger('change');

    $("#remove_button").tooltip();
    hitungTotal();
    hitungGrandTotal();
    if (typeof mask_thousand_digit === 'function') mask_thousand_digit(numberOfDecimalDigit);
}

function splitArticle(){
    let objArticle = $('#article_row select[name="articleId[]"]');
    let objQty     = $('#article_row input[name="qty[]"]');
    let objUom     = $('#article_row select[name="uom[]"]');
    let objStock   = $('#article_row input[name="stock[]"]');

    objArticle.off('change.sc').on('change.sc', function() {
        if ($(this).val()) {
            let idx       = objArticle.index(this);
            let uomMember = objArticle.eq(idx).find(":selected").data("uom-member");
            let uom       = objArticle.eq(idx).find(":selected").data("uom");
            let stock     = objArticle.eq(idx).find(":selected").data("stock");

            if (stock !== undefined && stock !== null && stock !== '') {
                objQty.eq(idx).attr('data-stock', stock);
                objStock.eq(idx).val(formatStock(stock));
            } else {
                objQty.eq(idx).removeAttr('data-stock');
                objStock.eq(idx).val('');
            }
            objQty.eq(idx).val('').removeClass('qty-over-stock');

            let uomOption = "";
            if (uomMember) {
                $.each(uomMember.split(','), function(i,val){ uomOption += `<option>${val}</option>`; });
            } else if (uom) {
                uomOption += `<option>${uom}</option>`;
            }
            objUom.eq(idx).html(uomOption);
            objUom.eq(idx).val(uom).trigger('change');

            if (uomMember) setTimeout(() => { objQty.eq(idx).focus().select(); }, 5);
        }
    });
}

// over-stock indicator (konsumsi TIDAK boleh melebihi stok)
$(document).on('input', '#article_row input[name="qty[]"]', function() {
    let stock = parseFloat($(this).attr('data-stock'));
    let val   = parseFloat(($(this).val()||'0').toString().replace(/,/g,'')) || 0;
    if (!isNaN(stock) && val > stock) {
        $(this).addClass('qty-over-stock').attr('title','Qty melebihi stock tersedia ('+formatStock(stock)+')');
    } else {
        $(this).removeClass('qty-over-stock').removeAttr('title');
    }
    hitungGrandTotal();
});

// ============================================================
// TOTAL
// ============================================================
hitungTotal = () => {
    $('#article_row input[name="qty[]"]').off('keyup.sc').on('keyup.sc', function(){ hitungGrandTotal(); });
}
hitungGrandTotal = () => {
    let objArticle = $('#article_row select[name="articleId[]"]');
    let objQTY     = $('#article_row input[name="qty[]"]');
    let qty = objQTY.map(function(){ return $(this).val(); }).get();
    $("#totalRow").val(objArticle.length);
    if (typeof sumFromArray === 'function') $("#totalQty").val(humanizeNumber(sumFromArray(qty)));
}

// ============================================================
// SIMPAN
// ============================================================
simpanData = (oEdit) => {
    let $btn = $('#cmdSave');
    if (!$("#frmAdd")[0].checkValidity()){ $("#frmAdd").submit(); return; }

    btnLoading($btn, 'Menyimpan...');
    $('.disabled-el').removeAttr('disabled');

    let objQty  = $('#article_row input[name="qty[]"]');
    let objUom  = $('#article_row select[name="uom[]"]');
    let objNote = $('#article_row input[name="note[]"]');
    let arr = [];
    let flag = 0, pesan = "";

    let location = $('#location').val();
    let coa      = $('#coa').val();
    if (!location) { pesan += "Location harus dipilih <br>"; flag = 1; }
    if (!coa)      { pesan += "COA harus dipilih <br>"; flag = 1; }

    $("#article_row select[name='articleId[]']").map(function(i){
        let $this = $(this);
        if ($this.val()) {
            let articleName = $this.select2('data')[0].text;
            let plu  = $this.val();
            let qty  = (objQty.eq(i).val()||'0').replace(/,/gi,'') || 0;
            let uom  = objUom.eq(i).val();
            let note = objNote.eq(i).val();

            let stock = parseFloat(objQty.eq(i).attr('data-stock'));
            if (!isNaN(stock) && parseFloat(qty) > stock) {
                pesan += "Qty " + articleName + " (" + qty + ") melebihi stock tersedia (" + stock + ") <br>";
                flag = 1;
            }
            if (parseFloat(qty) <= 0) {
                pesan += "QTY " + articleName + " tidak boleh 0 <br>";
                flag = 1;
            }
            if (plu !== '' && parseFloat(qty) > 0) {
                arr.push({ "article_code":plu, "qty":parseFloat(qty), "uom":uom, "note":note });
            }
        }
    });

    if (arr.length === 0) { pesan += "Artikel harus diisi <br>"; flag = 1; }

    // gabung artikel+uom yang sama
    let articles = arr;
    if (flag === 0) {
        let obj = {};
        arr.forEach((item) => {
            let key = item.article_code + '|' + item.uom;
            if (obj[key]) obj[key].qty += item.qty; else obj[key] = { ...item };
        });
        articles = Object.values(obj);
    }

    if (flag !== 0) { btnReset($btn); Swal.fire('Warning..', pesan, 'warning'); return; }

    let url      = oEdit == 'true' || oEdit === true ? "{{ route('stockConsumption.update') }}" : "{{ route('stockConsumption.store') }}";
    let scNumber = $('#scNumber').val();

    $.ajax({
        type: "post",
        url: url,
        data: {
            articles   : JSON.stringify(articles),
            scNumber   : scNumber,
            scDate     : $('#scDate').val(),
            location   : location,
            coa        : coa,
            note       : $('#note').val(),
            editReason : $('#editReason').val(),
        },
        dataType: "json",
        success: function(data) {
            if (data.status == 0) {
                btnReset($btn);
                for (let i = 0; i < data.message.length; i++) show_msg(data.title, data.message[i], data.alert);
                return;
            }
            show_msg(data.title, data.message, data.alert);
            $('#scNumber').val(data.scNumber);

            if (data.redirect_url) {
                window.location.href = data.redirect_url;
            } else if (oEdit == false || oEdit == 'false') {
                window.location.href = "{{ route('stockConsumption.create') }}";
            } else {
                btnReset($btn);
            }
        },
        error: function(error) {
            btnReset($btn);
            console.log(error);
            show_msg('Error', 'Terjadi kesalahan saat menyimpan, cek console.', 'error');
        }
    });
}

// ============================================================
// APPROVE
// ============================================================
approve = (scNumber, objButton) => {
    $('#'+objButton).attr('disabled','disabled');
    $.ajax({
        type: "GET",
        url: "{{ route('stockConsumption.approve') }}",
        data: { scNumber: scNumber },
        dataType: "json",
        success: function(data) {
            if (data.status == 0) {
                for (let i = 0; i < data.message.length; i++) show_msg(data.title, data.message[i], data.alert);
            } else {
                show_msg(data.title, data.message, data.alert);
                window.location.reload();
            }
        },
        error: function(error){ console.log(error); }
    });
}

$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>