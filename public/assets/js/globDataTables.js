
/*
*
*   JS Datatables
*   Supaya datatables bisa terpusat
*
*
*/

"use strict";

//datatables function
const dtdomGlob ='<"d-flex justify-content-between align-items-center header-actions mx-1 row mt-75"<"col-lg-12 col-xl-6" l><"col-lg-12 col-xl-6 pl-xl-75 pl-0"<"dt-action-buttons text-xl-right text-lg-left text-md-right text-left d-flex align-items-center justify-content-lg-end align-items-center flex-sm-nowrap flex-wrap mr-1"<"mr-1"f>B>>>t<"d-flex justify-content-between mx-2 row mb-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>';
const buttonExportGlob = (arrColPrint) => {
    return [
      {
        extend: 'collection',
        className: 'btn btn-outline-secondary dropdown-toggle mt-07',
        text: feather.icons['share'].toSvg({ class: 'font-small-4 mr-50' }) + 'Export',
        buttons: [
          // {
          //   extend: 'print',
          //   text: feather.icons['printer'].toSvg({ class: 'font-small-4 mr-50' }) + 'Print',
          //   className: 'dropdown-item',
          //   exportOptions: { columns: arrColPrint }
          // },
          {
            extend: 'csv',
            text: feather.icons['file-text'].toSvg({ class: 'font-small-4 mr-50' }) + 'Csv',
            className: 'dropdown-item',
            exportOptions: { columns: arrColPrint }
          },
          {
            extend: 'excel',
            text: feather.icons['file'].toSvg({ class: 'font-small-4 mr-50' }) + 'Excel',
            className: 'dropdown-item',
            exportOptions: { columns: arrColPrint }
          },
          // {
          //   extend: 'pdf',
          //   text: feather.icons['clipboard'].toSvg({ class: 'font-small-4 mr-50' }) + 'Pdf',
          //   className: 'dropdown-item',
          //   exportOptions: { columns: arrColPrint }
          // },
          // {
          //   extend: 'copy',
          //   text: feather.icons['copy'].toSvg({ class: 'font-small-4 mr-50' }) + 'Copy',
          //   className: 'dropdown-item',
          //   exportOptions: { columns: arrColPrint }
          // }
        ],
        init: function (api, node, config) {
          $(node).removeClass('btn-secondary');
          $(node).parent().removeClass('btn-group');
          setTimeout(function () {
            $(node).closest('.dt-buttons').removeClass('btn-group').addClass('d-inline-flex');
          }, 50);
        }
      },
    ]
}
let showDataTables = (opt) => {
    opt = $.extend({
      tableId:"",
      route:"",
      kolom:"",
      arrColPrint:"",
      dataSearch:"",
      orderColumn:"",
      buttons:true,
      columnDefs:"",
      excelFileName:"data",
    }, opt);
    let button = opt.buttons == true ? 'B' : '';
    $(function(){
      $("#"+opt.tableId).DataTable({
          ajax:{
              url:opt.route,
              data:opt.dataSearch
          },
          processing: true,
          serverSide: true,
          buttons: true,
          dom:` <"d-flex justify-content-between align-items-center header-actions mx-1 row mt-75"
                  <"col-lg-12 col-xl-6" 
                    l>
                  <"col-lg-12 col-xl-6 pl-xl-75 pl-0"
                    <"dt-action-buttons text-xl-right text-lg-left text-md-right text-left d-flex align-items-center justify-content-lg-end align-items-center flex-sm-nowrap flex-wrap mr-1"
                      <"mr-1" 
                      f>
                      ${button}
                    >
                  >
                >t
                <"d-flex justify-content-between mx-2 row mb-1"
                  <"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"
                  p>
                >`,
          lengthMenu: [
            [ 10, 25, 50, -1 ],
            [ '10', '25', '50', 'all' ]
          ],
          buttons: [
            {
              extend: 'collection',
              className: 'btn btn-outline-secondary dropdown-toggle mt-07',
              text: feather.icons['share'].toSvg({ class: 'font-small-4 mr-50' }) + 'Export',
              buttons: [
                // {
                //   extend: 'print',
                //   text: feather.icons['printer'].toSvg({ class: 'font-small-4 mr-50' }) + 'Print',
                //   className: 'dropdown-item',
                //   exportOptions: { columns: opt.arrColPrint }
                // },
                {
                  extend: 'csv',
                  text: feather.icons['file-text'].toSvg({ class: 'font-small-4 mr-50' }) + 'Csv',
                  className: 'dropdown-item',
                  exportOptions: { columns: opt.arrColPrint }
                },
                {
                  extend: 'excel',
                  text: feather.icons['file'].toSvg({ class: 'font-small-4 mr-50' }) + 'Excel',
                  className: 'dropdown-item',
                  exportOptions: { columns: opt.arrColPrint },
                  action: newExportAction,
                  title:null,
                  filename:opt.excelFileName
                },
                // {
                //   extend: 'pdf',
                //   text: feather.icons['clipboard'].toSvg({ class: 'font-small-4 mr-50' }) + 'Pdf',
                //   className: 'dropdown-item',
                //   exportOptions: { columns: opt.arrColPrint }
                // },
                // {
                //   extend: 'copy',
                //   text: feather.icons['copy'].toSvg({ class: 'font-small-4 mr-50' }) + 'Copy',
                //   className: 'dropdown-item',
                //   exportOptions: { columns: opt.arrColPrint }
                // }
              ],
              init: function (api, node, config) {
                $(node).removeClass('btn-secondary');
                $(node).parent().removeClass('btn-group');
                setTimeout(function () {
                  $(node).closest('.dt-buttons').removeClass('btn-group').addClass('d-inline-flex');
                }, 50);
              }
            },
          ],
          language: {
            paginate: {
                // remove previous & next text from pagination
                previous: '&nbsp;',
                next: '&nbsp;'
            }
          },
          columnDefs: opt.columnDefs,
          drawCallback: function( settings ) {
            feather.replace({
              width: 14,
              height: 14
            });
          },
          order: opt.orderColumn,
          bDestroy: true, //pakai ini supaya bisa di load berulang2
          // scrollX: true, //pakai ini supaya waktu responsive  bisa di scroll horizontal
          columns: opt.kolom,
      });
    });
    //$('div.head-label').html('<h6 class="mb-0">Data Users</h6>');   
}

