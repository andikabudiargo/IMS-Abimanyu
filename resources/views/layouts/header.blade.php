<nav class="header-navbar navbar navbar-expand-lg align-items-center floating-nav navbar-light navbar-shadow">
	<div class="navbar-container d-flex content">
		<div class="bookmark-wrapper d-flex align-items-center">
			<ul class="nav navbar-nav d-xl-none">
				<li class="nav-item">
					<a class="nav-link menu-toggle" href="javascript:void(0);">
						<i class="ficon" data-feather="menu"></i>
					</a>
				</li>
			</ul>
			<ul class="nav navbar-nav d-xl-none d-md-none" style="margin-left:10px">
				{{-- <li class="nav-item"> --}}
					<img src="{{asset('app-assets/images/logo/logo.png')}}" alt="logo" class="logo" style="height: 50px;">
				{{-- </li> --}}
			</ul>
			{{-- <ul class="nav navbar-nav bookmark-icons">
				<li class="nav-item d-none d-lg-block"><a class="nav-link" href="app-email.html" data-toggle="tooltip" data-placement="top" title="Email"><i class="ficon" data-feather="mail"></i></a></li>
				<li class="nav-item d-none d-lg-block"><a class="nav-link" href="app-chat.html" data-toggle="tooltip" data-placement="top" title="Chat"><i class="ficon" data-feather="message-square"></i></a></li>
				<li class="nav-item d-none d-lg-block"><a class="nav-link" href="app-calendar.html" data-toggle="tooltip" data-placement="top" title="Calendar"><i class="ficon" data-feather="calendar"></i></a></li>
				<li class="nav-item d-none d-lg-block"><a class="nav-link" href="app-todo.html" data-toggle="tooltip" data-placement="top" title="Todo"><i class="ficon" data-feather="check-square"></i></a></li>
			</ul> --}}
			<ul class="nav navbar-nav ml-1">
				<li class="nav-item d-none d-lg-block" >
					<img src="{{asset('app-assets/images/logo/logo.png')}}" alt="logo" class="logo" style="height: 50px;">
				</li>
			</ul>
			<ul class="nav navbar-nav align-items-end ml-2">
				<li class="nav-item d-none d-lg-block  mt-1">
					@if( env('APP_ENVIRONMENT') )
						<h2> {{ env('APP_ENVIRONMENT') }}</h2>
					@endif
				</li>
			</ul>
		</div>
		<ul class="nav navbar-nav align-items-center ml-auto">
			{{-- ini bagus juga nanti pelajari cara ngerubah languange nya --}}
			{{-- <li class="nav-item dropdown dropdown-language"><a class="nav-link dropdown-toggle" id="dropdown-flag" href="javascript:void(0);" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="flag-icon flag-icon-us"></i><span class="selected-language">English</span></a>
				<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-flag"><a class="dropdown-item" href="javascript:void(0);" data-language="en"><i class="flag-icon flag-icon-us"></i> English</a><a class="dropdown-item" href="javascript:void(0);" data-language="fr"><i class="flag-icon flag-icon-fr"></i> French</a><a class="dropdown-item" href="javascript:void(0);" data-language="de"><i class="flag-icon flag-icon-de"></i> German</a><a class="dropdown-item" href="javascript:void(0);" data-language="pt"><i class="flag-icon flag-icon-pt"></i> Portuguese</a></div>
			</li> --}}
			@include('layouts.notification')
			<li class="nav-item d-none d-lg-block">
				<a class="nav-link nav-link-style">
					<i class="ficon" data-feather="moon"></i>
				</a>
			</li>
			{{-- <li class="nav-item nav-search"><a class="nav-link nav-link-search"><i class="ficon" data-feather="search"></i></a>
				<div class="search-input">
					<div class="search-input-icon"><i data-feather="search"></i></div>
					<input class="form-control input" type="text" placeholder="Search..." tabindex="-1" data-search="search">
					<div class="search-input-close"><i data-feather="x"></i></div>
					<ul class="search-list search-list-main"></ul>
				</div>
			</li> --}}
			<li class="nav-item dropdown dropdown-user">
				<a class="nav-link dropdown-toggle dropdown-user-link" id="dropdown-user" href="javascript:void(0);" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<div class="user-nav d-sm-flex d-none">
						<span class="user-name font-weight-bolder">{{ strtoupper(Auth::user()->name) }}</span>
						<span class="user-status">{{ strtoupper(Auth::user()->username) }}</span>
					</div>
					<span class="avatar">
						<img class="round" src="{{ asset(Auth::user()->filename) }}" 
							onerror="this.src='{{ asset('app-assets/images/avatars/default.png') }}';" 
							alt="avatar" height="40" width="40">
						<span class="online"></span>
						{{-- <span class="avatar-status-offline"></span> --}}
					</span>
				</a>
				<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-user">
					<a class="dropdown-item" href="{{ route('user.profile') }}">
						<i class="mr-50" data-feather="user"></i> Profile
					</a>
					<div class="dropdown-divider"></div>
					<a class="dropdown-item" href="javascript:void(0);" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
						<i class="mr-50" data-feather="power"></i> Logout
					</a>
				</div>
			</li>
		</ul>
	</div>
</nav>

<ul class="main-search-list-defaultlist d-none">	
</ul>

<script type="text/javascript">
	const online = document.querySelector(".online");

	window.addEventListener("load", async (event) => {
		const status = navigator.onLine;
		if (status){
			online.classList.remove('avatar-status-offline');
			online.classList.add('avatar-status-online')
		}else{
			online.classList.remove('avatar-status-online')
			online.classList.add('avatar-status-offline');
		}
	});

	const checkOnlineStatus = async () => {
		try {
			const online = await navigator.onLine;
			return online // either true or false
		} catch (err) {
			return false; // definitely offline
		}
	};

	setInterval(async () => {
		const result = await checkOnlineStatus();
		if (result){
			online.classList.remove('avatar-status-offline');
			online.classList.add('avatar-status-online')
		}else{
			online.classList.remove('avatar-status-online')
			online.classList.add('avatar-status-offline');
		}
	}, 30000);

</script>
