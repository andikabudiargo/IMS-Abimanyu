@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-6">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">yield('title')</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('uomCon.store',['id'=> $uomCon->id]) }}" method="post" autocomplete="off">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="dept">Unit From *</label>
                                <select class="select2 form-control disabled-el" id="unitFrom" name="unitFrom" data-dependent="unitTo" disabled>
                                    <option value="">All</option>
                                    @foreach($uoms as $val)
                                        <option value="{{$val->code}}|{{$val->uom_group}}" {{ $val->code == old("unitFrom",$uomCon->unit_from) ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="dept">Unit To *</label>
                                <select class="select2 form-control disabled-el" id="unitTo" name="unitTo" disabled>                                
                                    @foreach($uoms as $val)
                                        <option value="{{$val->code}}|{{$val->uom_group}}" {{ $val->code == old("unitFrom",$uomCon->unit_from) ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unitFactor">Unit Factor</label>
                                    <input type="text" id="unitFactor" name="unitFactor" class="form-control angka" value="{{ old('unitFactor',$uomCon->unit_factor) }}"  required  maxlength="20"/>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="form-row">
                            <div class="col-12">
                                <a href="{{ route('uomCons.index') }}" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                                <button class="btn btn-success" type="button" id="cmdSave" name="cmdSave">Save</button>
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
        $('.disabled-el').removeAttr('disabled');
        $("#frmAdd").submit(); // Submit the form
    });

</script>
@endsection