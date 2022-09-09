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
                        <form id="frmAdd" name="frmAdd" action="{{ route('dnReceipt.store') }}" method="post" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="drNumber">Receipt Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="drNumber" name="drNumber" value= "{{ $drNumber }}"class="form-control text-hitam disabled-el"  disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="dnNumber">DN Number*</label>
                                    <input type="hidden" id="deliveryDate" name="deliveryDate"/>
                                    <select class="select2 form-control" id="dnNumber" name="dnNumber" value="{{ old('dnNumber') }}" required>
                                        <option value=""></option>
                                        @foreach($delivery as $val)
                                            <option value="{{ $val->delivery_number }}" 
                                                {{ $val->delivery_number == old('dnNumber') ? "selected":"" }}
                                                data-tanggal={{ $val->delivery_date }} >{{ $val->delivery_date }} - {{ $val->delivery_number }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="receiveBy">Received by*</label>
                                    <select class="select2 form-control" id="receiveBy" name="receiveBy" required>
                                        <option value=""></option>
                                        @foreach($users as $val)
                                            <option value="{{ $val->username }}" {{ $val->username == old('receiveBy') ? "selected":"" }} >{{ $val->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="receiveAt">Receipt At*</label>
                                    <input type="text" id="receiveAt" name="receiveAt" value="{{ old('receiveAt') }}" class="form-control tanggal" placeholder="DD-MM-YYYY" required />
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
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ old('note') }}</textarea>
                                </div>
                            </div>
                            <hr>
                            <div class="form-row">
                                <div class="col-12">
                                    <a href="{{ route('dnReceipt.index') }}" class="btn btn-warning">Back</a>
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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
        
    dnNumber.change(function(){
        let minDate = $(this).find(":selected").data("tanggal");
        if (tanggal.length) {
            tanggal.flatpickr({
                dateFormat: "d-m-Y",
                minDate: minDate
            });
            receiveAt.val(currentDate);
            submitAt.val(currentDate);
        }
    });

    cmdSave.click(function(){    
        let pesan = "";
        let receiptDate = new Date(receiveAt.val().split('-').reverse().join('-'));
        let submitDate = new Date(submitAt.val().split('-').reverse().join('-'));

        pesan += submitBy.val() === receiveBy.val() ? "Petugas tidak boleh sama" : "";
        pesan += receiptDate < submitDate ? "Tanggal terima salah" : "";
        

        if (pesan){
            Swal.fire('Warning..',pesan,'warning');
        }else{
            deliveryDate.val(dnNumber.find(":selected").data("tanggal"));
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