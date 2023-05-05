<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-left mb-0">@yield('title')</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        {{-- <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a> --}}
                        </li>
                        @if(\Request::segment(2) != "")
                        <li class="breadcrumb-item"><a href="{{url(\Request::segment(1))}}">{{ ucwords(preg_replace('/([a-z])([A-Z])/s','$1 $2', \Request::segment(1))) }}</a></li>
                            {{-- <li class="breadcrumb-item"><a href="{{url(\Request::segment(1))}}">{{ucwords(\Request::segment(1))}}</a></li> --}}
                            <li class="breadcrumb-item active">@yield('title')</li>
                        @else
                            <li class="breadcrumb-item active">@yield('title')</li>
                        @endif
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>