@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $status }}</h4>
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
                                    <label for="tDnNumber">Temporary DN Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="tDnNumber" name="tDnNumber" class="form-control disabled-el" value="{{ $header->tdn_number }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="deliveryDate">Delivery Date*</label>
                                    <input type="text" id="deliveryDate" name="deliveryDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->delivery_date }}" required disabled/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-7">
                                    <label class="form-label" for="soNumber">SO Number*</label>
                                    <select class="select2 form-control" id="soNumber" name="soNumber" required>
                                        <option value="">Choose SO</option>
                                        @foreach($soNumbers as $val)
                                            <option value="{{$val->so_code}}" >{{$val->so_code}} - {{$val->po_number}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-7">
                                    <label class="form-label" for="cust">Customer*</label>
                                    <select class="select2 form-control" id="cust" name="cust" required disabled>
                                        <option value="">Choose Customer</option>
                                        @foreach($customers as $val)
                                            <option value="{{$val->kode}}" {{$val->kode == $header->customer_id ? "selected" : ""}}>{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-7">
                                    <label for="perihal">Hal</label>
                                    <input type="text" id="perihal" name="perihal" class="form-control" value="{{ $header->perihal }}" disabled/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled>{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive main-table">
                        <table class="table table-bordered w-100" id="tableDetail">
                            <thead class="thead-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Article</th>
                                    <th class="text-right">QTY</th>
                                    <th class="text-left">UOM</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach( $details as $item )
                                    <tr>
                                        <td class="text-right"></td>
                                        <td >{{ $item->article }}</td>
                                        <td class="text-right">{{ number_format($item->qty,2) }} </td>
                                        <td>{{ $item->uom }}</td>
                                    </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <span>ROW : {{ $header->sum_row }}</span> <br>
                            <span>QTY : {{ number_format($header->sum_qty) }}</span>
                        </div>
                        <div class="col-md-4">
                        </div>
                    </div>
                    <br>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('purchaseRequests.index') }}" class="btn btn-light">Back</a>
                                    <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate" >Update</button>
                                </div>
                            </div>
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
    textarea {
        resize: none;
    }
    .main-table table {
        counter-reset: rowNumber;
        /* display: block;
        height: 500px;
        overflow-y: scroll; */
    }

    .main-table table tr > td:first-child{
        counter-increment: rowNumber;
    }

    .main-table table tr td:first-child::before {
        content: counter(rowNumber);
        min-width: 1em;
        margin-right: 0.5em;
    }

    .text-merah{
        color:red;
    }

    /* #tableDetail th, #tableDetail td {
        padding: 0.4rem 0.6rem;
        vertical-align: middle;
    } */
</style>
@endsection
@section('scripts')
@include('temporaryDn.addArticle')
<script type="text/javascript">
    let timerId="";
    $(document).ready(function(){           
        validateFormToast("frmAdd");
    });
   
    
    $("#cmdUpdate").click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $('.disabled-el').removeAttr('disabled');
            
            let tDnNumber = $('#tDnNumber').val();
            let customerId = $('#cust').val();
            let soNumber = $('#soNumber').val();

            $.ajax({
                type: "post",
                url: "{{ route('suratJalanSementara.updateSo.update') }}",
                data: {
                    customerId:customerId,
                    tDnNumber:tDnNumber,
                    soNumber:soNumber
                },
                dataType: "json",
                success: function(data) {
                    if (data.status == 0 ){
                        let message="";
                        for(let i = 0; i < data.message.length; i++) {
                            show_msg(data.title, data.message[i], data.alert);
                        }
                        $('#prNumber').attr('disabled','disabled');

                    }else{
                        show_msg(data.title, data.message, data.alert);
                        $('#prNumber').attr('disabled','disabled');
                        $('.disabled-el').attr('disabled','disabled');
                    }
                    
                },
                error: function(error) {
                    console.log(error);
                }
            });
        }
    });
            
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection