<div class="main-menu menu-fixed menu-dark menu-accordion menu-shadow" data-scroll-to-active="true">
  <div class="navbar-header">
      <ul class="nav navbar-nav flex-row">
          <li class="nav-item mr-auto">
            <a class="navbar-brand" href="{{route('home')}}">
              <span class="brand-logo">
                <div class="brand-wrapper">
                  <img src="{{asset('app-assets/images/logo/looping_icon.ico')}}" alt="logo" class="logo" >
                </div>
              </span>
              <h2 class="brand-text">IMS</h2>
            </a>
          </li>
          <li class="nav-item nav-toggle">
            <a class="nav-link modern-nav-toggle pr-0" data-toggle="collapse">
              <i class="d-block d-xl-none text-primary toggle-icon font-medium-4" data-feather="x"></i>
              <i class="d-none d-xl-block collapse-toggle-icon font-medium-4  text-primary" data-feather="disc" data-ticon="disc"></i>
            </a>
          </li>
      </ul>
  </div>
  <div class="shadow-bottom"></div>
  <div class="main-menu-content">
      <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
          <li class=" nav-item">
            <a class="d-flex align-items-center" href="{{ route('home') }}">
              <i data-feather="home">
              </i>
              <span class="menu-title text-truncate" data-i18n="Dashboards">Dashboards</span>
            </a>
          </li>
          <li class=" navigation-header"><span data-i18n="Apps &amp; Pages">Apps &amp; Pages</span><i data-feather="more-horizontal"></i>
          </li>
          <li class=" {{ in_array(\Request::segment(1), ['customers','suppliers']) ? 'active' : '' }} nav-item">
            <a class="d-flex align-items-center" href="javascript:void(0);">
              <i data-feather="book"></i>
              <span class="menu-title text-truncate" data-i18n="Form Elements">Master Data
              </span>
            </a>
            <ul class="menu-content">
                @can('customer-index')
                <li class="{{ \Request::segment(1) == 'customers'  ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('customers.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Customer</span>
                  </a>
                </li>
                @endcan
            </ul>
            <ul class="menu-content">
              @can('supplier-index')
              <li class="{{ \Request::segment(1) == 'suppliers'  ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('suppliers.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">Supplier</span>
                </a>
              </li>
              @endcan
          </ul>
          </li>
          <li class=" {{ in_array(\Request::segment(1), []) ? 'active' : '' }} nav-item">
            <a class="d-flex align-items-center" href="javascript:void(0);">
              <i data-feather="box"></i>
              <span class="menu-title text-truncate" data-i18n="Form Elements">Inventory
              </span>
            </a>
            <ul class="menu-content">
                @can('user-index')
                <li class="{{ \Request::segment(1) == '' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('users.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Master Data</span>
                  </a>
                @endcan
                </li>
                @can('permission-index')
                <li class="{{ \Request::segment(1) == '' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('permissions.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Kelompok</span>
                  </a>
                </li>
                @endcan
                @can('role-index')
                <li class="{{ \Request::segment(1) == '' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('roles.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Jenis barang</span>
                  </a>
                </li>
                @endcan
                @can('role-index')
                <li class="{{ \Request::segment(1) == '' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('roles.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">No Rak</span>
                  </a>
                </li>
                @endcan
            </ul>
          </li>
          <li class=" {{ in_array(\Request::segment(1), []) ? 'active' : '' }} nav-item">
            <a class="d-flex align-items-center" href="javascript:void(0);">
              <i data-feather="share"></i>
              <span class="menu-title text-truncate" data-i18n="Form Elements">Pembelian
              </span>
            </a>
            <ul class="menu-content">
                @can('user-index')
                <li class="{{ \Request::segment(1) == '' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('users.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Purchase Order</span>
                  </a>
                @endcan
                </li>
                @can('permission-index')
                <li class="{{ \Request::segment(1) == '' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('permissions.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Lists PO</span>
                  </a>
                </li>
                @endcan
                @can('role-index')
                <li class="{{ \Request::segment(1) == '' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('roles.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Jenis barang</span>
                  </a>
                </li>
                @endcan
                @can('role-index')
                <li class="{{ \Request::segment(1) == '' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('roles.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">No Rak</span>
                  </a>
                </li>
                @endcan
            </ul>
          </li>
          <li class=" {{ in_array(\Request::segment(1), []) ? 'active' : '' }} nav-item">
            <a class="d-flex align-items-center" href="javascript:void(0);">
              <i data-feather="shopping-cart"></i>
              <span class="menu-title text-truncate" data-i18n="Form Elements">Penjualan
              </span>
            </a>
            <ul class="menu-content">
                @can('user-index')
                <li class="{{ \Request::segment(1) == '' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('users.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Purchase Order</span>
                  </a>
                @endcan
                </li>
                @can('permission-index')
                <li class="{{ \Request::segment(1) == '' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('permissions.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Lists PO</span>
                  </a>
                </li>
                @endcan
                @can('role-index')
                <li class="{{ \Request::segment(1) == '' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('roles.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Jenis barang</span>
                  </a>
                </li>
                @endcan
                @can('role-index')
                <li class="{{ \Request::segment(1) == '' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('roles.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">No Rak</span>
                  </a>
                </li>
                @endcan
            </ul>
          </li>
          <li class=" {{ in_array(\Request::segment(1), ['setting','users','roles','permissions']) ? 'active' : '' }} nav-item">
            <a class="d-flex align-items-center" href="javascript:void(0);">
              <i data-feather="settings"></i>
              <span class="menu-title text-truncate" data-i18n="Form Elements">Setting
              </span>
            </a>
            <ul class="menu-content">
                @can('user-index')
                <li class="{{ \Request::segment(1) == 'users' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('users.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Users</span>
                  </a>
                @endcan
                </li>
                {{-- <li class="{{ \Request::segment(1) == 'menu' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('show.menu') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Menu</span>
                  </a>
                </li> --}}
                @can('permission-index')
                <li class="{{ \Request::segment(1) == 'permissions' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('permissions.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Permission</span>
                  </a>
                </li>
                @endcan
                @can('role-index')
                <li class="{{ \Request::segment(1) == 'roles' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('roles.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Roles</span>
                  </a>
                </li>
                @endcan
            </ul>
          </li>
          <li class=" {{ in_array(\Request::segment(1), ['logActivity']) ? 'active' : '' }} nav-item">
            <a class="d-flex align-items-center" href="javascript:void(0);">
              <i data-feather="briefcase"></i>
              <span class="menu-title text-truncate" data-i18n="Form Elements">Log
              </span>
            </a>
            <ul class="menu-content">
                @can('log-index')
                <li class="{{ \Request::segment(1) == 'logActivity' && \Request::segment(2) == '' ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('log.activity') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Activity</span>
                  </a>
                @endcan
                </li>
            </ul>
          </li>
          <li class="disabled nav-item">
            <a class="d-flex align-items-center" href="#">
              <i data-feather="eye-off"></i>
              <span class="menu-title text-truncate" data-i18n="Disabled Menu">Disabled Menu</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="d-flex align-items-center" href="javascript:void(0);" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
              <i data-feather="power"></i>
              <span class="menu-title text-truncate" data-i18n="Table">Logout</span>
              <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                {{ csrf_field() }}
              </form>
            </a>
          </li>
      </ul>
  </div>
</div>

  