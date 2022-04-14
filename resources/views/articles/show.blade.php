@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form id="frmAdd" name="frmAdd" action="{{ route('article.update',['id'=> $article->id,'artCode' =>$article->article_code])}}" method="post" autocomplete="off">
                        @csrf
                        <div class="form-row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="kode">Article Code</label>
                                    <input type="text" id="kode" name="kode" class="form-control disabled-el" value="{{ old('kode',$article->code) }}" disabled />
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="articleType">Article Type*</label>
                                <select class="select2 form-control" id="articleType" name="articleType" disabled>
                                    <option value="">All</option>
                                    @foreach($types as $val)
                                        <option value="{{$val->code}}" {{ $val->code == old("articleType",$article->article_type) ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>       
                        </div>
                        <div class="form-row">       
                            <div class="form-group col-md-12">
                                <label class="form-label" for="group">Group of material</label>
                                <select class="select2 form-control" id="group" name="group" disabled>
                                    <option value="">All</option>
                                    @foreach($groups as $val)
                                        <option value="{{$val->code}}" {{ $val->code == old("group",$article->group) ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>         
                        </div>
                        <div class="form-row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="colorCode">Color Code</label>
                                    <input type="text" id="colorCode" name="colorCode" class="form-control text-uppercase" value="{{ old("colorCode",$article->color_code) }}" maxlength="10" disabled/>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="variant">Variant</label>
                                    <input type="text" id="variant" name="variant" class="form-control text-uppercase" value="{{ old('variant',$article->variant) }}" maxlength="10" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="cust"> {{ $article->article_type == 'FG' || $article->article_type == 'RM' ? 'Customer' : 'Supplier'}}</label>
                                <select class="select2 form-control" id="cust" name="cust[]" disabled multiple>
                                    @foreach($custs as $val)
                                        <option value="{{$val->kode}}" {{ in_array($val->kode, old('cust',$suppliers)) ? "selected" : ""}}>{{$val->kode}} - {{$val->nama}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-12">
                                <div class="form-group">
                                <label for="nama">Description *</label>
                                    <input type="text" id="nama" name="nama" class="form-control" value="{{ old('nama',$article->desc) }}" maxlength="100" disabled/>
                                </div>
                            </div>
                        </div>                      
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="price">Price</label>
                                <input type="text" id="price" name="price" class="form-control numeral-mask text-right" value="{{ old('price',$article->costprice) }}"  maxlength="10" disabled/>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="uom">Smallest unit *</label>
                                <select class="select2 form-control" id="uom" name="uom" disabled>
                                    <option value="">All</option>
                                    @foreach($uoms as $val)
                                        <option value="{{$val->code}}" {{ $val->code == old("uom",$article->uom) ? "selected" : ""}} >{{$val->code}} - {{$val->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label class="form-label" for="note">Notes</label>
                                <textarea type="text" id="note" name="note" class="form-control" rows="3" maxlength="100" disabled>{{ old('note',$article->note) }}</textarea>
                            </div>
                        </div>
                        <div id="fileUpload" class="d-none">
                        </div>
                        <div class="form-group col-md-4 align-self-end" >
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="status" name="status"  {{ old('status',$article->status) == '1' ? 'checked' : '' }} />
                                <label class="custom-control-label" for="status">Active</label>
                            </div>
                        </div>                        
                    </form>
                    <div class="form-row">
                        <div class="col-md-12">
                            <a href="{{ route('articles.index') }}" class="btn btn-success">
                                Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="ecommerce-application">
                <div class="grid-view wishlist-items">
                    @foreach ($images as $item)
                        <div class="card ecommerce-card" data-namafile="{{ $item->path }}" style="padding: 5px;">
                            <div class="item-img text-center">
                                <div style="max-height:350px;overflow:hidden">
                                    <img src="{{ asset('storage/'. $item->path) }}" alt="{{ $item->name }}" 
                                    onerror="this.src='{{ asset('app-assets/images/product/imageNotFound.png')}}';" 
                                    class="img-fluid img-list">
                                </div>                                
                            </div>
                            <div class="card-body">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
<div id="viewImg" class="modal bisa-geser fade text-left" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header" >
            <h4>View Image</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div id="imgViewer" style="overflow-x: hidden;">
            </div>
          </div>
        </div>
    </div>
</div>
@endsection
@section('styles')
<link rel="stylesheet" type="text/css" href="{{asset('app-assets/css/pages/app-ecommerce.css')}}">
<style>
    textarea {
        resize: none;
    }

    #imgViewer::-webkit-scrollbar {
        -webkit-appearance: none;
        height: 10px;
    }
    #imgViewer::-webkit-scrollbar-thumb {
        border-radius: 5px;
        background-color: rgba(0,0,0,.5);
        box-shadow: 0 0 1px rgba(255,255,255,.5);
    }

    .img-list:hover {
        cursor: pointer;
    }

    .ecommerce-application .grid-view.wishlist-items {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .ecommerce-application .grid-view .ecommerce-card .item-img {
        min-height: 1rem;
    }

    .ecommerce-application .grid-view .ecommerce-card .item-name {
        margin-top: 0.1rem;
    }

</style>
@endsection
@section('scripts')
<script src="{{asset('app-assets/vendors/js/extensions/dropzone.min.js')}}"></script>
<script type="text/javascript">
    let hapusCount=1;
    $(document).ready(function(){           
        validateFormToast("frmAdd");
        mask_thousand();
    });

    $(".select2").on('change', function() {
        $(this).valid();
    });
    
    $('.img-list').on('click', function(e) {
        $('#imgViewer').html('').append( $(e.currentTarget).clone())
        $('#viewImg').modal('show')
    })

    $('.img-list').each(function(i,e) {
        $(e).wrap('<div class="img-wrapper"></div>')
    })

</script>
@endsection