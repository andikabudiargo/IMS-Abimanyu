@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="receiving-index">
  <div class="card">
    <div class="card-header">  
      <h4 class="card-title">Filter <small class="text-muted"> {{ $lockDate ? "Locked From : ".$lockDate : '' }}</small></h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <form class="needs-validation" novalidate>
            <div class="form-row">
              <div class="form-group col-md-3"> 
                <label for="searchRec">Rec Number</label>
                <input type="text" class="form-control text-uppercase" id="searchRec" name="searchRec" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchPo">PO Number</label>
                <input type="text" class="form-control text-uppercase" id="searchPo" name="searchPo" placeholder=""  />
              </div>
              <div class="form-group col-md-3"> 
                <label for="searchInv">Invoice Number</label>
                <input type="text" class="form-control text-uppercase" id="searchInv" name="searchInv" placeholder=""  />
              </div>
               <div class="form-group col-md-3">
                <label class="form-label" for="recType">Receive Type</label>
                                    <select class="select2 form-control" id="recType" name="recType" required>
                                       <option value="">All</option>
                                        <option value="NORMAL">Purchase Order</option>
                                        <option value="NP">Non Purchase</option>
                                         <option value="TRIAL">Trial & Project</option>
                                        <option value="JASA">Jasa</option>
                                    </select>
                                </div>
            </div>
             <div class="form-row">
            <div class="form-group col-md-3"> 
                <label class="form-label" for="searchSupplier">Supplier</label>
                <select class="select2 form-control" id="searchSupplier" name="searchSupplier">
                    <option value="">All</option>
                    @foreach($supps as $val)
                        <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-3 form-group">
                <label for="recDate">Receiving Date</label>
                <input type="text" id="recDate" name="recDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="col-md-3 form-group">
                <label for="doDate">DO Date</label>
                <input type="text" id="doDate" name="doDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-3"> 
                <label class="form-label" for="searchStatus">Rec Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}">{{ $val }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @can('receiving-create')
                    <a href="{{ route('receiving.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                    @endcan
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</section>
<section id="table-receiving">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title"> @yield('title') List</h4>
      <div class="heading-elements">
          <ul class="list-inline mb-0">
              <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
              <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
          </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <button type="button" class="btn btn-primary" id ="btnDetail" name="btnDetail" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data detail">Detail</button>
        <button type="button" class="btn btn-primary" id ="btnSummary" name="btnSummary" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data summary">Summary</button>
        <div class="row">
            <div class="col-sm-12">
              <div class="table-responsive">
                <table id="detailedTable" class="table mb-0">
                  <thead class="thead-light">
                  </thead>
                </table>
              </div>
            </div>
        </div>  
      </div>
    </div>
  </div>
</section>
@include('partials.delete-modal')
@include('receiving.expired-date-modal')
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">
  let searchRec = $("#searchRec");
  let searchPo = $("#searchPo");
  let searchInv = $("#searchInv");
  let searchSupplier = $("#searchSupplier"); 
  let searchStatus = $("#searchStatus");
  let recType = $("#recType");   // <-- tambahan
  let recDate = $("#recDate");
  let doDate = $("#doDate");
  let btnSummary = $('#btnSummary');
  let btnDetail = $('#btnDetail');

  $(document).ready(function(){    
    let href;
    $(document).on('click', '#cancelReasonButton', function(event) {
        event.preventDefault();
        href = $(this).data('href');
        $('#modalReasonCancel').attr("action", href);
    });

    btnSummary.hide();
    btnDetail.hide();

  });

  //refresh di cards
  $('a[data-action="reload"]').on('click', function () {
    btnSummary.hide();
    btnDetail.show();
    showList(searchRec.val(),searchPo.val(),searchInv.val(),searchSupplier.val(),searchStatus.val(),recDate.val(),doDate.val(),recType.val());
  });

  rangePickr = $('.flatpickr-range');
  if (rangePickr.length) {
    rangePickr.flatpickr({
      dateFormat: "d-m-Y",
      mode: 'range'
    });
  }

  $("#btnSearch").click(function(e){
    btnSummary.hide();
    btnDetail.show();
    showList(searchRec.val(),searchPo.val(),searchInv.val(),searchSupplier.val(),searchStatus.val(),recDate.val(),doDate.val(),recType.val());
  });

  btnSummary.click(function(e){
    btnSummary.hide();
    btnDetail.show();
    showList(searchRec.val(),searchPo.val(),searchInv.val(),searchSupplier.val(),searchStatus.val(),recDate.val(),doDate.val(),recType.val());
  });

  btnDetail.click(function(e){
    btnSummary.show();
    btnDetail.hide();
    showListDetail(searchRec.val(),searchPo.val(),searchInv.val(),searchSupplier.val(),searchStatus.val(),recDate.val(),doDate.val(),recType.val());
  });

  const showList = (searchRec,searchPo,searchInv,searchSupplier,searchStatus,recDate,doDate,recType) => {
  if ($('#detailedTable tr').length >0){
      let table= $('#detailedTable').DataTable();
      table.destroy();
      $('#detailedTable tbody > tr').remove();
      $("#detailedTable thead > tr").remove();
  }
  showDataTables({
    tableId:"detailedTable",
    route:"{{ route('receiving.list') }}",
    kolom:{!! $kolom !!},
    type:'POST',
    arrColPrint:[1,2,3,4,5,6,7,8,9,10,11,15,16],
    columnDefs :[
      { width: '5%', targets: 0 }
    ],
    dataSearch:  {
      searchRec:searchRec,
      searchPo:searchPo,
      searchInv:searchInv,
      searchSupplier:searchSupplier,
      searchStatus:searchStatus,
      recDate:recDate,
      doDate:doDate,
      recType:recType          // <-- tambahan
    },
    orderColumn:[[ 4, 'desc' ]],
    excelFileName:'receiving'
  });
}

