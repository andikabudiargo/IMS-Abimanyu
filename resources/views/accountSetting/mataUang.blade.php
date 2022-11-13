@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-bank">
    <div class="form-row">
        <div class="col-6">
            <div class="card">
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('accountSetting.store',['type'=>'mataUang']) }}" method="post" autocomplete="off">
                        @csrf
                        <table width="100%">
                            <tbody>
                                @foreach($accDefaults as $valDefault)
                                    <tr>
                                        <td width="15%">{{ $valDefault->name }}</td>
                                        <td width="2%"></td>
                                        <td>
                                            <select class="select2 form-control" id="{{ $valDefault->code }}" name="{{ $valDefault->code }}">
                                                <option value=""></option>
                                                @foreach($accounts as $val)
                                                    <option value="{{$val->account}}" {{ $val->account == $valDefault->account ? "selected" : "" }}>
                                                        {{$val->account}} - {{$val->description}}
                                                    </option>
                                                @endforeach
                                            </select>
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
</script>
@endsection