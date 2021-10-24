{{-- alet dati ajax success --}}
<div class="alert alert-dismissible collapse" role="alert" id="alert-message-success">
    <div class="alert-body">
    </div>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

{{-- alert dari validasi form --}}
<div class="alert alert-warning alert-dismissible collapse" role="alert" id="alert-message">
    <div class="alert-body">
    </div>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

{{-- alert dari validasi respon simpan di session --}}
@if  ($alert = Session::get('alert'))
    <div class="alert {{ $alert }} alert-dismissible " role="alert" id="alert-message-alert">
        <div class="alert-body">
            {{ Session::get('message') }}
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

{{-- alert dari validasi controller --}}
@if ($errors->any())
    <div class="alert alert-warning alert-dismissible" role="alert" id="alert-message-error">
        <div class="alert-body">
            @foreach ($errors->all() as $error)
                    {{ $error }} <br>
            @endforeach
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif