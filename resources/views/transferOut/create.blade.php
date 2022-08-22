@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: New</h4>
                    <input type="hidden" id='oEdit' value="{{ $oEdit }}">
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <input type="text" id="article" name="article" hidden>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="trNumber">Transfer Out Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="trNumber" name="trNumber" class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="trDate">Date*</label>
                                    <input type="text" id="trDate" name="trDate" class="form-control" placeholder="DD-MM-YYYY" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="toType">TO Type</label>
                                    <select class="select2 form-control" id="toType" name="toType" required>
                                        <option value="std">Standard</option>
                                        <option value="prd">Production</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4" id="tsoBox">
                                    <label class="form-label" for="tsoCode">Production</label>
                                    <select class="select2 form-control" id="tsoCode" name="tsoCode">
                                    </select>
                                </div>
                            </div>
                            {{-- <div class="form-row" id="tsoBox">
                                
                            </div> --}}
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
                                </div>
                            </div>                            
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body" >
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('transferOut.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();hitungGrandTotal();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                <label for="totalQty" class="col-sm-3 col-form-label titik-dua">Total QTY</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQty" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel" data-trType="TROUT">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave" data-trType="TROUT">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
@include('transferOut.addArticle')
<script type="text/javascript">
    let objToType = $('#toType');
    let objTsoCode = $('#tsoCode');
    let objTsoBox = $('#tsoBox');
    document.querySelector('#cmdSave').addEventListener('click',() =>{
        let element = document.getElementById('cmdSave');
        let oEdit = document.getElementById('oEdit');
        simpanData(oEdit.value);
    });

    $(document).ready(function(){           
        validateFormToast("frmAdd");
        $('#trDate').val(currentDate);
        isiArticle('trArticle');
        objTsoBox.hide();
    });

    objToType.change(function(e){
        let toType=$(this).val();
        objTsoBox.hide();
        if (toType ==='prd'){
            objTsoBox.show();
            dependent = 'wos_list'
            changeSelect({
                dependent:dependent,
                obj:'tsoCode',
                url:"{{ route('dynamic.dependent') }}"            
            });
        }
    });

    objTsoCode.change(function(e){
        let tsoCode = $(this).val();    
        if (tsoCode){        
            $.ajax({
                type: "GET",
                url: "{{ route('transferOut.article.tso') }}",
                data: {
                    tsoCode:tsoCode
                },
                dataType: "json",
                success: function(data) {
                    if (data){
                        for(let i=0;i<data.length;i++){
                            add_new_row_edit(data[i].article_code,data[i].grand_total,data[i].uom,data[i].uom_member,'');
                        }
                    }
                },
                error: function(error) {
                    console.log(error);
                }
            });
        }
    });
</script>
@endsection