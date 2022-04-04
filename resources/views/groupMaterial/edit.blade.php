@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-6">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">accounts</h4>
                </div> --}}
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('groupMaterial.update',['id'=> $group->id]) }}" method="post" autocomplete="off">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="kode">Kode</label>
                                <input type="text" id="kode" name="kode" class="form-control .disabled-el"  value="{{old('kode',$group->code)}}" required disabled  />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label for="nama">Nama</label>
                                <input type="text" id="nama" name="nama" class="form-control text-uppercase" value="{{old('nama',$group->name)}}"  required  maxlength="100"  autofocus />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label for="desc">Keterangan</label>
                                <input type="text" id="desc" name="desc" class="form-control" value="{{old('desc',$group->description)}}"  maxlength="100"/>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-12">
                                <a href="{{ route('groupMaterials.index') }}" class="btn btn-success">
                                    Back
                                </a>
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
<style>
    textarea {
        resize: none;
    }
</style>
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