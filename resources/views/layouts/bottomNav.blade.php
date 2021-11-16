<style>

    .mobile-bottom-nav{
        will-change:transform;
        transform: translateZ(0);
        height:45px;
        box-shadow: -6px 2px 36px -15px rgba(0,0,0,0.75);
        -webkit-box-shadow: -6px 2px 36px -15px rgba(0,0,0,0.75);
        -moz-box-shadow: -6px 2px 36px -15px rgba(0,0,0,0.75);
        background-color:#fff;	
        -webkit-border-radius: 10px 10px 10px 10px ;
        -moz-border-radius: 10px 10px 10px 10px;
        -ms-border-radius: 10px 10px 10px 10px;
        -o-border-radius: 10px 10px 10px 10px;
        border-radius: 10px 10px 10px 10px;
        margin:0px 10px 10px 10px;
    }

    .icon-in:hover{
        color: #000;
        -webkit-transform: scale(1.1);
        transform: scale(1.1);
    }

    .icon-in-logout:hover{
        color: rgb(247, 6, 6);
        -webkit-transform: scale(1.1);
        transform: scale(1.1);
    }

    #but-main-menu {
        /* padding: 10px; */
        background: #0084d6;
        opacity: 1;
        -webkit-border-radius: 12px;
        -moz-border-radius: 12px;
        -ms-border-radius: 12px;
        -o-border-radius: 12px;
        border-radius: 12px;
    }
    
    #but-main-menu:hover {
        -webkit-transform: scale(1.1);
        transform: scale(1.1);
    }

    #but-main-menu .icon-in {
        color: rgb(252, 247, 247);
    }

    
</style>
    
<nav class="navbar mobile-bottom-nav navbar-expand fixed-bottom d-md-none d-lg-none d-xl-none footer-default">
    <ul class="navbar-nav nav-justified w-100">
        <li class="nav-item">
          
        </li>
        <li class="nav-item">
            <a href="{{ route('home') }}" class="nav-link text-center">
                <i class="feather-24 icon-in" data-feather='home' ></i>
            </a>
        </li>
        <li class="nav-item" id="but-main-menu">
            <a class="nav-link menu-toggle" href="javascript:void(0);">
                <i class="feather-24 icon-in" data-feather="menu"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('user.profile') }}" class="nav-link text-center">
                <i class="feather-24 icon-in" data-feather='user'></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="javascript:void(0);" onclick="event.preventDefault();document.getElementById('logout-form').submit();" class="nav-link text-center">
                <i class="feather-24 icon-in-logout" data-feather='log-out'></i>
            </a>
        </li>
    </ul>
</nav>