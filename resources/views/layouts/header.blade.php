<nav class="header-navbar navbar navbar-expand-lg align-items-center floating-nav navbar-light navbar-shadow">
	<div class="navbar-container d-flex content">
		<div class="bookmark-wrapper d-flex align-items-center">
			<ul class="nav navbar-nav d-xl-none">
				<li class="nav-item"><a class="nav-link menu-toggle" href="javascript:void(0);"><i class="ficon" data-feather="menu"></i></a></li>
			</ul>
			{{-- <ul class="nav navbar-nav bookmark-icons">
				<li class="nav-item d-none d-lg-block"><a class="nav-link" href="app-email.html" data-toggle="tooltip" data-placement="top" title="Email"><i class="ficon" data-feather="mail"></i></a></li>
				<li class="nav-item d-none d-lg-block"><a class="nav-link" href="app-chat.html" data-toggle="tooltip" data-placement="top" title="Chat"><i class="ficon" data-feather="message-square"></i></a></li>
				<li class="nav-item d-none d-lg-block"><a class="nav-link" href="app-calendar.html" data-toggle="tooltip" data-placement="top" title="Calendar"><i class="ficon" data-feather="calendar"></i></a></li>
				<li class="nav-item d-none d-lg-block"><a class="nav-link" href="app-todo.html" data-toggle="tooltip" data-placement="top" title="Todo"><i class="ficon" data-feather="check-square"></i></a></li>
			</ul> --}}
			{{-- <ul class="nav navbar-nav">
				<li class="nav-item d-none d-lg-block"><a class="nav-link bookmark-star"><i class="ficon text-warning" data-feather="star"></i></a>
					<div class="bookmark-input search-input">
						<div class="bookmark-input-icon"><i data-feather="search"></i></div>
						<input class="form-control input" type="text" placeholder="Bookmark" tabindex="0" data-search="search">
						<ul class="search-list search-list-bookmark"></ul>
					</div>
				</li>
			</ul> --}}
			<ul class="nav navbar-nav">
				<li class="nav-item nav-search">
					<a class="nav-link nav-link-search">
						<i class="ficon" data-feather="search"></i>
					</a>
					<div class="search-input">
						<div class="search-input-icon"><i data-feather="search"></i></div>
						<input class="form-control input" type="text" placeholder="Search..." tabindex="-1" data-search="search">
						<div class="search-input-close"><i data-feather="x"></i></div>
						<ul class="search-list search-list-main"></ul>
					</div>
				</li>
			</ul>
		</div>
		<ul class="nav navbar-nav align-items-center ml-auto">
			{{-- ini bagus juga nanti pelajari cara ngerubah languange nya --}}
			{{-- <li class="nav-item dropdown dropdown-language"><a class="nav-link dropdown-toggle" id="dropdown-flag" href="javascript:void(0);" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="flag-icon flag-icon-us"></i><span class="selected-language">English</span></a>
				<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-flag"><a class="dropdown-item" href="javascript:void(0);" data-language="en"><i class="flag-icon flag-icon-us"></i> English</a><a class="dropdown-item" href="javascript:void(0);" data-language="fr"><i class="flag-icon flag-icon-fr"></i> French</a><a class="dropdown-item" href="javascript:void(0);" data-language="de"><i class="flag-icon flag-icon-de"></i> German</a><a class="dropdown-item" href="javascript:void(0);" data-language="pt"><i class="flag-icon flag-icon-pt"></i> Portuguese</a></div>
			</li> --}}
			<li class="nav-item d-none d-lg-block">
				<a class="nav-link nav-link-style">
					<i class="ficon" data-feather="moon"></i>
				</a>
			</li>
			<li class="nav-item dropdown dropdown-user">
				<a class="nav-link dropdown-toggle dropdown-user-link" id="dropdown-user" href="javascript:void(0);" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<div class="user-nav d-sm-flex d-none"><span class="user-name font-weight-bolder">{{ strtoupper(Auth::user()->name) }}</span>
					<span class="user-status">Admin</span></div>
					<span class="avatar">
						<img class="round" src="{{ asset(Auth::user()->filename) }}" alt="avatar" height="40" width="40">
						{{-- <span class="avatar-status-online"></span> --}}
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

<ul class="main-search-list-defaultlist-other-list d-none">
	<li class="auto-suggestion justify-content-between">
		<a class="d-flex align-items-center justify-content-between w-100 py-50">
			<div class="d-flex justify-content-start">
				<span class="mr-75" data-feather="alert-circle"></span><span>No results found.</span>
			</div>
		</a>
	</li>
</ul>