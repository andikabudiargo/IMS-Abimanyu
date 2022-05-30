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
        <li class=" {{ in_array(\Request::segment(1), ['articles','articles','articles/create','articles/edit','articles/show','stockTake','uoms','uomCons','groupMaterials','articleTypes']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather="box"></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">Inventory
            </span>
          </a>
          <ul class="menu-content">
            @can('article-index')
            <li class="{{ \Request::is(['articles','articles/create','articles/edit','articles/show']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('articles.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Article</span>
              </a>
            </li>
            @endcan
            @can('uom-index')
            <li class="{{ \Request::segment(1) == 'uoms'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('uoms.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">UOM</span>
              </a>
            </li>
            @endcan
            @can('uomCon-index')
            <li class="{{ \Request::segment(1) == 'uomCons'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('uomCons.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">UOM Conversion</span>
              </a>
            </li>
            @endcan
            @can('groupMaterial-index')
            <li class="{{ \Request::segment(1) == 'groupMaterials'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('groupMaterials.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Group of Material</span>
              </a>
            </li>
            @endcan
            @can('articleType-index')
            <li class="{{ \Request::segment(1) == 'articleTypes'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('articleTypes.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Article Type</span>
              </a>
            </li>
            @endcan
            <li class="{{ \Request::segment(1) == 'stockTake' ? 'active' : '' }} disabled">
              <a class="d-flex align-items-center" href="{{ route('articles.index') }} ">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Stocktake</span>
              </a>
            </li>
            <li class="{{ \Request::segment(1) == 'stockTake' ? 'active' : '' }} disabled">
              <a class="d-flex align-items-center" href="{{ route('articles.index') }} ">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Adjustment</span>
              </a>
            </li>
          </ul>
        </li>
        <li class=" {{ in_array(\Request::segment(1), ['salesOrders','customers','invoice']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather='layers'></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">Sales
            </span>
          </a>
          <ul class="menu-content">
            @can('salesOrder-index')
            <li class="{{ \Request::segment(1) == 'salesOrders' ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('salesOrders.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Sales Order</span>
              </a>
            </li>
            @endcan
            @can('customer-index')
            <li class="{{ \Request::segment(1) == 'customers'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('customers.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Customer</span>
              </a>
            </li>
            @endcan
            <li class="{{ \Request::segment(1) == 'invoice' ? 'active' : '' }} " >
              <a class="d-flex align-items-center" href="{{ route('invoice.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Invoice</span>
              </a>
            </li>

            {{-- <li class="{{ \Request::segment(1) == 'stockTake' ? 'active' : '' }} disabled">
              <a class="d-flex align-items-center" href="{{ route('articles.index') }} ">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Invoice</span>
              </a>
            </li> --}}
          </ul>
        </li>
        <li class=" {{ in_array(\Request::segment(1), ['purchaseOrders','suppliers','purchaseRequests']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather='shopping-cart'></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">Purchasing
            </span>
          </a>
          <ul class="menu-content">
            @can('purchaseRequest-index')
            <li class="{{ \Request::segment(1) == 'purchaseRequests' ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('purchaseRequests.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Purchase Request</span>
              </a>
            </li>
            @endcan
            @can('purchaseOrder-index')
            <li class="{{ \Request::segment(1) == 'purchaseOrders' ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('purchaseOrders.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Purchase Order</span>
              </a>
            </li>
            @endcan
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
        <li class=" {{ in_array(\Request::segment(1), ['receivings','receivingsRm']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather='package'></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">Receiving
            </span>
          </a>
          <ul class="menu-content">
            @can('receiving-index')
            <li class="{{ \Request::segment(1) == 'receivings' ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('receivings.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Receiving PO</span>
              </a>
            </li>
            @endcan
            @can('receivingRm-index')
            <li class="{{ \Request::segment(1) == 'receivingsRm' ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('receivingsRm.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Receiving RM</span>
              </a>
            </li>
            @endcan
          </ul>
        </li>
        <li class=" {{ in_array(\Request::segment(1), ['boms','workingOrders','workingOrderSheets','deliveryPlan']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather='tool'></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">PPIC
            </span>
          </a>
          <ul class="menu-content">
            @can('workingOrder-index')
            <li class="{{ \Request::segment(1) == 'deliveryPlan' ? 'active' : '' }} " >
              <a class="d-flex align-items-center" href="{{ route('deliveryPlan.create') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Delivery Plan</span>
              </a>
            </li>
            @endcan
            @can('workingOrder-index')
            <li class="{{ \Request::segment(1) == 'production' ? 'active' : '' }}" >
              <a class="d-flex align-items-center" href="{{ route('production.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Production</span>
              </a>
            </li>
            @endcan
            
            {{-- @can('workingOrder-index')
            <li class="{{ \Request::segment(1) == 'workingOrderSheets' ? 'active' : '' }}" >
              <a class="d-flex align-items-center" href="{{ route('workingOrderSheets.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Working Order Sheet</span>
              </a>
            </li>
            @endcan
            @can('workingOrder-index')
            <li class="{{ \Request::segment(1) == 'workingOrders' ? 'active' : '' }}" >
              <a class="d-flex align-items-center" href="{{ route('workingOrders.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Working Order</span>
              </a>
            </li>
            @endcan --}}
            @can('bom-index')
            <li class="{{ \Request::segment(1) == 'boms' ? 'active' : '' }}" >
              <a class="d-flex align-items-center" href="{{ route('boms.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Bill Of Material</span>
              </a>
            </li>
            @endcan
          </ul>
        </li>
        <li class=" {{ in_array(\Request::segment(1), ['subContracts']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather='link-2'></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">Subcontracting
            </span>
          </a>
          <ul class="menu-content">
            @can('subContract-index')
            <li class="{{ \Request::segment(1) == 'subContracts1' ? 'active' : '' }} disabled" >
              <a class="d-flex align-items-center" href="{{ route('subContracts.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">PO</span>
              </a>
            </li>
            @endcan
            @can('subContract-create')
            <li class="{{ \Request::segment(1) == 'subContracts' ? 'active' : '' }}" >
              <a class="d-flex align-items-center" href="{{ route('subContract.delivery') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Delivery</span>
              </a>
            </li>
            @endcan
            @can('subContract-index')
            <li class="{{ \Request::segment(1) == 'subContracts1' ? 'active' : '' }} disabled" >
              <a class="d-flex align-items-center" href="{{ route('subContracts.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Receiving</span>
              </a>
            </li>
            @endcan
          </ul>
        </li>
        <li class=" {{ in_array(\Request::segment(1), ['delivery']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather='truck'></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">Shipping
            </span>
          </a>
          <ul class="menu-content">
            <li class="{{ \Request::segment(1) == 'delivery' ? 'active' : '' }} " >
              <a class="d-flex align-items-center" href="{{ route('delivery.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Delivery</span>
              </a>
            </li>

            {{-- @can('purchaseOrder-index')
            <li class="{{ \Request::segment(1) == 'purchaseOrdersSSS' ? 'active' : '' }} disabled">
              <a class="d-flex align-items-center" href="{{ route('purchaseOrders.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Delivery Order</span>
              </a>
            </li>
            @endcan --}}
          </ul>
        </li>
        <li class=" {{ in_array(\Request::segment(1), ['aps','banks','pettyCash','proforma','bankReceipt']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather="dollar-sign"></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">Finance
            </span>
          </a>
          <ul class="menu-content">
            {{-- @can('finance-index')
            <li class="{{ \Request::segment(1) == 'aps'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('aps.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Account Payable</span>
              </a>
            </li>
            @endcan --}}
            @can('ap-index')
            <li class="{{ \Request::segment(1) == 'aps'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('aps.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Input Invoice</span>
              </a>
            </li>
            @endcan
            {{-- @can('finance-index')
            <li class="{{ \Request::segment(1) == 'aps'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('aps.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Invoice Correction</span>
              </a>
            </li>
            @endcan --}}
            @can('ap-proforma-index')
            <li class="{{ \Request::segment(1) == 'proforma'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('apProforma.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Proforma Invoice</span>
              </a>
            </li>
            @endcan
            @can('finance-index')
            <li class="{{ \Request::segment(1) == 'accountPayable'  ? 'active' : '' }} disabled">
              <a class="d-flex align-items-center" href="{{ route('accTypes.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Tax</span>
              </a>
            </li>
            @endcan
            @can('finance-index')
            <li class="{{ \Request::segment(1) == 'disbursement'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('disbursement.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Bank Disbursement</span>
              </a>
            </li>
            @endcan
            @can('finance-index')
            <li class="{{ \Request::segment(1) == 'bankReceipt'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('bankReceipt.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Bank Receipt</span>
              </a>
            </li>
            @endcan
            @can('pettyCash-index')
            <li class="{{ \Request::segment(1) == 'pettyCashs'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('pettyCashs.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Petty Cash</span>
              </a>
            </li>
            @endcan
            @can('bank-index')
            <li class="{{ \Request::segment(1) == 'banks'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('banks.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Bank Master</span>
              </a>
            </li>
            @endcan
          </ul>
        </li>
        <li class=" {{ in_array(\Request::segment(1), ['accounts','groups','accTypes']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather="book"></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">Accounting
            </span>
          </a>
          <ul class="menu-content">
            @can('accType-index')
            <li class="{{ \Request::segment(1) == 'accounts'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('accounts.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Account</span>
              </a>
            </li>
            @endcan
            @can('accType-index')
            <li class="{{ \Request::segment(1) == 'accTypes'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('accTypes.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Account Type</span>
              </a>
            </li>
            @endcan
            @can('group-index')
            <li class="{{ \Request::segment(1) == 'groups'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('groups.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Group Account</span>
              </a>
            </li>
            @endcan
            @can('jobPosition-index')
            <li class="{{ \Request::segment(1) == 'jobPositionsjjj'  ? 'active' : '' }} disabled">
              <a class="d-flex align-items-center" href="{{ route('jobPositions.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Reports</span>
              </a>
            </li>
            @endcan
          </ul>
        </li>
        <li class=" {{ in_array(\Request::segment(1), ['employees','jobPositions','depts']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather='users'></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">HRD
            </span>
          </a>
          <ul class="menu-content">
            {{-- @can('employee-index')
            <li class="{{ \Request::segment(1) == 'employees'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('employees.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Employee</span>
              </a>
            </li>
            @endcan --}}
            @can('department-index')
            <li class="{{ \Request::segment(1) == 'depts'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('depts.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Departement</span>
              </a>
            </li>
            @endcan
            {{-- @can('jobPosition-index')
            <li class="{{ \Request::segment(1) == 'jobPositions'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('jobPositions.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Position</span>
              </a>
            </li>
            @endcan --}}
            {{-- @can('jobPosition-index')
            <li class="{{ \Request::segment(1) == 'jobPositionsSS'  ? 'active' : '' }} disabled">
              <a class="d-flex align-items-center" href="{{ route('jobPositions.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Attendant</span>
              </a>
            </li>
            @endcan
            @can('jobPosition-index')
            <li class="{{ \Request::segment(1) == 'jobPositionsSS'  ? 'active' : '' }} disabled">
              <a class="d-flex align-items-center" href="{{ route('jobPositions.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Payroll</span>
              </a>
            </li>
            @endcan --}}
          </ul>
        </li>
        {{-- <li class=" {{ in_array(\Request::segment(1), []) ? 'active' : '' }} nav-item">
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
        </li> --}}
        <li class=" {{ in_array(\Request::segment(1), ['setting','users','roles','permissions','company','approval']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather="settings"></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">Setting
            </span>
          </a>
          <ul class="menu-content">
              @can('company-index')
              <li class="{{ \Request::segment(1) == 'company' && \Request::segment(2) == '' ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('company.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">Company</span>
                </a>
              </li>
              @endcan
              @can('approval-index')
              <li class="{{ \Request::segment(1) == 'approval' && \Request::segment(2) == '' ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('approval.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">Approval</span>
                </a>
              </li>
              @endcan
              @can('user-index')
              <li class="{{ \Request::segment(1) == 'users' && \Request::segment(2) == '' ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('users.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">Users</span>
                </a>
              </li>
              @endcan
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
              @can('setting-index')
              <li class="{{ \Request::segment(1) == 'setting' && \Request::segment(2) == '' ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('setting.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">System Setting</span>
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
        {{-- <li class="disabled nav-item">
          <a class="d-flex align-items-center" href="#">
            <i data-feather="eye-off"></i>
            <span class="menu-title text-truncate" data-i18n="Disabled Menu">Disabled Menu</span>
          </a>
        </li> --}}
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

  