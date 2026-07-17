{{-- adjustment/stockAdjustment/addArticle.blade.php

     Grid artikel Stock Adjustment — dipakai bersama create.blade.php & edit.blade.php.

     ── MODEL INPUT ──────────────────────────────────────────────────────
     User mengisi SALDO AKHIR (balance) yang seharusnya. Sistem menghitung
     selisih terhadap Stock Before, lalu menurunkan qty + direction (+/-).
     Baris yang selisihnya 0 tidak dikirim ke server.

     ── KONTRAK DENGAN HALAMAN PEMAKAI ───────────────────────────────────
     Wajib ada di DOM   : #adjDate #adjType #location #periode #description
                          #article_row #totalRow #oEdit #cmdSave
     Opsional di DOM    : #note        (kalau tidak ada, dikirim null)
     Disediakan halaman : clearFileInput(id)
     Hook opsional      : onSaveSuccess(data, oEdit), onSaveFailed(data)
     Global untuk edit  : adjRevisionCode, adjReviseReason  (lihat blok 1)
--}}

<style>
    .margin-nol  { margin-bottom: 0.5rem; }
    .mb-50       { margin-bottom: 0.5rem; }
    .mb-03       { margin-bottom: 0.3rem; }

    label.titik-dua::after { content:":"; position:absolute; right:1px; }

    .adj-qty-output.adj-positive { border-color:#28c76f !important; color:#28c76f !important; background:#f0fdf6 !important; }
    .adj-qty-output.adj-negative { border-color:#ea5455 !important; color:#ea5455 !important; background:#fff5f5 !important; }

    .balance-input.balance-changed { border-color:#ff9f43; }

    @media screen and (min-width:1200px) and (max-width:1600px) {
        .lebar-list-item     { width:100%; }
        .container-list-item { max-width:100%; overflow-x:auto; scrollbar-width:thin; margin-top:7px; }
    }
    @media only screen and (min-width:600px) and (max-width:1200px) {
        .lebar-list-item     { width:200%; }
        .container-list-item { max-width:100%; overflow-x:auto; scrollbar-width:thin; margin-top:7px; }
    }
</style>

<script type="text/javascript">

/* ══════════════════════════════════════════════════════════════════════
   1. KONSTANTA & STATE
══════════════════════════════════════════════════════════════════════ */

const currentDate = "{{ $currentDateValue }}";

/** Toleransi float. Samakan dengan EPSILON di StockAdjustmentController. */
const ADJ_EPS = 1e-6;

/** Desimal maksimum qty — samakan dengan presisi kolom di PostgreSQL. */
const ADJ_DP = 4;

const IMPORT_BATCH = 150;   // baris per insert DOM
const STOCK_CHUNK  = 500;   // article_code unik per request bulk

let dataArticle = null;   // null = belum selesai load; '' = gagal/kosong
let articleMeta = {};     // code -> { label, uom, uomMember }
let rowSeq      = 0;      // penomoran baris, tidak pernah dipakai ulang

/* ── Di-set oleh edit.blade.php kalau dokumen sudah pernah diposting (4/6).
      create.blade.php membiarkannya null → perilaku tidak berubah.

      adjRevisionCode dikirim ke stockBefore & stockBeforeBulk. Tanpa itu
      Stock Before jadi DOBEL: get_last_qty_new memfilter `<= tanggal`, jadi
      movement dokumen ini sendiri ikut terhitung di saldo historisnya.
      Server mengeluarkannya kalau adjCode dikirim.                      ── */
let adjRevisionCode = null;
let adjReviseReason = null;


/* ══════════════════════════════════════════════════════════════════════
   2. UTIL
══════════════════════════════════════════════════════════════════════ */

/** "1,234.5" -> 1234.5 ; null/''/NaN -> 0 */
function parseNum(v) {
    if (v === null || v === undefined || v === '') return 0;
    const n = parseFloat(String(v).replace(/,/g, ''));
    return isNaN(n) ? 0 : n;
}

/** Bulatkan ke ADJ_DP desimal — mencegah debu float jadi qty palsu. */
function roundQty(n) {
    const f = Math.pow(10, ADJ_DP);
    return Math.round((n + Number.EPSILON) * f) / f;
}

function isZero(n) { return Math.abs(n) < ADJ_EPS; }

function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

/* Semua id baris terkumpul di satu tempat, bukan tersebar sebagai
   string concat di belasan fungsi. */
function $rowEl(n)       { return $('#new_row' + n); }
function $article(n)     { return $('#articleId' + n); }
function $uom(n)         { return $('#uom' + n); }
function $stockBefore(n) { return $('#stockBefore' + n); }
function $balance(n)     { return $('#balanceQty' + n); }
function $adjQty(n)      { return $('#adjQty' + n); }
function $notes(n)       { return $('#notesRow' + n); }

function rowIds() {
    return $('#article_row .tanda-baris').map(function () {
        return $(this).attr('id').replace('new_row', '');
    }).get();
}

function getStockBefore(n) { return parseNum($stockBefore(n).data('raw')); }

/**
 * Nilai numerik saldo akhir.
 *
 * Kenapa tidak langsung parseNum($el.val()): humanizeNumber() memformat
 * tampilan dan bisa memotong desimal. Kalau stok aslinya 10.1234 lalu
 * ditampilkan "10.12", membacanya balik dari .val() menghasilkan selisih
 * -0.0034 — adjustment palsu untuk baris yang sebenarnya tidak berubah.
 * Jadi nilai eksak disimpan di .data('raw') dan itu yang dipakai selama
 * user belum mengetik sendiri.
 */
function getBalance(n) {
    const $b = $balance(n);
    if ($b.data('typed')) return parseNum($b.val());
    const raw = $b.data('raw');
    return (raw === undefined || raw === null) ? parseNum($b.val()) : parseNum(raw);
}

function setBalance(n, value, opts) {
    opts = opts || {};
    const v = Math.max(0, parseNum(value));
    $balance(n).val(humanizeNumber(v))
               .data('raw', v)
               .data('typed', false)
               .data('autofilled', !!opts.autofilled);
}


/* ══════════════════════════════════════════════════════════════════════
   3. ARTICLE META
══════════════════════════════════════════════════════════════════════ */

function isiArticle(dependent) {
    $.ajax({
        url:    "{{ route('dynamic.dependent') }}",
        method: "POST",
        data:   { dependent: dependent },
        success: function (result) {
            dataArticle = result;
            buildArticleMeta(result);
        },
        error: function () {
            dataArticle = '';
            console.error('Gagal load daftar artikel');
        }
    });
}

function buildArticleMeta(html) {
    articleMeta = {};
    $('<select>').html(html).find('option').each(function () {
        const $o = $(this), val = $o.val();
        if (!val) return;
        articleMeta[val] = {
            label:     $o.text(),
            uom:       $o.data('uom'),
            uomMember: $o.data('uom-member')
        };
    });
}

/** Jalankan cb setelah daftar artikel siap (sukses maupun gagal). */
function whenArticlesReady(cb) {
    if (dataArticle !== null) { cb(); return; }
    setTimeout(function () { whenArticlesReady(cb); }, 150);
}

function buildUomOptions(uomMember, uomBase) {
    if (uomMember) {
        return uomMember.toString().split(',')
            .map(u => `<option value="${esc(u.trim())}">${esc(u.trim())}</option>`).join('');
    }
    return uomBase ? `<option value="${esc(uomBase)}">${esc(uomBase)}</option>` : '';
}


/* ══════════════════════════════════════════════════════════════════════
   4. MARKUP BARIS — SATU-SATUNYA definisi

   Sebelumnya markup baris hidup di TIGA tempat: template #new_row (untuk
   di-clone), _buildImportRowHtml() (string, untuk import), dan _wireIds()
   (daftar id yang harus di-rename setelah clone). Menambah satu kolom
   berarti mengubah ketiganya; lupa salah satu → jalur import dan jalur
   manual berbeda diam-diam. Sekarang hanya di sini.
══════════════════════════════════════════════════════════════════════ */

function buildRowHtml(n, d) {
    d = d || {};
    const meta    = articleMeta[d.articleCode] || {};
    const label   = esc(d.label || meta.label || d.articleCode || '');
    const uomOpts = buildUomOptions(d.uomMember || meta.uomMember, d.uom || meta.uom);
    const sb      = parseNum(d.stockBefore);
    const bal     = (d.balance === null || d.balance === undefined || d.balance === '')
                        ? '' : humanizeNumber(Math.max(0, parseNum(d.balance)));

    const artOption = d.articleCode
        ? `<option value="${esc(d.articleCode)}" selected>${label}</option>`
        : '<option value=""></option>';

    return `
    <div id="new_row${n}" class="tanda-baris mb-50">
        <div class="form-row d-flex align-items-center">

            <div class="col-md-3 col-12"><div class="form-group margin-nol">
                <label class="d-block d-md-none">Article</label>
                <select class="form-control article-select" id="articleId${n}" name="articleId[]">
                    ${artOption}
                </select>
            </div></div>

            <div class="col-md-1 col-12"><div class="form-group margin-nol">
                <label class="d-block d-md-none">UOM</label>
                <select class="form-control" id="uom${n}" name="uom[]">${uomOpts}</select>
            </div></div>

            <div class="col-md-2 col-12"><div class="form-group margin-nol">
                <label class="d-block d-md-none">Stock Before</label>
                <input type="text" class="form-control text-right bg-light"
                    id="stockBefore${n}" name="stockBefore[]"
                    value="${humanizeNumber(sb)}" readonly tabindex="-1" />
            </div></div>

            <div class="col-md-2 col-12"><div class="form-group margin-nol">
                <label class="d-block d-md-none">Saldo Akhir</label>
                <input type="text" class="form-control text-right tombol-panah balance-input"
                    id="balanceQty${n}" name="balanceQty[]"
                    value="${bal}" placeholder="0" maxlength="12"
                    data-type-el-kiri="select"  data-nama-el-kiri="articleId"
                    data-type-el-kanan="input"  data-nama-el-kanan="notesRow" />
            </div></div>

            <div class="col-md-2 col-12"><div class="form-group margin-nol">
                <label class="d-block d-md-none">Adjustment</label>
                <input type="text" class="form-control text-right bg-light adj-qty-output"
                    id="adjQty${n}" name="adjQty[]" value="0" readonly tabindex="-1" />
            </div></div>

            <div class="col-md-1 col-12"><div class="form-group margin-nol">
                <input type="text" class="form-control tombol-panah"
                    id="notesRow${n}" name="notesRow[]" maxlength="150"
                    value="${esc(d.notes)}"
                    data-type-el-kiri="input" data-nama-el-kiri="balanceQty" />
            </div></div>

            <div class="col-md-1 col-12"><div class="form-group margin-nol text-center">
                <a class="btn-del-row" style="cursor:pointer">
                    <i data-feather="trash-2" class="feather-24 text-danger"></i>
                </a>
            </div></div>

        </div>
    </div>`;
}


/* ══════════════════════════════════════════════════════════════════════
   5. TAMBAH BARIS
══════════════════════════════════════════════════════════════════════ */

/**
 * Sisipkan banyak baris sekaligus dalam SATU operasi DOM.
 *
 * @param  {Array}  list  array of row-data (lihat buildRowHtml)
 * @param  {Object} opts  { eager: bool } — eager = select2 langsung di-init
 * @return {Array}        [{ n, articleCode }] untuk bulk stock fetch
 */
function appendRows(list, opts) {
    opts = opts || {};
    let html = '';
    const meta = [];

    list.forEach(function (d) {
        rowSeq++;
        html += buildRowHtml(rowSeq, d);
        meta.push({ n: rowSeq, data: d });
    });

    document.getElementById('article_row').insertAdjacentHTML('beforeend', html);

    meta.forEach(function (m) {
        const n = m.n, d = m.data;

        // Nilai eksak disimpan terpisah dari tampilan — lihat getBalance().
        $stockBefore(n).data('raw', parseNum(d.stockBefore));

        if (d.balance !== null && d.balance !== undefined && d.balance !== '') {
            $balance(n).data('raw', Math.max(0, parseNum(d.balance)))
                       .data('typed', false)
                       .data('autofilled', false);
        }

        if (d.uom) $uom(n).val(d.uom);

        if (opts.eager) initSelect2(n);
        else            bindLazySelect2(n);

        recomputeRow(n);
    });

    return meta.map(m => ({ n: m.n, articleCode: m.data.articleCode }));
}

function initSelect2(n) {
    const $sel = $article(n);
    if ($sel.hasClass('select2-hidden-accessible')) return;

    const current = $sel.val();
    $sel.off('.lazyInit');
    $sel.html('<option value=""></option>' + dataArticle);
    if (current) $sel.val(current);
    $sel.select2({ width: '100%', placeholder: 'Pilih artikel...' });
}

/**
 * select2 baru di-init saat select-nya disentuh.
 *
 * Untuk dokumen hasil import ribuan baris, meng-init select2 penuh per baris
 * saat render bikin browser menggantung — daftar artikel di-clone sebanyak
 * jumlah barisnya.
 */
function bindLazySelect2(n) {
    $article(n).on('mousedown.lazyInit focus.lazyInit', function () { initSelect2(n); });
}

/** Tambah 1 baris kosong — dipanggil tombol Add Article. */
function add_new_row() {
    if (!$('#adjDate').val()) {
        Swal.fire({ toast:true, position:'top-end', icon:'warning',
            title:'Isi Adjustment Date terlebih dahulu.', timer:1500, showConfirmButton:false });
        return;
    }
    if (dataArticle === null) {
        Swal.fire({ toast:true, position:'top-end', icon:'info',
            title:'Memuat daftar artikel...', timer:1200, showConfirmButton:false });
        whenArticlesReady(add_new_row);
        return;
    }

    appendRows([{}], { eager: true });
    feather.replace();
    hitungGrandTotal();
}

/**
 * Tambah 1 baris terisi (dipakai halaman edit).
 * Signature dipertahankan supaya pemanggil lama tetap jalan.
 */
function add_new_row_edit(articleCode, balanceValue, uom, uomMember, notes, stockBeforeVal, opts) {
    opts = opts || {};

    const meta = appendRows([{
        articleCode: articleCode,
        balance:     balanceValue,
        uom:         uom,
        uomMember:   uomMember,
        notes:       notes,
        stockBefore: stockBeforeVal
    }], { eager: !opts.lazySelect });

    if (articleCode && $('#location').val() && !opts.skipFetch) {
        fetchStockBefore(articleCode, $('#location').val(), meta[0].n);
    }
    if (!opts.skipFeather) feather.replace();
    hitungGrandTotal();

    return meta[0].n;
}


/* ══════════════════════════════════════════════════════════════════════
   6. STOCK BEFORE

   Selalu ikutkan adjDate — Stock Before dihitung dari saldo HISTORIS pada
   tanggal adjustment, bukan saldo current, supaya cocok dengan yang dipakai
   server saat posting.
══════════════════════════════════════════════════════════════════════ */

function fetchStockBefore(articleCode, locationCode, rowId) {
    const adjDate = $('#adjDate').val();

    if (!articleCode || !locationCode) { setStockBefore(rowId, 0); return; }

    if (!adjDate) {
        setStockBefore(rowId, 0);
        Swal.fire({ toast:true, position:'top-end', icon:'warning',
            title:'Isi Adjustment Date terlebih dahulu agar Stock Before akurat.',
            timer:2000, showConfirmButton:false });
        return;
    }

    $.ajax({
        url:    "{{ route('stockAdjustment.stockBefore') }}",
        method: "GET",
        data:   {
            article_code:  articleCode,
            location_code: locationCode,
            adjDate:       adjDate,
            adjCode:       adjRevisionCode   // null di jalur create
        },
        success: function (data) { setStockBefore(rowId, data.stock ?? 0); },
        error:   function ()     { setStockBefore(rowId, 0); }
    });
}

function setStockBefore(rowId, stock) {
    const v = parseNum(stock);
    $stockBefore(rowId).val(humanizeNumber(v)).data('raw', v);

    // Prefill saldo akhir = stok saat ini kalau user belum isi apa pun,
    // supaya default adjustment = 0 dan tinggal diedit kalau memang beda.
    const $b = $balance(rowId);
    if ($b.val() === '' || $b.data('autofilled')) {
        setBalance(rowId, v, { autofilled: true });
    }
    recomputeRow(rowId);
}

/**
 * Ambil Stock Before untuk BANYAK baris sekaligus, dipecah per chunk.
 *
 * @param {Array}    rowMeta  [{ n, articleCode }]
 * @param {Function} done     dipanggil setelah semua chunk selesai
 * @param {Function} onProg   opsional (selesai, totalChunk)
 *
 * Callback `done` sengaja dipisah: versi lama memanggil _finishImport()
 * langsung dari dalam sini, jadi fungsinya tidak bisa dipakai ulang di luar
 * import — memakainya saat ganti Location akan menutup Swal dan mereset
 * input file tanpa sebab.
 */
function fetchStockBulk(rowMeta, done, onProg) {
    done = done || function () {};

    const adjDate = $('#adjDate').val();
    const locCode = $('#location').val();

    if (!adjDate || !locCode || !rowMeta || rowMeta.length === 0) { done({}); return; }

    const codes  = [...new Set(rowMeta.map(m => m.articleCode).filter(Boolean))];
    const chunks = [];
    for (let i = 0; i < codes.length; i += STOCK_CHUNK) {
        chunks.push(codes.slice(i, i + STOCK_CHUNK));
    }
    if (chunks.length === 0) { done({}); return; }

    const stockMap = {};
    let finished = 0;

    if (onProg) onProg(0, chunks.length);

    (function runChunk(ci) {
        if (ci >= chunks.length) {
            applyStockBulk(rowMeta, stockMap);
            done(stockMap);
            return;
        }
        $.ajax({
            url:    "{{ route('stockAdjustment.stockBeforeBulk') }}",
            method: "POST",
            data:   {
                adjDate:           adjDate,
                location_code:     locCode,
                adjCode:           adjRevisionCode,
                'article_codes[]': chunks[ci]
            },
            success: function (data) { Object.assign(stockMap, data.stocks || {}); },
            error:   function () {
                // Satu chunk gagal → baris terkait tetap 0, proses jalan terus.
                console.error('Gagal memuat stock before untuk batch', ci);
            },
            complete: function () {
                finished++;
                if (onProg) onProg(finished, chunks.length);
                runChunk(ci + 1);
            }
        });
    })(0);
}

function applyStockBulk(rowMeta, stockMap) {
    rowMeta.forEach(function (m) {
        if (!Object.prototype.hasOwnProperty.call(stockMap, m.articleCode)) return;

        const v = parseNum(stockMap[m.articleCode]);
        $stockBefore(m.n).val(humanizeNumber(v)).data('raw', v);

        const $b = $balance(m.n);
        if ($b.val() === '' || $b.data('autofilled')) {
            setBalance(m.n, v, { autofilled: true });
        }
        recomputeRow(m.n);
    });
}

/**
 * Refresh Stock Before seluruh baris — dipakai saat Location / adjDate berubah.
 *
 * Versi lama memanggil fetchStockBefore() per baris: dokumen 500 baris = 500
 * request HTTP sekali ganti lokasi. Sekarang satu endpoint bulk.
 * Saldo akhir yang sudah diisi manual TIDAK ditimpa, hanya yang autofilled.
 */
function refreshStockOnRows(done) {
    const rowMeta = rowIds()
        .map(n => ({ n: n, articleCode: $article(n).val() }))
        .filter(m => m.articleCode);

    if (rowMeta.length === 0) { if (done) done({}); return; }

    fetchStockBulk(rowMeta, function (map) {
        hitungGrandTotal();
        if (done) done(map);
    });
}


/* ══════════════════════════════════════════════════════════════════════
   7. RECOMPUTE
══════════════════════════════════════════════════════════════════════ */

function recomputeRow(n) {
    const diff = roundQty(getBalance(n) - getStockBefore(n));

    $adjQty(n)
        .val((diff > 0 ? '+' : '') + humanizeNumber(diff))
        .removeClass('adj-positive adj-negative')
        .addClass(diff > 0 ? 'adj-positive' : (diff < 0 ? 'adj-negative' : ''));

    $balance(n).toggleClass('balance-changed', !isZero(diff));
    return diff;
}

function recomputeAllRows() { rowIds().forEach(recomputeRow); }

function hitungGrandTotal() {
    $('#totalRow').val($('#article_row .tanda-baris').length);
}


/* ══════════════════════════════════════════════════════════════════════
   8. EVENT DELEGATION

   Semua listener dipasang SEKALI di container. Versi lama membind sebagian
   per baris dan sebagian lagi lewat onclick inline di markup, jadi baris
   hasil import massal diam-diam kehilangan perilaku tertentu — mis.
   select-on-click yang dibind pakai $("input[type='text']").on(...) saat
   page load hanya kena input yang sudah ada waktu itu.
══════════════════════════════════════════════════════════════════════ */

$(function () {
    const $c = $('#article_row');

    /* Saldo akhir diketik */
    $c.on('input keyup', '.balance-input', function () {
        const n = $(this).attr('id').replace('balanceQty', '');
        const v = $(this).val().replace(/-/g, '');       // saldo tidak boleh negatif
        if ($(this).val() !== v) $(this).val(v);

        $(this).data('typed', true).data('autofilled', false);
        recomputeRow(n);
        hitungGrandTotal();
    });

    /* Artikel dipilih */
    $c.on('change', 'select.article-select', function () {
        const n       = $(this).attr('id').replace('articleId', '');
        const artCode = $(this).val();
        const $opt    = $(this).find(':selected');

        $uom(n).html(buildUomOptions($opt.data('uom-member'), $opt.data('uom')))
               .val($opt.data('uom')).trigger('change');

        $balance(n).val('').removeData('raw').data('typed', false).data('autofilled', true);
        fetchStockBefore(artCode, $('#location').val(), n);

        setTimeout(() => { $balance(n).focus().select(); }, 10);
    });

    /* Hapus baris */
    $c.on('click', '.btn-del-row', function () {
        $(this).closest('.tanda-baris').remove();
        hitungGrandTotal();
    });

    /* Select-on-click, termasuk baris yang ditambah belakangan */
    $c.on('click', "input[type='text']:not([readonly])", function () { $(this).select(); });

    /* Tanggal adjustment berubah → seluruh Stock Before historis ikut geser */
    const $dp = $('#adjDate');
    if ($dp.length) {
        $dp.flatpickr({
            dateFormat: 'd-m-Y',
            onChange: function () { refreshStockOnRows(); }
        });
        // Fallback kalau value diubah tanpa lewat flatpickr
        $dp.on('change', function () { refreshStockOnRows(); });
    }
});


/* ══════════════════════════════════════════════════════════════════════
   9. KUMPULKAN BARIS

   Satu tempat untuk membaca grid. simpanData() memakainya, dan halaman edit
   memakainya juga untuk membangun preview dampak revisi — supaya keduanya
   tidak bisa berbeda pendapat soal baris mana yang terkirim.
══════════════════════════════════════════════════════════════════════ */

/**
 * @return {Object} { articles, skipped, errors, seen }
 *   articles : payload siap kirim (baris berselisih 0 sudah dibuang)
 *   skipped  : [{ code, label }] baris yang selisihnya 0
 *   seen     : { article_code: label } semua artikel yang ada di grid
 */
function collectRows() {
    const articles = [];
    const skipped  = [];
    const errors   = [];
    const seen     = {};

    rowIds().forEach(function (n) {
        const artCode = $article(n).val();
        if (!artCode) return;

        const label = $article(n).find(':selected').text() || artCode;
        seen[artCode] = label;

        const balance = getBalance(n);
        if (balance < 0) {
            errors.push(`Saldo akhir untuk <b>${esc(label)}</b> tidak boleh negatif.`);
            return;
        }

        // Dibulatkan SEBELUM dibanding nol. Tanpa ini, baris yang tampil "0"
        // bisa punya selisih 1e-15 dan tetap terkirim sebagai adjustment —
        // lalu ditolak server dengan "Qty tidak boleh 0" yang membingungkan.
        const diff = roundQty(balance - getStockBefore(n));

        if (isZero(diff)) { skipped.push({ code: artCode, label: label }); return; }

        articles.push({
            article_code:   artCode,
            uom:            $uom(n).val(),
            direction:      diff > 0 ? '+' : '-',
            stock_before:   getStockBefore(n),
            qty_adjustment: Math.abs(diff),
            stock_after:    balance,
            notes:          $notes(n).val()
        });
    });

    return { articles, skipped, errors, seen };
}


/* ══════════════════════════════════════════════════════════════════════
   10. SAVE
══════════════════════════════════════════════════════════════════════ */

simpanData = (oEdit) => {
    const errors = [];

    if (!$('#adjDate').val())  errors.push('Adjustment Date harus diisi.');
    if (!$('#adjType').val())  errors.push('Adjustment Type harus dipilih.');
    if (!$('#location').val()) errors.push('Location harus dipilih.');
    if (!$('#periode').val())  errors.push('Periode harus dipilih.');

    if (errors.length) {
        Swal.fire({ title:'Validation Error', html: errors.join('<br>'), icon:'warning' });
        return;
    }

    const res = collectRows();
    const all = res.errors.slice();

    if (res.articles.length === 0) {
        if (adjRevisionCode) {
            all.push('Semua artikel jadi selisih 0. Kalau maksudnya membatalkan seluruh '
                   + 'adjustment ini, gunakan <strong>Cancel</strong> dari halaman list — '
                   + 'bukan revisi kosong.');
        } else {
            all.push(res.skipped.length > 0
                ? 'Tidak ada artikel yang saldonya berubah. Ubah saldo akhir minimal 1 artikel.'
                : 'Artikel harus diisi.');
        }
    }

    if (all.length) {
        Swal.fire({ title:'Validation Error', html: all.join('<br>'), icon:'warning' });
        return;
    }

    const url = oEdit ? "{{ route('stockAdjustment.update') }}"
                      : "{{ route('stockAdjustment.store')  }}";

    $('#cmdSave').attr('disabled', 'disabled');

    $.ajax({
        type: "POST",
        url:  url,
        data: {
            // #adjCode & #location bisa disabled; .val() tetap mengembalikan nilainya.
            adjCode:     $('#adjCode').val(),
            adjDate:     $('#adjDate').val(),
            adjType:     $('#adjType').val(),
            periode:     $('#periode').val(),
            location:    $('#location').val(),
            description: $('#description').val(),
            note:        $('#note').length ? $('#note').val() : null,
            articles:    JSON.stringify(res.articles),
            reason:      adjReviseReason,   // null di jalur draft — diabaikan controller
        },
        dataType: "json",
        success: function (data) {
            if (data.status == 0) {
                data.message.forEach(m => show_msg(data.title, m, data.alert));
                if (typeof onSaveFailed === 'function') onSaveFailed(data);
                return;
            }
            show_msg(data.title, data.message, data.alert);
            $('#adjCode').val(data.adjCode);
            $('#oEdit').val(data.oEdit);

            if (typeof onSaveSuccess === 'function') { onSaveSuccess(data, oEdit); return; }
            if (!oEdit) window.location.href = "{{ route('stockAdjustment.create') }}";
        },
        error: function (err) {
            console.error(err);
            Swal.fire('Error..', 'Gagal menyimpan. Cek koneksi lalu coba lagi.', 'error');
            if (typeof onSaveFailed === 'function') onSaveFailed(null);
        },
        complete: function () {
            $('#cmdSave').removeAttr('disabled');
        }
    });
};


/* ══════════════════════════════════════════════════════════════════════
   11. IMPORT EXCEL

   Ringan karena: HTML dibangun per BATCH lalu di-insert sekali per batch;
   select2 lazy (baru init saat select disentuh); listener lewat delegation
   jadi tidak ada bind per baris.

   `stock_before` dari controller SELALU 0 — nilai historis yang benar
   (termasuk untuk backdate) diambil setelah semua baris ter-render, lewat
   satu endpoint bulk untuk semua artikel sekaligus.
══════════════════════════════════════════════════════════════════════ */

function importRowsFast(rows) {
    const total = rows.length;
    let idx = 0;
    let rowMeta = [];

    function processBatch() {
        const end   = Math.min(idx + IMPORT_BATCH, total);
        const batch = [];

        for (; idx < end; idx++) {
            const r = rows[idx];
            batch.push({
                articleCode: r.article_code,
                balance:     r.qty_adjustment,   // dari Excel = saldo akhir yang dituju
                uom:         r.uom,
                uomMember:   r.uom_member,
                notes:       r.notes,
                stockBefore: r.stock_before      // placeholder 0, ditimpa bulk fetch
            });
        }

        rowMeta = rowMeta.concat(appendRows(batch, { eager: false }));

        if (Swal.isVisible()) {
            Swal.getHtmlContainer().innerHTML = `<b>${idx}/${total}</b> baris dimuat`;
        }

        if (idx < total) {
            requestAnimationFrame(processBatch);   // beri napas ke browser
            return;
        }

        feather.replace();
        hitungGrandTotal();

        fetchStockBulk(rowMeta, finishImport, function (done, chunks) {
            if (Swal.isVisible()) {
                Swal.getHtmlContainer().innerHTML =
                    `Memuat stock before (${done}/${chunks} batch)...`;
            }
        });
    }

    whenArticlesReady(function () {
        Swal.fire({
            title: "Importing...",
            html:  `<b>0/${total}</b> baris dimuat`,
            icon:  "info",
            showConfirmButton: false,
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); processBatch(); }
        });
    });
}

function finishImport() {
    $('#uploadExcel').removeAttr('disabled');
    $(".loading-spinner-container").removeClass("-show");
    Swal.close();
    if (typeof clearFileInput === 'function') clearFileInput('file');
}


/* ══════════════════════════════════════════════════════════════════════
   12. ALIAS BACK-COMPAT — nama lama yang mungkin masih dipanggil blade lain
══════════════════════════════════════════════════════════════════════ */

const _fetchStockBeforeBulk = fetchStockBulk;
const _finishImport         = finishImport;
const _buildUomOptions      = buildUomOptions;
const _escapeAttr           = esc;

</script>