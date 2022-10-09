<div class="modal fade text-left bisa-geser" id="mdlList" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xls modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5>List item by Customer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label class="form-label" for="customerList">Customer*</label>
                        <select class="select2 form-control" id="customerList" name="customerList">
                            <option value=""></option>
                            @foreach($custs as $val)
                                <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="table-responsive" id="articleList" >
                    <table class="table table-striped" id="tblListItem">
                        <thead>
                            <tr>
                                <th class="text-center">Check</th>
                                <th class="text-center">Article Code</th>
                                <th class="text-center">Article Desc</th>
                                <th class="text-center">Qty Target</th>
                                <th class="text-center">Qty Forcast</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
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
        height: 80vh;
        overflow-y: auto;
    }

    .table th, .table td {
        padding: 0.5rem 1rem;
        vertical-align: middle;
    }
</style>
<script type="text/javascript">

    const listItem = () => {
        $('#mdlList').modal('show');
    }

    $('#mdlList').on('shown.bs.modal', function (e) {
        $('#customerList').val('').trigger('change');
        $('#tblListItem tbody').empty();
    })

    $('#customerList').change(function(){
        let customer= $(this).val();
        listItemDetail(customer);
    });

    const listItemDetail = (customer) => {
        $.ajax({
            dataType: 'json',
            type:'GET',
            url: "{{ route('targetSo.itemList') }}",
            data: { customer:customer },
            success: function(response) {
                let detail="";
                $('#tblListItem tbody').empty();
                for(let i=0;i<response.data.length;i++){
                    notes = !response.success ? `<td >${response.data[i].notes ? response.data[i].notes : ''}</td>`:'';
                    detail +=`<tr>
                                <td width="5%"><div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="customCheck${i}" 
                                                name="customCheck[]"
                                                data-article-code="${response.data[i].article_code}">
                                                <label class="custom-control-label" for="customCheck${i}"></label>
                                            </div>
                                </td>
                                <td width="5%">${response.data[i].article_alternative_code}</td>
                                <td >${response.data[i].article_desc}</td>
                                <td width="5%">
                                    <input type="text" id="mdlQtyTarget" name="mdlQtyTarget[]" class="form-control form-control-sm numeral-mask-satuan tombol-panah text-right" maxlength="10" 
                                    data-type-el-kanan='input'
                                    data-nama-el-kanan='mdlQtyForcast'
                                    data-click-add = 'false' />
                                </td>
                                <td width="5%">
                                    <input type="text" id="mdlQtyForcast" name="mdlQtyForcast[]" class="form-control form-control-sm numeral-mask-satuan tombol-panah text-right" maxlength="10" 
                                    data-type-el-kiri='input'
                                    data-nama-el-kiri='mdlQtyTarget'
                                    data-click-add = 'false' />
                                </td>
                            </tr>`
                }

                let details = `${detail}`;
                $('#tblListItem tbody').append(details);
                mask_thousand_satuan();
            },
            error: function(data) {
                console.log(data.status);
            }
        });
    }

    document.querySelector('#cmdSelect').addEventListener('click',() =>{
        let objMdlQtyTarget= $('#articleList input[name="mdlQtyTarget[]"]');
        let objMdlQtyForcast= $('#articleList input[name="mdlQtyForcast[]"]');
        let articles = []; 

        $("#articleList input[name='customCheck[]']").map(function(i) {  
            let $this=$(this);
            if ($this.is(':checked')){
                let articleId = $this.data('article-code');
                let qtyTarget=objMdlQtyTarget.eq(i).val().replace(/,/gi,'')||0;
                let qtyForcast=objMdlQtyForcast.eq(i).val().replace(/,/gi,'')||0;
                add_new_row_edit(articleId,qtyTarget,qtyForcast);
                $('#mdlList').modal('toggle');
            }
        });  
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>