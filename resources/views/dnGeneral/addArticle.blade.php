<style>
    #article_row .form-group { margin-bottom: 0.5rem; }
    /* Feedback visual: input manual selalu tampil uppercase */
    #article_row input[name="uomManual[]"] { text-transform: uppercase; }
    /* Search box select2 (other) juga uppercase */
    .select2-search__field { text-transform: uppercase; }
</style>

{{-- Template row (hidden, untuk di-clone) --}}
<div id="new_row" class="d-none">
    <div class="tanda-baris">
        <div class="form-row d-flex align-items-center">
            {{-- col-md-6: select artikel --}}
            <div class="col-md-6 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none">Article</label>
                    <select class="form-control article-count" name="articleCode[]"></select>
                </div>
            </div>
            {{-- col-md-2: stok gudang (selalu tampil, semua type) --}}
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none">Stok</label>
                    <span class="form-control"
                          style="background:#f8f8f8;font-size:.85rem;line-height:2.2;">
                        <span class="stok-label">Stok:</span>
                        <span class="stok-angka">-</span>
                    </span>
                </div>
            </div>
            {{-- col-md-2: qty --}}
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none">QTY</label>
                    <input type="text" class="form-control numeral-mask text-right"
                           name="qtyOrder[]" maxlength="9" placeholder="Qty" />
                </div>
            </div>
            {{-- col-md-1: UOM --}}
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none">UOM</label>
                    {{-- UOM auto (rm/ot): span read-only --}}
                    <span class="form-control uom-val" name="uom[]"
                          style="background:#f8f8f8;">-</span>
                    {{-- UOM editable (other): input, hidden default --}}
                    <input type="text" name="uomManual[]"
                           class="form-control uom-input d-none"
                           placeholder="UOM" style="padding:.3rem .5rem;" />
                </div>
            </div>
            {{-- col-md-1: hapus --}}
            <div class="col-md-1 col-12">
                <div class="form-group text-center">
                    <a style="cursor:pointer"
                       onclick="$(this).closest('.tanda-baris').remove(); recordCount(); disabledEnabledSelect2();">
                        <i data-feather="trash-2" class="feather-24 text-danger"></i>
                    </a>
                </div>
            </div>
        </div>
        <hr class="d-block d-md-none" />
    </div>
</div>

