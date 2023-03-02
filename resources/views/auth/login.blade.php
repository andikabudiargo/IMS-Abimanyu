<!DOCTYPE html>
<html class="loading semi-dark-layout" lang="en" data-layout="semi-dark-layout" data-textdirection="ltr">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <meta name="csrf-token" content="{{csrf_token()}}">
    <meta name="description" content="Integrated Manufacture System">
    <meta name="keywords" content="abimany, manufacture, ims">
    <meta name="author" content="Oki">
    <title>{{ env('APP_NAME') }} - Login</title>

    <link rel="apple-touch-icon" sizes="57x57" href="{{asset('app-assets/images/ico/favicon/apple-icon-57x57.png')}}">
    <link rel="apple-touch-icon" sizes="60x60" href="{{asset('app-assets/images/ico/favicon/apple-icon-60x60.png')}}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{asset('app-assets/images/ico/favicon/apple-icon-72x72.png')}}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{asset('app-assets/images/ico/favicon/apple-icon-76x76.png')}}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{asset('app-assets/images/ico/favicon/apple-icon-114x114.png')}}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{asset('app-assets/images/ico/favicon/apple-icon-120x120.png')}}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{asset('app-assets/images/ico/favicon/apple-icon-144x144.png')}}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{asset('app-assets/images/ico/favicon/apple-icon-152x152.png')}}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{asset('app-assets/images/ico/favicon/apple-icon-180x180.png')}}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{asset('app-assets/images/ico/favicon/android-icon-192x192.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{asset('app-assets/images/ico/favicon/favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{asset('app-assets/images/ico/favicon/favicon-96x96.png')}}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{asset('app-assets/images/ico/favicon/favicon-16x16.png')}}">
    <link rel="icon" type="image/x-icon" href="{{asset('app-assets/images/ico/favicon/favicon.ico')}}">

    <link rel="stylesheet" type="text/css" href="{{asset('app-assets/vendors/css/vendors.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('app-assets/css/bootstrap.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('app-assets/css/bootstrap-extended.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('app-assets/css/colors.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('app-assets/css/components.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('app-assets/css/themes/dark-layout.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('app-assets/css/themes/bordered-layout.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('app-assets/css/themes/semi-dark-layout.css')}}">

    <link rel="stylesheet" type="text/css" href="{{asset('app-assets/css/core/menu/menu-types/vertical-menu.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('app-assets/css/plugins/forms/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('app-assets/css/pages/page-auth.css')}}">

    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/style.css')}}">


</head>

<body class="vertical-layout vertical-menu-modern blank-page navbar-floating footer-static" data-open="click"
    data-menu="vertical-menu-modern" data-col="blank-page">
    <!-- BEGIN: Content-->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-header row">
            </div>
            <div class="content-body">
                <div class="auth-wrapper auth-v1 px-2">
                    <div class="auth-inner py-2">
                        <!-- Login v1 -->
                        <div class="card mb-0">
                            <div class="card-body">
                                <div class="mb-2">
                                    @if( env('APP_ENV') == 'local' )
                                    <img src="{{asset('app-assets/images/logo/logo1.png')}}" alt="logo" class="logo" style="height: 100px;">
                                    @else
                                    <img src="{{asset('app-assets/images/logo/logo.png')}}" alt="logo" class="logo" style="height: 100px;">
                                    @endif
                                    @if( env('APP_ENVIRONMENT') )
                                    <h2 class="text-primary">{{ env('APP_ENVIRONMENT') }}</h2>
                                    @endif
                                    <h2 class="text-secondary">{{ env('APP_NAME_ALIAS') }}</h2>
                                </div>
                                <h4 class="card-title mb-1">sign-in</h4>
                                <form autocomplete="off" class="auth-login-form mt-2" id="form-login"
                                    action="{{ route('login') }}" method="POST">
                                    {{ csrf_field() }}
                                    <div class="form-group">
                                        <label for="login-username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username"
                                            value="{{ old('username') }}" placeholder="username"
                                            aria-describedby="username" tabindex="1" autofocus />
                                    </div>

                                    <div class="form-group">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group input-group-merge form-password-toggle">
                                            <input type="password" class="form-control form-control-merge" id="password"
                                                name="password" tabindex="2"
                                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                                aria-describedby="password" />
                                            <div class="input-group-append">
                                                <span class="input-group-text cursor-pointer"><i
                                                        data-feather="eye"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    @if($errors->any())
                                    @foreach ($errors->all() as $error)
                                    <h5>{{ $error }}</h5>
                                    @endforeach
                                    @endif

                                    <button id="login" name="login" type="submit" value="Login" class="btn btn-primary btn-block" tabindex="4">Sign in</button>
                                </form>
                                <p class="text-center mt-2">
                                    {{-- <span>New on our platform?</span> --}}
                                    {{-- <a href="page-auth-register-v1.html">
                                        <span>Create an account</span>
                                    </a> --}}
                                </p>
                            </div>
                            <div class="card-body">
                                <p class="clearfix mb-0">
                                    <span class="float-md-left d-block d-md-inline-block mt-25">COPYRIGHT &copy; {{ env('APP_YEAR_CREATED') }} | {{ env('APP_COMPANY') }}, All rights Reserved
                                        {{-- <span class="d-sm-inline-block">{{ env('APP_VERSION') }}</span> --}}
                                        <span class="d-sm-inline-block"> V.3.2.1 </span>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{asset('app-assets/vendors/js/vendors.min.js')}}"></script>
    <script src="{{asset('app-assets/vendors/js/forms/validation/jquery.validate.min.js')}}"></script>
    <script src="{{asset('app-assets/js/core/app-menu.js')}}"></script>
    <script src="{{asset('app-assets/js/core/app.js')}}"></script>
    <script src="{{asset('app-assets/js/scripts/pages/page-auth-login.js')}}"></script>

    <script>
        $(window).on('load', function() {
            if (feather) {
                feather.replace({
                    width: 14,
                    height: 14
                });
            }
        })
    </script>
</body>

</html>