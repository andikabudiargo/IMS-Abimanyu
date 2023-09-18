@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText"></span></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" action="{{ route('dnReceipt.update') }}" method="post" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="drNumber">Receipt Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="drNumber" name="drNumber" value= "{{ $dnReceipt->dr_number }}"class="form-control text-hitam disabled-el"  disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="dnNumber">DN Number*</label>
                                    <input type="hidden" id="deliveryDate" name="deliveryDate" value="{{ $dnReceipt->delivery_date }}" />
                                    <input type="text" id="dnNumber" name="dnNumber" value= "{{ $dnReceipt->delivery_number }}" class="form-control text-hitam disabled-el"  disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="receiveBy">Received by*</label>
                                    <select class="select2 form-control" id="receiveBy" name="receiveBy" disabled>
                                        <option value=""></option>
                                        @foreach($users as $val)
                                            <option value="{{ $val->username }}" {{ $val->username == old('receiveBy',$dnReceipt->received_by) ? "selected":"" }} >{{ $val->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="receiveAt">Receipt At*</label>
                                    <input type="text" id="receiveAt" name="receiveAt" value="{{ old('receiveAt',$drDate) }}" class="form-control" placeholder="DD-MM-YYYY" disabled />
                                </div>                               
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="submitBy">Submitted by*</label>
                                    <select class="select2 form-control" id="submitBy" name="submitBy" required>
                                        <option value=""></option>
                                        @foreach($users as $val)
                                            <option value="{{ $val->username }}" {{ $val->username == old("submitBy") ? "selected":"" }} >{{$val->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="submitAt">Submit At*</label>
                                    <input type="text" id="submitAt" name="submitAt" value="{{ old('submitAt') }}" class="form-control tanggal" placeholder="DD-MM-YYYY" required />
                                </div>                               
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ old('note',$dnReceipt->note) }}</textarea>
                                </div>
                            </div>
                            <hr>
                            <div class="form-row">
                                <div class="col-12">
                                    <a href="{{ route('dnReceipt.index') }}" class="btn btn-light">Back</a>
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('styles')
<style>
    textarea {
        resize: none;
    }

    .mb-03{
        margin-bottom: 0.3rem;
    }

</style>
@endsection
@section('scripts')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');  
    const deliveryDate = $('#deliveryDate');
    const dnNumber = $('#dnNumber');
    const cmdSave = $('#cmdSave');
    const submitAt = $('#submitAt');
    const submitBy = $('#submitBy');
    const receiveBy = $('#receiveBy');
    const receiveAt = $('#receiveAt');
    const tanggal = $('.tanggal');

    $(document).ready(function(){
        validateFormToast("frmAdd");
    });
        

    let minDate = "{{ $dnReceipt->dr_date }}";
    if (tanggal.length) {   
        tanggal.flatpickr({
            dateFormat: "d-m-Y",
            minDate: minDate
        });
        submitAt.val(currentDate);
    }

    cmdSave.click(function(){    
        let pesan = "";
        let submitDate = new Date(submitAt.val().split('-').reverse().join('-'));
        let receiptDate = new Date(receiveAt.val().split('-').reverse().join('-'));
        
        pesan += submitBy.val() === receiveBy.val() ? "Petugas tidak boleh sama" : "";
        pesan += receiptDate > submitDate ? "Submit Date > Receipt Date" : "";

        if (pesan){
            Swal.fire('Warning..',pesan,'warning');
        }else{
            $('.disabled-el').removeAttr('disabled');
            $("#frmAdd").submit(); // Submit the form
        }

    });
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection