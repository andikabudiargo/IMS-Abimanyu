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
                        <table width="100%" class="table table-sm">
                            <thead>
                                <tr>
                                    <td width="15%">Menu</td>
                                    <td>Last Lock Date</td>
                                    <td>New Lock Date</td>
                                    <td class="text-center">Activity Lock</td>
                                    <td class="text-center">Overstock Lock</td>
                                    <td>Last By</td>
                                    <td>Last At</td>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($menus as $index=>$val)
                                    <tr>
                                        <td width="15%">{{ $val->module_name }}</td>
                                        <td>
                                            <input type="hidden" name="codeKey[]" value="{{ $val->code_key }}" />
                                            <input type="text" name="dateBefore[]" value="{{ $val->lock_date }}" class="form-control disabled-el" disabled/>
                                        </td>
                                        <td>
                                            @if($val->has_period)
                                                <input type="text" name="newDate[]" class="form-control date-picker disabled-el" placeholder="DD-MM-YYYY"/>
                                            @else
                                                <input type="hidden" name="newDate[]" value="" />
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{-- hidden 0 + checkbox 1 pada name+index yang sama: unchecked -> 0, checked -> 1 --}}
                                            <input type="hidden" name="activityLock[{{ $index }}]" value="0" />
                                            <input type="checkbox" name="activityLock[{{ $index }}]" value="1" {{ $val->activity_lock ? 'checked' : '' }} />
                                        </td>
                                        <td class="text-center">
                                            @if($val->has_overstock)
                                                <input type="hidden" name="overstockLock[{{ $index }}]" value="0" />
                                                <input type="checkbox" name="overstockLock[{{ $index }}]" value="1" {{ $val->overstock_lock ? 'checked' : '' }} />
                                            @else
                                                <input type="hidden" name="overstockLock[{{ $index }}]" value="0" />
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="text" value="{{ $val->created_by }}" class="form-control disabled-el" disabled/>
                                        </td>
                                        <td>
                                            <input type="text" value="{{ $val->created_at }}" class="form-control disabled-el" disabled/>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <br>
                        <div class="form-row">
                            <div class="col-md-12">
                                <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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

    $("#cmdSave").click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{ 
            $('.disabled-el').removeAttr('disabled');
            $("#frmAdd").submit();
        }
    })

    let datePicker = $('.date-picker');
    if (datePicker.length) {
        datePicker.flatpickr({
            dateFormat: "d-m-Y",
            // maxDate: "today",
        });
    }
</script>
@endsection