const showListDetail = (searchRec,searchPo,searchInv,searchSupplier,searchStatus,recDate,doDate,recType) => {
  if ($('#detailedTable tr').length >0){
      let table= $('#detailedTable').DataTable();
      table.destroy();
      $('#detailedTable tbody > tr').remove();
      $("#detailedTable thead > tr").remove();
  }
  showDataTables({
    tableId:"detailedTable",
    route:"{{ route('receiving.list.detail') }}",
    kolom:{!! $kolomDetail !!},
    type:'POST',
    arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,25,26],
    columnDefs :[
      { width: '5%', targets: 0 },
      {
        targets: [ 12,13,15,16,17,18,19 ],
        render: $.fn.dataTable.render.number(',', '.',2, ''),
        className: "text-right"
      },
    ],
    dataSearch:  {
      searchRec:searchRec,
      searchPo:searchPo,
      searchInv:searchInv,
      searchSupplier:searchSupplier,
      searchStatus:searchStatus,
      recDate:recDate,
      doDate:doDate,
      recType:recType          // <-- tambahan
    },
    orderColumn:[[ 2, 'desc' ]],
    excelFileName:'receiving_detail'
  });
}

  let href;
  $(document).on('click', '#revisionReasonButton', function(event) {
      event.preventDefault();
      href = $(this).data('href');
      $('#modalReasonRevision').attr("action", href);
  });

  // =========================================================
// CHEMICAL UNIT (Add Expired Date) — modal handler
// =========================================================
let chemicalUnitRecNumber = '';

function tpl(templateId, replacements) {
  let html = $(templateId).html();
  for (let key in replacements) {
    html = html.split('__' + key + '__').join(replacements[key]);
  }
  return html;
}