//export all data on datatables and all pages
const oldExportAction = function (self, e, dt, button, config) {
  if (button[0].className.indexOf('buttons-excel') >= 0) {
      if ($.fn.dataTable.ext.buttons.excelHtml5.available(dt, config)) {
          $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config);
      }
      else {
          $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button, config);
      }
  } else if (button[0].className.indexOf('buttons-print') >= 0) {
      $.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
  }
};

const newExportAction = function (e, dt, button, config) {
  let self = this;
  let oldStart = dt.settings()[0]._iDisplayStart;
  dt.one('preXhr', function (e, s, data) {
      // Just this once, load all data from the server...
      data.start = 0;
      data.length = 2147483647;
      dt.one('preDraw', function (e, settings) {
          // Call the original action function 
          oldExportAction(self, e, dt, button, config);
          dt.one('preXhr', function (e, s, data) {
              // DataTables thinks the first item displayed is index 0, but we're not drawing that.
              // Set the property to what it was before exporting.
              settings._iDisplayStart = oldStart;
              data.start = oldStart;
          });
          // Reload the grid with the original page. Otherwise, API functions like table.cell(this) don't work properly.
          setTimeout(dt.ajax.reload, 0);

          // Prevent rendering of the full data to the DOM
          return false;
      });
  });
  // Requery the server with the new one-time export settings
  dt.ajax.reload();
};