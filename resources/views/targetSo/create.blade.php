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
                                    <label for="tsoCode">Target SO Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="tsoCode" name="tsoCode" class="form-control disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="tsoDate">Date*</label>
                                    <input type="text" id="tsoDate" name="tsoDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label for="tsoName">Target SO Name*</label>
                                    <input type="text" id="tsoName" name="tsoName" class="form-control" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
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
                    <div class="form-group col-md-6">
                    <h4 class="card-title">Article</h4><small class="text-muted">Daftar article adalah article yang sudah memiliki BOM </small>
                    </div>
                </div>
                <div class="card-body" >
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('targetSo.headerColumn')
                            <div class="" id="article_row" style="max-height: 30rem;overflow-x: hidden;scrollbar-width: thin;">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <button class="btn btn-success btn-prev" type="button" id="addNewList" onclick="listItem()">
                                <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle d-sm-inline-block d-none">Add by customer</span>
                            </button>

                            <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();hitungGrandTotal();">
                                <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                            </button>
                        </div>
                    </div>
                    {{-- <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();hitungGrandTotal();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div> --}}
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
                                <label for="totalAmount" class="col-sm-3 col-form-label titik-dua">Total QTY Target</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQtyTarget" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalAmount" class="col-sm-3 col-form-label titik-dua">Total QTY Forcast</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQtyForcast" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('targetSo.index') }}" class="btn btn-warning">Back</a>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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
@include('targetSo.listItem')
@include('targetSo.addArticle')
<script type="text/javascript">
    
    $(document).ready(function(){           
        validateFormToast("frmAdd");
        isiArticle('tsoArticle');
        $('#customerList').select2();
        $('#tsoDate').val(currentDate);
    });

    document.querySelector('#cmdSave').addEventListener('click',() =>{
        let oEdit = $('#oEdit').val();
        if (oEdit){
            updateData('cmdSave');
        }else{
            simpanData('cmdSave');
        }
    });

</script>
@endsection