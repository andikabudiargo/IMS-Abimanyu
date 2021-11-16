@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<section id="add-index">
    <div class="row">
        <div class="col-6">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">accounts</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('employee.update',['id'=> $employee->id]) }}" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="kode">Kode</label>
                                    <input type="text" id="kode" name="kode" class="form-control .disabled-el"  value="{{old('kode',$employee->employee_id)}}" required disabled  />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="nama">Nama</label>
                                    <input type="text" id="nama" name="nama" class="form-control text-uppercase" value="{{old('nama',$employee->name)}}"  required  maxlength="100"  autofocus />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="dept">Departemen</label>
                                <select class="select2 w-100" id="dept" name="dept">
                                    <option value="">All</option>
                                    @foreach($depts as $val)
                                        <option value="{{$val->code}}" {{ $val->code == old("dept",$employee->department) ? "selected" : ""}} >{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="position">Jabatan</label>
                                <select class="select2 w-100" id="position" name="position">
                                    <option value="">All</option>
                                    @foreach($positions as $val)
                                        <option value="{{$val->code}}" {{ $val->code == old("position",$employee->job_position) ? "selected" : ""}} >{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
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
<style>
    textarea {
        resize: none;
    }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    $(document).ready(function(){           
        $("#frmAdd").validate({
            invalidHandler: function(event, validator) {
            let errors = validator.numberOfInvalids();
            if (errors) {
                let message = errors == 1
                    ? 'You missed 1 field. It has been highlighted'
                    : 'You missed ' + errors + ' fields. They have been highlighted';
                $("#alert-message .alert-body").html(message);
                $("#alert-message").show();
                $("#alert-message").fadeTo(5000, 500).slideUp(500, function(){
                    $("#alert-message").slideUp(500);
                });
            } else {
                $("#alert-message").hide();
            }
        }
        }).settings.ignore = "";

        // $('#dept').val('{{ Request::old('dept',$employee->department) }}').trigger('change');
        // $('#position').val('{{ Request::old('position',$employee->job_position) }}').trigger('change');

    });

    $("#cmdSave").click(function(){       
        $('.disabled-el').removeAttr('disabled');
        $("#frmAdd").submit(); // Submit the form
    });

</script>
@endsection