<div class="modal fade text-left bisa-geser" id="mdlList" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xls modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5>List bom</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label class="form-label" for="bomList">Bom*</label>
                        <select class="select2 form-control" id="bomList" name="bomList">
                            <option value=""></option>
                            @foreach($boms as $val)
                                <option value="{{$val->bom_code}}" >{{$val->bom_code}} - {{$val->article_alternative_code}} - {{$val->article_desc}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="table-responsive table-scroll-container" id="bomListTable" >
                    <table class="table table-striped" id="tblListBom">
                        <thead>
                            <tr>
                                <th class="text-center">Check</th>
                                <th class="text-center">Tone</th>
                                <th class="text-center">POS</th>
                                <th class="text-center">Article Code</th>
                                <th class="text-center">Article Desc</th>
                                <th class="text-center">Qty</th>
                                <th class="text-center">UOM</th>
                                <th class="text-center">UOM Con</th>
                                <th class="text-center">Qty Con</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <br>
                <div class="form-row" style="padding-left: 10px">
                    <div class="form-group col-md-12">
                        <div class="custom-control custom-checkbox d-none" id="selectAllDiv">
                            <input type="checkbox" class="custom-control-input" id="selectAll">
                            <label class="custom-control-label" for="selectAll">Select All</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="col-md-12">
                    <button class="btn btn-success" type="button" data-dismiss="modal" id="cmdCancel" name="cmdCaancel">Cancel</button>
                    <button class="btn btn-primary" type="button" id="cmdSelect" name="cmdSelect">Select</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .modal-dialog{
        overflow-y: initial !important
    }
    .modal-body{
        height: 50vh;
        overflow-y: auto;
    }

    .table th, .table td {
        padding: 0.5rem 1rem;
        vertical-align: middle;
    }

    /* Container untuk tabel dengan scroll */
    .table-scroll-container {
        max-height: 32vh; /* Atur tinggi maksimum sesuai kebutuhan */
        overflow-y: auto; /* Aktifkan scroll vertikal */
        border: 1px solid #dee2e6; /* Tambahkan border agar terlihat jelas */
        border-radius: 4px;
        margin-top: 10px;
    }
    
    /* Untuk membuat header tabel tetap saat scroll */
    #tblListBom thead th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa; /* Warna background header */
        z-index: 10;
        border-bottom: 2px solid #dee2e6;
    }
    
    /* Pastikan modal memiliki tinggi yang cukup */
    .modal-xls {
        max-width: 100%;
    }
    
    /* Atur tinggi modal body */
    .modal-body {
        max-height: 80vh;
        overflow-y: none;
    }
</style>

