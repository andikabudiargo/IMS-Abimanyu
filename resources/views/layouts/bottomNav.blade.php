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

    /* .kotak-utama{
        margin:0;
        padding:0;
        box-sizing: border-box;
    }
    :root{
        --clr:#222327;
    }

    .kotak{
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 12vh;
        background: var(--clr);
    }

    .kotak .navigation1 {
        position: relative;
        width: 95%;
        height:45px;
        background: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 10px;
    }

    .navigation1 ul{
        display: flex;
        width: 100%;
        padding-left: 0;
    }

    
    .navigation1 ul li {
        position: relative;
        list-style: none;
        width: 100px;
        height: 60px;
        z-index: 1;
    }

    .navigation1 ul li a {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        width: 100%;
        text-align: center;
        font-weight: 500;
    }

    .navigation1 ul li a .icon{
        position: relative;
        display: block;
        line-height: 75px;
        font-size: 1.5em;
        text-align: center;
        transition: 0.5s;
        color: var(--clr);
    } 

    .navigation1 ul li.active a .icon{
        transform: translateY(-20px);
    }

    .navigation1 ul li  a .text{
        position: absolute;
        color: var(--clr);
        font-weight: 400;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        transition: 0.5s;
        opacity:0;
        transform: translateY(20px);
    }

    .navigation1 ul li.active a .text{
        opacity:1;
        transform: translateY(10px);
    }

    .indicator{
        position: absolute;
        top:-50%;
        left:20px;
        width: 50px;
        height: 50px;
        background:#29fd53;
        border-radius: 50%;
        border: 6px solid var(--clr);
        transition:0.5s;
    }

    .indicator::before{
        content:'';
        position: absolute;
        top: 45%;
        left: -20px;
        width:20px;
        height: 20px;
        background: transparent;
        border-top-right-radius:20px;
        box-shadow: 0px -10px 0 0 var(--clr);
    }

    .indicator::after{
        content:'';
        position: absolute;
        top: 45%;
        right: -22px;
        width:20px;
        height: 20px;
        background: transparentred;
        border-top-left-radius:20px;
        box-shadow: 0px -10px 0 0 var(--clr);
    }

    .navigation1 ul li:nth-child(1).active ~ .indicator {
        transform: translateX(calc(90px*0));
    }

    .navigation1 ul li:nth-child(2).active ~ .indicator {
        transform: translateX(calc(90px*1));
    }

    .navigation1 ul li:nth-child(3).active ~ .indicator {
        transform: translateX(calc(90px*2));
    }

    .navigation1 ul li:nth-child(4).active ~ .indicator {
        transform: translateX(calc(90px*3));
    }

    .navigation1 ul li:nth-child(5).active ~ .indicator {
        transform: translateX(calc(90px*4));
    } */

    
</style>

{{-- <div class="kotak-utama">
    <div class="kotak">
        <div class="navigation1">
            <ul>
                <li class="list active">
                    <a href="javascript:void(0);">
                        <span class="icon">
                            <ion-icon name="home-outline"></ion-icon>
                        </span>
                        <span class="text">Home</span>
                    </a>
                </li>
                <li class="list">
                    <a href="javascript:void(0);">
                        <span class="icon">
                            <ion-icon name="home-outline"></ion-icon>
                        </span>
                        <span class="text">Home</span>
                    </a>
                </li>
                <li class="list">
                    <a href="javascript:void(0);">
                        <span class="icon">
                            <ion-icon name="home-outline"></ion-icon>
                        </span>
                        <span class="text">Home</span>
                    </a>
                </li>
                <li class="list">
                    <a href="javascript:void(0);">
                        <span class="icon">
                            <ion-icon name="home-outline"></ion-icon>
                        </span>
                        <span class="text">Home</span>
                    </a>
                </li>
                <li class="list">
                    <a href="javascript:void(0);">
                        <span class="icon">
                            <ion-icon name="home-outline"></ion-icon>
                        </span>
                        <span class="text">Home</span>
                    </a>
                </li>
                <div class="indicator">
    
                </div>
            </ul>
        </div>
    </div>
</div>

<script>
    const list = document.querySelectorAll('.list');
    function activeLink(){
        list.forEach((item)=>
        item.classList.remove('active'));
        this.classList.add('active');
    }
    list.forEach((item) =>
    item.addEventListener('click',activeLink));
</script>

<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script> --}}
    
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