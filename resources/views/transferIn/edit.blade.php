@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $statusTr }}</h4>
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
                                    <label for="trNumber">Transfer In Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="trNumber" name="trNumber" value="{{ old('trNumber',$header->tr_number) }}" class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="trDate">Date*</label>
                                    <input type="text" id="trDate" name="trDate" value="{{ old('trDate',$header->tr_date) }}" class="form-control" placeholder="DD-MM-YYYY" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ old('trDate',$header->note) }}</textarea>
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
                            @include('transferIn.headerColumn')
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
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('transferIn.index') }}" class="btn btn-warning">Back</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                        @if( $statusTr =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate" data-trType="TRIN">Update</button>
                                        @endif
                                    @else
                                        @if( !$approveValidate && $statusTr =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate" data-trType="TRIN">Update</button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            @if($val->status == true)
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-{{ $val->statusapprove == 1 ? 'success':'warning' }} mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="{{ $val->statusapprove == 1 ? 'check':'x' }}" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">{{ $val->statusapprove == 1 ? 'Approve':'Decline' }}-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-danger mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="x" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->petugas }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
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
@include('transferIn.addArticle')
<script type="text/javascript">
    const updateBtn = document.querySelector('#cmdUpdate');
    const approveBtn = document.querySelector('#cmdApprove');

    if (updateBtn) {
        updateBtn.addEventListener('click',() =>{
            let oEdit = document.getElementById('oEdit');
            simpanData(oEdit.value);
        // },{ once:true});
        });
    }

    if (approveBtn) {
        approveBtn.addEventListener('click',() =>{
            let trNumber = $('#trNumber').val();
            approve(trNumber,'cmdApprove');
        },{ once:true});
    }
    
    $(document).ready(function(){           
        validateFormToast("frmAdd");
        isiArticle('trArticle');
        let timerId= setInterval(() => checkVariable(), 1000);
        function checkVariable() {
            if (dataArticle.length > 0) {
                clearInterval(timerId);
                let detail = {!!  $details !!};
                for(let i=0;i<detail.length;i++){
                    article = detail[i].article_code;
                    qty = detail[i].qty;
                    uom =  detail[i].uom;
                    uomMember = detail[i].uom_member;
                    note = detail[i].note;
                    add_new_row_edit(article,qty,uom,uomMember,note);
                }
            }
        }
        
    });
</script>
@endsection