<script type="text/javascript">
    var cloneCount  = 0;
    var dataArticle = '';
    var objCustomer = $('#cust');

    var $deliveryDate = $('#deliveryDate');
    if ($deliveryDate.length) {
        $deliveryDate.flatpickr({ dateFormat: 'd-m-Y' });
    }

    function disabledEnabledSelect2() {
        var records = $('#article_row .tanda-baris').length;
        if (records > 0) {
            objCustomer.attr('disabled', 'disabled');
            $('#type').attr('disabled', 'disabled');
        } else {
            objCustomer.removeAttr('disabled');
            $('#type').removeAttr('disabled');
        }
        recordCount();
    }

    function recordCount() {
        $('#records').text($('#article_row .tanda-baris').length);
    }

    // ── Build option HTML (qty & uom sebagai attribute biasa, bukan data-*) ───
    function buildOptionHtml(options) {
        var html = '<option value="">-- Pilih Artikel --</option>';
        options.forEach(function(item) {
            var label = (item.alt_code ? item.alt_code + ' - ' : '') + (item.name || '');
            html += '<option'
                  + ' value="'    + escAttr(item.code || '') + '"'
                  + ' qty="'      + escAttr(item.qty !== null && item.qty !== undefined ? String(item.qty) : '') + '"'
                  + ' uom_val="'  + escAttr(item.uom  || '') + '"'
                  + ' desc_val="' + escAttr(item.name || '') + '"'
                  + ' alt_val="'  + escAttr(item.alt_code || '') + '">'
                  + label
                  + '</option>';
        });
        return html;
    }

    function escAttr(str) {
        return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function setArticleOptions(options) {
        articleOptions = options;
        dataArticle    = buildOptionHtml(options);
    }

    // ── Toggle header UOM label (opsional, dipanggil dari create.blade) ────────
    function applyTypeToRows(type) {
        // Tidak ada perubahan struktur kolom — semua type punya kolom stok + uom.
        // Hanya UOM yang berubah: span (rm/ot) vs input (other).
    }

    // ── Validasi qty vs stok gudang ───────────────────────────────────────────
    // Artikel manual (data-manual="1") dikecualikan — tidak punya stok gudang.
    var _qtyToastTimer = null;
    function validateQtyStock($input) {
        var $row     = $input.closest('.tanda-baris');
        var isManual = $row.attr('data-manual') === '1';
        var qty      = parseFloat(($input.val() || '0').replace(/,/g, '')) || 0;
        var stock    = parseFloat($row.attr('data-stock') || '0') || 0;

        // Manual tidak divalidasi
        if (isManual) {
            $input.css('background-color', '');
            $row.removeAttr('data-overstock');
            return true;
        }

        if (qty > stock) {
            // Lebihi stok → merah + toast (throttle agar tidak spam)
            $input.css('background-color', '#f8d7da');
            $row.attr('data-overstock', '1');
            if (_qtyToastTimer) clearTimeout(_qtyToastTimer);
            _qtyToastTimer = setTimeout(function() {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Qty (' + qty.toLocaleString('en-US') +
                                 ') melebihi stok gudang (' + stock.toLocaleString('en-US') + ')',
                                 'Stok Tidak Cukup');
                }
            }, 400);
            return false;
        }

        // Valid
        $input.css('background-color', '');
        $row.removeAttr('data-overstock');
        return true;
    }

        // ── Tambah baris baru ─────────────────────────────────────────────────────
    add_new_row = function() {
        if (!currentType) {
            Swal.fire('Warning', 'Pilih Type terlebih dahulu.', 'warning');
            return;
        }
        if (!currentCust) {
            Swal.fire('Warning', 'Pilih Customer / Supplier terlebih dahulu.', 'warning');
            return;
        }

        cloneCount++;
        var $clone  = $('#new_row').clone();
        $clone.removeAttr('id').removeClass('d-none');

        var $select = $clone.find('select[name="articleCode[]"]');
        $select.attr('id', 'articleCode' + cloneCount);
        $clone.find('input[name="qtyOrder[]"]').attr('id', 'qtyOrder' + cloneCount);

        // UOM: rm/ot pakai span auto; other pakai input editable
        if (currentType === 'other') {
            $clone.find('.uom-val').addClass('d-none');
            $clone.find('.uom-input').removeClass('d-none');
        } else {
            $clone.find('.uom-val').removeClass('d-none');
            $clone.find('.uom-input').addClass('d-none');
        }

        $('#article_row').append($clone);

        // ── Init Select2 ─────────────────────────────────────────────────────
        if (currentType === 'other') {
            $select.html(dataArticle);
            $select.select2({
                placeholder : '-- Cari atau ketik artikel --',
                tags        : true,
                createTag   : function(params) {
                    var term = $.trim(params.term).toUpperCase();   // paksa uppercase
                    if (!term) return null;
                    return { id: 'OTHER', text: term, newTag: true };
                },
                templateResult: function(item) {
                    if (!item.id) return item.text;
                    if (item.newTag) {
                        return $('<span>'
                            + '<span class="badge badge-warning mr-50">MANUAL</span>'
                            + 'Gunakan <strong>"' + $('<span>').text(item.text).html() + '"</strong> sebagai deskripsi'
                            + '</span>');
                    }
                    return $('<span>' + item.text + '</span>');
                },
                insertTag: function(data, tag) { data.unshift(tag); }
            });

            $select.on('select2:select', function(e) {
                var item      = e.params.data;
                var $row      = $(this).closest('.tanda-baris');
                var $uomInput = $row.find('input[name="uomManual[]"]');
                var $qtyInput = $row.find('input[name="qtyOrder[]"]');

                if (item.newTag) {
                    // Manual: stok = 0, UOM kosong. Tandai manual agar TIDAK divalidasi stok.
                    $row.find('.stok-angka').text('0');
                    $row.attr('data-stock', '0');
                    $row.attr('data-manual', '1');
                    $uomInput.val('');
                } else {
                    // Dari daftar gudang: ambil stok & uom dari attribute
                    var qty = item.element ? $(item.element).attr('qty')     : '';
                    var uom = item.element ? $(item.element).attr('uom_val') : '';
                    var stockNum = parseFloat((qty || '0').replace(/,/g, '')) || 0;
                    $row.find('.stok-angka').text(qty !== '' && qty != null ? qty : '0');
                    $row.attr('data-stock', stockNum);
                    $row.attr('data-manual', '0');
                    $uomInput.val(uom || '');
                }
                $qtyInput.val('').focus();
                validateQtyStock($qtyInput);
            });

        } else {
            // rm / ot
            $select.html(dataArticle);
            $select.select2({ placeholder: '-- Pilih Artikel --' });

            $select.on('change', function() {
                var $row    = $(this).closest('.tanda-baris');
                var $selOpt = $(this).find('option:selected');
                var qty = $selOpt.attr('qty')     || '-';
                var uom = $selOpt.attr('uom_val') || '-';
                $row.find('.stok-angka').text(qty);
                $row.find('.uom-val').text(uom);
                // Simpan stok numeric di baris untuk validasi qty
                var stockNum = parseFloat(($selOpt.attr('qty') || '0').replace(/,/g, '')) || 0;
                $row.attr('data-stock', stockNum);
                var $qtyInput = $row.find('input[name="qtyOrder[]"]');
                $qtyInput.val('').focus();
                validateQtyStock($qtyInput);
            });
        }

        // Validasi qty saat user mengetik (berlaku untuk baris ini)
        $clone.find('input[name="qtyOrder[]"]').on('input keyup change', function() {
            validateQtyStock($(this));
        });

        // Paksa UOM manual jadi uppercase saat diketik (type other)
        $clone.find('input[name="uomManual[]"]').on('input', function() {
            var pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });

        mask_thousand();
        recordCount();
        disabledEnabledSelect2();
        feather.replace();
    };

    // ── Collect & validasi ────────────────────────────────────────────────────
    function collectArticles() {
        var articles = [], flag = 0, pesan = '';

        $('#article_row .tanda-baris').each(function() {
            var $row    = $(this);
            var $sel    = $row.find('select[name="articleCode[]"]');
            var selData = $sel.select2('data')[0];

            var code = selData ? $.trim(selData.id)   : '';
            var name = selData ? $.trim(selData.text) : '';

            var uom;
            if (currentType === 'other') {
                uom = $row.find('input[name="uomManual[]"]').val().trim();
            } else {
                uom = $row.find('.uom-val').text().trim();
            }

            var qty = $row.find('input[name="qtyOrder[]"]').val().replace(/,/g, '') || 0;

            if (!code) {
                pesan += 'Kode artikel tidak boleh kosong.<br>';
                flag = 1; return;
            }
            if (Number(qty) <= 0) {
                pesan += 'QTY artikel <b>' + (currentType === 'other' ? name : code) + '</b> tidak boleh 0.<br>';
                flag = 1; return;
            }

           

            // Duplikat: other yang manual (kode OTHER) cek pakai deskripsi;
            // other yang dari daftar (kode asli) cek pakai kode
            var isManual = (code === 'OTHER');
            var dupKey   = isManual ? name : code;
            var dup = $.grep(articles, function(o) {
                return isManual ? (o.article_code === 'OTHER' && o.article_name === dupKey)
                                : (o.article_code === dupKey);
            })[0];
            if (dup) {
                pesan += 'Artikel <b>' + dupKey + '</b> dimasukkan lebih dari sekali.<br>';
                flag = 1; return;
            }

            articles.push({
                article_code : code,
                article_name : name,
                qty          : qty,
                uom          : uom
            });
        });

        if (articles.length === 0) {
            pesan += 'Artikel harus diisi minimal satu baris.<br>';
            flag = 1;
        }

        return { articles: articles, flag: flag, pesan: pesan };
    }
</script>