<script type="text/javascript">

    const listItem = () => {
        $('#cmdSelect').removeAttr('disabled');
        $('#mdlList').modal('show');
    }

    $('#mdlList').on('shown.bs.modal', function (e) {
        $('#bomList').val('').trigger('change');
        $('#bomList').select2();
        $('#tblListBom tbody').empty();
        $('#selectAll').prop('checked', false); // uncheck selectAll
        $('#selectAllDiv').addClass('d-none');
    })

    $('#bomList').change(function(){
        let bomCode= $(this).val();
        $('#selectAllDiv').addClass('d-none');
        $('#tblListBom tbody').empty();

        if(bomCode){
            listBomDetail(bomCode);
        }

    });

    const listBomDetail = (bomCode) => {
        $.ajax({
            dataType: 'json',
            type:'GET',
            url: "{{ route('bom.bomList') }}",
            data: { 
                bomCode:bomCode 
            },
            success: function(response) {
                let detail="";
                $('#tblListBom tbody').empty();

                if(response.data.length > 0){
                    for(let i=0;i<response.data.length;i++){
                        notes = !response.success ? `<td >${response.data[i].notes ? response.data[i].notes : ''}</td>`:'';
                        detail +=`<tr>
                                    <td width="5%"><div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="customCheck${i}" 
                                                        name="customCheck[]"
                                                        data-article-code="${response.data[i].article_code}"
                                                        data-qty="${response.data[i].qty}"
                                                        data-uom="${response.data[i].uom}"
                                                        data-uom-con="${response.data[i].uom_con}"
                                                        data-factor="${response.data[i].factor}"
                                                        data-pos-code="${response.data[i].pos}"
                                                        data-tone-code="${response.data[i].tone}"
                                                        data-uom-group="${response.data[i].uom_group}"
                                                        data-uom-member="${response.data[i].uom_member}"
                                                        data-brand="${response.data[i].nama}"
                                                        data-uoms="${response.data[i].uoms}"
                                                    >
                                                    <label class="custom-control-label" for="customCheck${i}"></label>
                                                </div>
                                    </td>
                                    <td width="5%">${response.data[i].tone}</td>
                                    <td width="5%">${response.data[i].pos_name}</td>
                                    <td width="10%">${response.data[i].article_alternative_code}</td>
                                    <td width="5%">${response.data[i].article_desc}</td>
                                    <td width="5%" align="right">${response.data[i].qty}</td>
                                    <td width="5%">${response.data[i].uom}</td>
                                    <td width="5%">${response.data[i].uom_con}</td>
                                    <td width="5%" align="right">${response.data[i].factor*response.data[i].qty}</td>
                                </tr>`
                    }
    
                    let details = `${detail}`;
                    
                    $('#tblListBom tbody').append(details);
                    $('#selectAllDiv').removeClass('d-none');
    
                    mask_thousand_satuan();
                }

            },
            error: function(data) {
                // Extract error message from different possible sources
                let errorMessage = 'An error occurred while fetching data.';
                
                // // Try to get error message from response
                // if (xhr.responseJSON && xhr.responseJSON.message) {
                //     errorMessage = xhr.responseJSON.message;
                // } else if (xhr.responseJSON && xhr.responseJSON.error) {
                //     errorMessage = xhr.responseJSON.error;
                // } else if (xhr.responseText) {
                //     errorMessage = xhr.responseText;
                // } else if (xhr.status === 0) {
                //     errorMessage = 'Network error. Please check your internet connection.';
                // } else if (xhr.status === 404) {
                //     errorMessage = 'Requested resource not found.';
                // } else if (xhr.status === 500) {
                //     errorMessage = 'Internal server error.';
                // } else if (error) {
                //     errorMessage = error;
                // }
                
                Swal.fire('Error', errorMessage, 'error');
                
                // Optional: Log error for debugging
                console.error('AJAX Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    response: xhr.responseJSON,
                    error: error
                });
            }
        });
    }

    function checkVariable(obj) {
        if (allSelectsAreFilledjQuery(obj)) {
            clearInterval(timerId);
            $(".loading-spinner-container").removeClass("-show");
        }
    }

    document.querySelector('#cmdSelect').addEventListener('click',() =>{
        
        $('#mdlList').modal('toggle');
        $('#cmdSelect').attr('disabled','disabled');
        let bomCode = $('#bomList').val();
        let checkedItems = $("#bomListTable input[name='customCheck[]']:checked");
        let totalItems = checkedItems.length;

        if(totalItems > 0){

            removeAllChildDivs("article_row");
            removeAllChildDivs("article_row_sb");

            setTimeout(function () {
                $(".loading-spinner-container").addClass("-show");
            }, 500);

            timerId= setInterval(() => checkVariable("#article_row select[name='article_id[]']"), 1000);

            getSpayBooths(bomCode);

            checkedItems.each(function(i) {
                let $this=$(this);
                let article = $this.data('article-code');
                let qty = $this.data('qty') || 0 ;
                let uom =  $this.data('uom');
                let uomCon =  $this.data('uom-con');
                let typeName = $this.data('uom-group');
                let uomMember = $this.data('uom-member');
                let uoms = $this.data('uoms');
                let factor = $this.data('factor') || 1;
                let pos = $this.data('pos-code');
                let tone = $this.data('tone-code');
                let brand = $this.data('brand');
                // add_new_row_edit(article,qty,uom,uomCon,typeName,uomMember,uoms,factor,pos,tone,brand);
                //addNewRowEdit(article, qty, uom, uomCon, typeName, uomMember, uoms, factor, pos, tone, brand);
                 add_new_row_edit(article, qty, uom, uomCon, typeName, uomMember, uoms, factor, pos, tone, brand);
            }).promise().done(function() {
                $(".loading-spinner-container").removeClass("-show");
            });

        }else{
            Swal.fire('Error', 'Please select at least one item', 'error');
        }

    });

    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="customCheck[]"]');
        const isChecked = this.checked;
        checkboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
    });

    const checkboxes = document.querySelectorAll('input[name="customCheck[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            const selectAllCheckbox = document.getElementById('selectAll');
            selectAllCheckbox.indeterminate = !allChecked && Array.from(checkboxes).some(cb => cb.checked);
            selectAllCheckbox.checked = allChecked;
        });
    });

    const getSpayBooths = (bomNumber) => {
        $.ajax({
            url: "{{ route('bom.getSpayBooths') }}",
            data : {
                bomNumber:bomNumber
            },
            dataType: 'json',
            method: 'GET',
            success: function(response) {
                sprayBooths = response.data;

                for(let a=0;a<sprayBooths.length;a++){
                    let sprayBooth = sprayBooths[a].spray_booth;
                    let tone = sprayBooths[a].tone;
                    let tack =  sprayBooths[a].tack;
                    let passRate =  sprayBooths[a].pass_rate;
                    let passThru =  sprayBooths[a].pass_thru;
                    let cycleTime =  sprayBooths[a].cycle_time;
                    add_new_row_edit_sb(sprayBooth,tone,tack,passRate,passThru,cycleTime);
                }

            },

            error: function(xhr, status, error) {
                // Extract error message from different possible sources
                let errorMessage = 'An error occurred while fetching data.';
                
                // // Try to get error message from response
                // if (xhr.responseJSON && xhr.responseJSON.message) {
                //     errorMessage = xhr.responseJSON.message;
                // } else if (xhr.responseJSON && xhr.responseJSON.error) {
                //     errorMessage = xhr.responseJSON.error;
                // } else if (xhr.responseText) {
                //     errorMessage = xhr.responseText;
                // } else if (xhr.status === 0) {
                //     errorMessage = 'Network error. Please check your internet connection.';
                // } else if (xhr.status === 404) {
                //     errorMessage = 'Requested resource not found.';
                // } else if (xhr.status === 500) {
                //     errorMessage = 'Internal server error.';
                // } else if (error) {
                //     errorMessage = error;
                // }
                
                Swal.fire('Error', errorMessage, 'error');
                
                // Optional: Log error for debugging
                console.error('AJAX Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    response: xhr.responseJSON,
                    error: error
                });
            }
            
            // error: function(response) {
            //     swal.fire('Error..',response.message,'error');
            // }
        });
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>