function loadChemicalUnitModal(recNumber) {
  chemicalUnitRecNumber = recNumber;

  $('#chemicalUnitRecNumber').text('Rec Number: ' + recNumber);
  $('#chemicalUnitGroups').empty();
  $('#chemicalUnitAlert').hide().text('');
  $('#btnSaveChemicalUnit').hide();
  $('#chemicalUnitLoading').show();

  $.ajax({
    url: "{{ route('receiving.chemicalUnitPreview') }}",
    type: 'POST',
    data: { rec_number: recNumber },
    success: function (res) {
      $('#chemicalUnitLoading').hide();

      if (res.status !== 1) {
        $('#chemicalUnitAlert').text(res.message).show();
        return;
      }

      let hasEditableRows = false;

      res.groups.forEach(function (group) {
       let groupHtml = tpl('#tplChemicalGroup', {
  RECEIVING_DET_ID: group.receiving_det_id,
  ARTICLE_CODE: group.article_code,
  ARTICLE_ALT_CODE: group.article_alternative_code || group.article_code,   // fallback kalau kosong
  ARTICLE_DESC: group.article_desc || '',
  MIN_PACKAGE: group.min_package || '-',
  TOTAL_QTY: group.total_qty_needed,
  ALLOCATED_QTY: group.allocated_qty,
  REMAINING_QTY: group.remaining_qty,
  UOM: group.uom || ''
});

        let $groupEl = $(groupHtml);
        let $rowsContainer = $groupEl.find('.chemical-unit-rows');

        if (group.error) {
          $rowsContainer.html('<div class="text-danger small">' + group.error + '</div>');
        } else if (!group.rows || group.rows.length === 0) {
          $rowsContainer.html('<div class="text-success small"><i data-feather="check"></i> Sudah lengkap</div>');
        } else {
          hasEditableRows = true;
          group.rows.forEach(function (row) {
            let rowHtml = tpl('#tplChemicalRow', {
              RECEIVING_DET_ID: group.receiving_det_id,
              UNIT_SEQUENCE: row.unit_sequence,
              QTY: row.qty,
              UOM: group.uom || ''
            });
            $rowsContainer.append(rowHtml);
          });
        }

        $('#chemicalUnitGroups').append($groupEl);
      });

      if (hasEditableRows) {
        $('#btnSaveChemicalUnit').show();
      } else {
        $('#chemicalUnitAlert').text('Tidak ada baris yang perlu diisi expired date.').show();
      }

      if (typeof feather !== 'undefined') feather.replace();
    },
    error: function () {
      $('#chemicalUnitLoading').hide();
      $('#chemicalUnitAlert').text('Gagal memuat data. Silakan coba lagi.').show();
    }
  });
}

$(document).on('click', '.chemical-unit-button', function () {
  let recNumber = $(this).data('rec-number');
  loadChemicalUnitModal(recNumber);
});

$('#btnSaveChemicalUnit').on('click', function () {
  let groups = [];
  let isValid = true;

  $('.chemical-unit-group').each(function () {
    let receivingDetId = $(this).data('receiving-det-id');
    let units = [];

    $(this).find('.chemical-unit-row').each(function () {
      let expiredDate = $(this).find('.input-expired-date').val();
      let printBarcode = $(this).find('.chk-print-barcode').is(':checked');

      if (!expiredDate) {
        isValid = false;
        $(this).find('.input-expired-date').addClass('is-invalid');
      } else {
        $(this).find('.input-expired-date').removeClass('is-invalid');
      }

      units.push({
        unit_sequence: $(this).data('unit-sequence'),
        qty: $(this).data('qty'),
        expired_date: expiredDate,
        print_barcode: printBarcode
      });
    });

    if (units.length > 0) {
      groups.push({
        receiving_det_id: receivingDetId,
        units: units
      });
    }
  });

  if (!isValid) {
    $('#chemicalUnitAlert').text('Semua kaleng wajib diisi Expired Date.').show();
    return;
  }

  if (groups.length === 0) {
    $('#chemicalUnitAlert').text('Tidak ada data untuk disimpan.').show();
    return;
  }

  $('#btnSaveChemicalUnit').prop('disabled', true).text('Menyimpan...');

  $.ajax({
  url: "{{ route('receiving.chemicalUnitStore') }}",
  type: 'POST',
  data: {
    rec_number: chemicalUnitRecNumber,
    groups: JSON.stringify(groups)
  },
  success: function (res) {
    $('#btnSaveChemicalUnit').prop('disabled', false).html('<i data-feather="save"></i> Simpan & Print');

 if (res.status === 1) {
  $('#chemicalUnitModal').modal('hide');
  showList(searchRec.val(), searchPo.val(), searchInv.val(), searchSupplier.val(), searchStatus.val(), recDate.val(), doDate.val(), recType.val());

  Swal.fire({
    title: 'Berhasil',
    text: res.message,
    icon: 'success',
    confirmButtonText: 'OK'
  }).then(() => {
    let unitsToPrint = (res.units || []).filter(u => u.print_barcode);
    if (unitsToPrint.length === 0) return;

    let unitIds = unitsToPrint.map(u => u.id);

   $.ajax({
  url: "{{ route('receiving.printChemicalUnitLabel') }}",
  type: 'POST',
  data: { unit_ids: unitIds },
  success: function (printRes) {
    if (printRes.status === 1) {
      doBrowserPrintChemical(printRes);
    } else {
      Swal.fire('Gagal Print', printRes.message, 'warning');
    }
  },
  error: function (xhr) {
    console.error(xhr.responseText);
    Swal.fire('Error Print', 'Gagal generate label. Cek console untuk detail.', 'error');
  }
});
  });
} else {
      $('#chemicalUnitAlert').text(res.message).show();
      Swal.fire({
        title: 'Gagal',
        text: res.message,
        icon: 'warning',
        confirmButtonText: 'OK'
      });
    }
  },
  error: function () {
    $('#btnSaveChemicalUnit').prop('disabled', false).html('<i data-feather="save"></i> Simpan & Print');
    $('#chemicalUnitAlert').text('Gagal menyimpan. Silakan coba lagi.').show();
    Swal.fire({
      title: 'Error',
      text: 'Gagal menyimpan. Silakan coba lagi.',
      icon: 'error',
      confirmButtonText: 'OK'
    });
  }
});
});

