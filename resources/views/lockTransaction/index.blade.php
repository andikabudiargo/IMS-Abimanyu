@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-bank">
    <div class="form-row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('lockTransaction.store') }}" method="post" autocomplete="off">
                        @csrf
                        <table width="100%">
                            <thead>
                                <tr>
                                    <td width="15%">Menu</td>
                                    <td>Last Lock Date</td>
                                    <td>New Lock Date</td>
                                    <td>Last Created By</td>
                                    <td>Last Created at</td>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($menus as $index=>$val)
                                    <tr>
                                        <td width="15%">{{ $val->module_name }}</td>
                                        <td>
                                            <input type="hidden" id="codeKey{{ $index }}" name="codeKey[]" value="{{ $val->code_key }}" class="form-control" />
                                            <input type="text" id="dateBefore{{ $index }}" name="dateBefore[]" value="{{ $val->lock_date }}" class="form-control disabled-el" disabled/>
                                        </td>
                                        <td>
                                            <input type="text" id="newDate{{ $index }}" name="newDate[]" class="form-control date-picker disabled-el" placeholder="DD-MM-YYYY"/>
                                        </td>
                                        <td>
                                            <input type="text" id="createdBy{{ $index }}" name="updatedBy[]" value="{{ $val->created_by }}" class="form-control disabled-el" disabled/>
                                        </td>
                                        <td>
                                            <input type="text" id="createdAt{{ $index }}" name="updatedBy[]" value="{{ $val->created_at }}" class="form-control disabled-el" disabled/>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <br>
                        <div class="form-row">
                            <div class="col-md-12">
                                <button class="btn btn-primary" type="submit" id="cmdSave" name="cmdSave">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('styles')

@endsection
@section('scripts')
<script type="text/javascript">
     $(document).ready(function(){           
        validateFormToast("frmAdd");
    });

    let datePicker = $('.date-picker');
    if (datePicker.length) {
        datePicker.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today",
        });
    }
</script>
@endsection