// Reset modal saat ditutup
$('#chemicalUnitModal').on('hidden.bs.modal', function () {
  $('#chemicalUnitGroups').empty();
  $('#chemicalUnitAlert').hide();
  $('#chemicalUnitLoading').hide();
  chemicalUnitRecNumber = '';
});

function doBrowserPrintChemical(data) {
    var labels    = data.labels;
    var printedBy = data.printed_by;
    var total     = labels.length;

    var labelsHtml = '';
    labels.forEach(function (lbl, i) {
        labelsHtml +=
            '<div class="label-card">' +
            '<div class="label-top">' +
            '<img class="label-qr" src="' + lbl.qr_url + '">' +
            '<div class="label-text">' +
            '<div class="label-altcode">' + lbl.alt_code + '</div>' +
            '<div class="label-exp">EXP: ' + lbl.expired_date + '</div>' +
            '<div class="label-desc">' + lbl.article_desc + '</div>' +
            '</div></div>' +
            '<div class="label-footer">Dicetak: ' + printedBy + ' &bull; ' + (i+1) + '/' + total + '</div>' +
            '</div>';
    });

    var html = '<!DOCTYPE html><html><head><meta charset="utf-8">' +
        '<title>Label Chemical Unit</title>' +
        '<style>' +
        '@page{margin:0;size:30mm 20mm;}' +
        '*{box-sizing:border-box;margin:0;padding:0;font-family:Arial,sans-serif;text-shadow:none;}' +
        'body{background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
        '.label-sheet{display:flex;flex-wrap:wrap;}' +
        '.label-card{width:30mm;height:20mm;border:0.3mm solid #000;padding:1mm 1.5mm;' +
            'display:flex;flex-direction:column;justify-content:space-between;' +
            'overflow:hidden;page-break-inside:avoid;}' +
        '.label-top{display:flex;align-items:center;gap:1.5mm;flex:1;min-height:0;overflow:hidden;}' +
        '.label-qr{width:10mm;height:10mm;flex-shrink:0;object-fit:contain;}' +
        '.label-text{overflow:visible;min-width:0;flex:1;}' +
        '.label-altcode{font-size:5.5pt;font-weight:900;color:#000;white-space:nowrap;overflow:visible;}' +
        '.label-exp{font-size:8pt;font-weight:900;color:#000;white-space:nowrap;margin-top:0.3mm;}' +
        '.label-desc{font-size:4pt;color:#333;font-weight:600;line-height:1.2;overflow:visible;word-wrap:break-word;}' +
        '.label-footer{font-size:3.5pt;color:#555;border-top:0.2mm solid #999;padding-top:0.5mm;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}' +
        '</style></head><body>' +
        '<div class="label-sheet">' + labelsHtml + '</div>' +
        '<script>window.onload=function(){' +
            'var imgs=document.querySelectorAll("img"),loaded=0;' +
            'if(!imgs.length){window.print();return;}' +
            'imgs.forEach(function(img){' +
                'if(img.complete){loaded++;if(loaded===imgs.length)window.print();}' +
                'else{img.onload=img.onerror=function(){loaded++;if(loaded===imgs.length)window.print();};}' +
            '});' +
        '};' +
        '<\/script></body></html>';

    var w = window.open('', '_blank', 'width=600,height=400');
    if (!w) { Swal.fire('Popup Diblokir', 'Izinkan popup di browser Anda.', 'warning'); return; }
    w.document.write(html);
    w.document.close();
}

  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
