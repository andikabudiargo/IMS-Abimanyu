<div class="main-menu menu-fixed menu-dark menu-accordion menu-shadow" data-scroll-to-active="true">
  <div class="navbar-header">
      <ul class="nav navbar-nav flex-row">
          <li class="nav-item mr-auto">
            <a class="navbar-brand" href="{{route('home')}}">
              <span class="brand-logo">
                <div class="brand-wrapper">
                  @if( env('APP_ENV') == 'local' )
                  <img src="{{asset('app-assets/images/logo/logo1.png')}}" alt="logo" class="logo" >
                  @else
                  <img src="{{asset('app-assets/images/logo/looping_icon.ico')}}" alt="logo" class="logo" >
                  @endif
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
        <li class=" navigation-header"><span data-i18n="Inventory">Inventory</span><i data-feather="more-horizontal"></i>
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
            @can('article-request-index')
            <li class="{{ \Request::is(['articles/request','articles/request/create','articles/request/edit','articles/request/show']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('article.request') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Article Request</span>
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
            {{-- <li class="{{ \Request::segment(1) == 'stockTake' ? 'active' : '' }} disabled">
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
            </li> --}}
          </ul>
        </li>
        <li class=" navigation-header"><span data-i18n="Marketing">Marketing</span><i data-feather="more-horizontal"></i>
        </li>
        <li class=" {{ in_array(\Request::segment(1), ['salesOrders','customers','targetSo','forecastSales','salesOrderReport']) ? 'active' : '' }} nav-item">
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
            @can('targetSo-index')
            <li class="{{ \Request::segment(1) == 'targetSo' ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('targetSo.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Target SO</span>
              </a>
            </li>
            @endcan
            
            <li class="{{ \Request::is(['forecastSales','forecastSales/create']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('forecastSales.index') }} ">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Forecasting</span>
              </a>
            </li>

            @can('customer-index')
            <li class="{{ \Request::segment(1) == 'customers'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('customers.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Customer</span>
              </a>
            </li>
            @endcan

            @can('salesOrder-index')
            <li class="{{ \Request::is(['salesOrderReport']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('salesOrder.report') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Report SO</span>
              </a>
            </li>
            @endcan

            {{-- <li class="{{ \Request::segment(1) == 'invoice' ? 'active' : '' }} " >
              <a class="d-flex align-items-center" href="{{ route('invoice.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Invoice Customer</span>
              </a>
            </li> --}}
            {{-- <li class="{{ \Request::segment(1) == 'stockTake' ? 'active' : '' }} disabled">
              <a class="d-flex align-items-center" href="{{ route('articles.index') }} ">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Invoice</span>
              </a>
            </li> --}}
          </ul>
        </li>
        <li class=" {{ in_array(\Request::segment(1), ['purchaseOrders','suppliers','purchaseRequests','forecastPurchase']) ? 'active' : '' }} nav-item">
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
            <li class="{{ \Request::is(['forecastPurchase','forecastPurchase/create']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('forecastPurchase.index') }} ">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Forecasting</span>
              </a>
            </li>
            @can('supplier-index')
            <li class="{{ \Request::segment(1) == 'suppliers'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('suppliers.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Supplier</span>
              </a>
            </li>
            @endcan
            <li class="{{ \Request::is(['deliveryInstruction']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('deliveryInstruction.index') }} ">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Delivery Instruction</span>
              </a>
            </li>

            @can('purchaseOrder-index')
            {{-- <li class="{{ \Request::segment(1) == 'purchaseOrders' ? 'active' : '' }}"> --}}
            <li class="{{ \Request::is(['purchaseOrdersReport']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('purchaseOrders.report') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Report PO</span>
              </a>
            </li>
            @endcan

          </ul>
        </li>
        <li class=" navigation-header"><span data-i18n="Logistic">Logistic</span><i data-feather="more-horizontal"></i>
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
                <span class="menu-item text-truncate" data-i18n="Input">Receiving</span>
              </a>
            </li>
            @endcan
            {{-- @can('receivingRm-index')
            <li class="{{ \Request::segment(1) == 'receivingsRm' ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('receivingsRm.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Receiving RM</span>
              </a>
            </li>
            @endcan --}}
          </ul>
        </li>
        <li class=" {{ in_array(\Request::segment(1), ['warehouse','transferIn']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather='box'></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">Warehouse
            </span>
          </a>
          <ul class="menu-content">
            {{-- @can('warehouse-index')
            <li class="{{ \Request::is(['warehouse']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('warehouse.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Warehouse</span>
              </a>
            </li>
            @endcan --}}
            @can('warehouse-index')
            <li class="{{ \Request::is(['warehouse/article']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('warehouse.article') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Stock</span>
              </a>
            </li>
            @endcan
            @can('transferIn-index')
            <li class="{{ \Request::is(['transferIn','transferIn/create','transferIn/show']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('transferIn.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Transfer in</span>
              </a>
            </li>
            @endcan
            @can('transferOut-index')
            <li class="{{ \Request::is(['transferOut','transferOut/create','transferOut/show']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('transferOut.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Transfer out</span>
              </a>
            </li>
            @endcan
          </ul>
        </li>
        
        {{-- tutup sementara --}}
        {{-- <li class=" {{ in_array(\Request::segment(1), ['subContracts']) ? 'active' : '' }} nav-item">
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
        </li> --}}

        <li class=" {{ in_array(\Request::segment(1), ['delivery','dnReceipt','deliveryReport']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather='truck'></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">Delivery
            </span>
          </a>
          <ul class="menu-content">

            @can('delivery-index')
            <li class="{{ \Request::segment(1) == 'delivery' ? 'active' : '' }} " >
              <a class="d-flex align-items-center" href="{{ route('delivery.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Delivery Note">Delivery Note</span>
              </a>
            </li>
            @endcan

            @can('dnReceipt-index')
            <li class="{{ \Request::is(['dnReceipt','dnReceipt/create']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('dnReceipt.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Delivery Receipt">DN Received</span>
              </a>
            </li>
            @endcan

            @can('delivery-report')
            <li class="{{ \Request::segment(1) == 'deliveryReport' ? 'active' : '' }} " >
              <a class="d-flex align-items-center" href="{{ route('delivery.report') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="DN Report">DN Report</span>
              </a>
            </li>
            @endcan

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
        <li class=" navigation-header"><span data-i18n="PPIC">PPIC</span><i data-feather="more-horizontal"></i>
        </li>
        <li class=" {{ in_array(\Request::segment(1), ['boms','bom','workingOrders','workOrderSheet','deliveryPlan','wosMixing']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather='tool'></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">PPIC
            </span>
          </a>
          <ul class="menu-content">
            {{-- @can('workingOrder-index')
            <li class="{{ \Request::segment(1) == 'deliveryPlan' ? 'active' : '' }} " >
              <a class="d-flex align-items-center" href="{{ route('deliveryPlan.create') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Delivery Plan</span>
              </a>
            </li>
            @endcan --}}

            {{-- @can('production-index')
            <li class="{{ \Request::segment(1) == 'production' ? 'active' : '' }}" >
              <a class="d-flex align-items-center" href="{{ route('production.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Production</span>
              </a>
            </li>
            @endcan --}}
            
            @can('workingOrder-index')
            <li class="{{ in_array(\Request::segment(1),['workOrderSheet','workOrderSheet/create','workOrderSheet/edit']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('workingOrderSheets.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Work Order Sheet</span>
              </a>
            </li>
            @endcan           

            @can('wosMixing-index')
            <li class="{{ \Request::segment(1) == 'wosMixing' ? 'active' : '' }}" >
              <a class="d-flex align-items-center" href="{{ route('wosMixing.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">WOS Mixing</span>
              </a>
            </li>
            @endcan

            {{-- <li><a class="d-flex align-items-center" href="#"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Wos Mixing">Production</span></a>
              <ul class="menu-content">
                @can('actualLoading-index')
                  <li class="{{ \Request::is(['production/actualLoading','production/actualLoading/create']) ? 'active' : '' }}">
                    <a class="d-flex align-items-center" href="{{ route('production.actualLoading.index') }}">
                      <span class="menu-item text-truncate" data-i18n="Invoice supplier">Actual Loading</span>
                    </a>
                  </li>
                @endcan
                @can('actualFinishGoods-index')
                  <li class="{{ \Request::is(['production/actualFinishGoods']) ? 'active' : '' }}">
                    <a class="d-flex align-items-center" href="{{ route('production.actualFinishGoods.index') }}">
                      <span class="menu-item text-truncate" data-i18n="Proforma Invoice">Actual Finish Goods</span>
                    </a>
                  </li>
                @endcan
              </ul>
            </li> --}}

            {{-- @can('workingOrder-index')
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

            @can('bom-index')
            <li class="{{ \Request::segment(1) == 'bom' ? 'active' : '' }}" >
              <a class="d-flex align-items-center" href="{{ route('bom.report.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">BOM Report</span>
              </a>
            </li>
            @endcan

          </ul>
        </li>
        <li class=" navigation-header"><span data-i18n="Production">Production</span><i data-feather="more-horizontal"></i>
        </li>

        <li class=" {{ in_array(\Request::segment(1), ['actualLoading','actualFinishGoods']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather='repeat'></i>
            <span class="menu-title text-truncate" data-i18n="Production">Production
            </span>
          </a>
          <ul class="menu-content">

            @can('actualLoading-index')
            <li class="{{ \Request::is(['actualLoading','actualLoading/create','actualLoading/edit','actualLoading/show']) ? 'active' : '' }}" >
              <a class="d-flex align-items-center" href="{{ route('production.actualLoading.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Actual Loading">Actual Loading</span>
              </a>
            </li>
            @endcan

            @can('actualLoading-index')
            <li class="{{ \Request::is(['actualFinishGoods','actualFinishGoods/edit','actualFinishGoods/show']) ? 'active' : '' }}" >
              <a class="d-flex align-items-center" href="{{ route('production.actualFinishGoods.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Actual Loading">Actual Finish Goods</span>
              </a>
            </li>
            @endcan
          </ul>
        </li>

        @can('accounting-menu')
          <li class=" navigation-header"><span data-i18n="Finance Accounting">Finance Accounting</span><i data-feather="more-horizontal"></i>
          </li>
          <li class=" {{ in_array(\Request::segment(1), ['aps','banks','pettyCash','bankReceipt','invoice','kasPenerimaan','kasKeluar','bankPenerimaan','bankKeluar','deliveryReportAcc','deliveryReportSoAcc','jurnalUmum']) ? 'active' : '' }} nav-item">
            <a class="d-flex align-items-center" href="javascript:void(0);">
              <i data-feather="dollar-sign"></i>
              <span class="menu-title text-truncate" data-i18n="Form Elements">Finance & acc
              </span>
            </a>
            <ul class="menu-content">
              
              @can('ap-index')
                <li><a class="d-flex align-items-center" href="#"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Invoice">Invoice</span></a>
                  <ul class="menu-content">
                    
                      <li class="{{ \Request::segment(1) == 'aps'  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('aps.index') }}"><span class="menu-item text-truncate" data-i18n="Invoice supplier">Invoice Supplier</span></a>
                      </li>
                      <li class="{{ \Request::segment(1) == 'invoice' ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('invoice.index') }}"><span class="menu-item text-truncate" data-i18n="Invoice customer">Invoice Customer</span></a>
                      </li>
                  </ul>
                </li>
              @endcan
              @can('ap-index')
                <li><a class="d-flex align-items-center" href="#"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Account Setting">Kas</span></a>
                  <ul class="menu-content">
                    {{-- @can('ap-index') --}}
                      <li class="{{ \Request::is(['kasPenerimaan','kasPenerimaan/create','kasPenerimaan/show','kasPenerimaan/edit'])  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('kasPenerimaan.index') }}"><span class="menu-item text-truncate" data-i18n="Penerimaan">Penerimaan</span></a>
                      </li>
                    {{-- @endcan --}}
                    {{-- @can('ap-index') --}}
                      <li class="{{ \Request::is(['kasKeluar','kasKeluar/create','kasKeluar/show','kasKeluar/edit'])  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('kasKeluar.index') }}"><span class="menu-item text-truncate" data-i18n="Pembayaran">Pembayaran</span></a>
                      </li>
                    {{-- @endcan --}}
                  </ul>
                </li>
              @endcan
              @can('ap-index')
                <li><a class="d-flex align-items-center" href="#"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Account Setting">Bank</span></a>
                  <ul class="menu-content">
                    {{-- @can('ap-index') --}}
                      <li class="{{ \Request::is(['bankPenerimaan','bankPenerimaan/create','bankPenerimaan/show','bankPenerimaan/edit'])  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('bankPenerimaan.index') }}"><span class="menu-item text-truncate" data-i18n="Penerimaan">Penerimaan</span></a>
                      </li>
                    {{-- @endcan --}}
                    {{-- @can('ap-index') --}}
                      <li class="{{ \Request::is(['bankKeluar','bankKeluar/create','bankKeluar/show','bankKeluar/edit'])  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('bankKeluar.index') }}"><span class="menu-item text-truncate" data-i18n="Pembayaran">Pembayaran</span></a>
                      </li>
                    {{-- @endcan --}}
                  </ul>
                </li>
              @endcan

              <li><a class="d-flex align-items-center" href="#"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Account Report">Report</span></a>
                <ul class="menu-content">
                  @can('delivery-report-acc')
                    <li class="{{ \Request::is(['deliveryReportAcc'])  ? 'active' : '' }}">
                      <a class="d-flex align-items-center" href="{{ route('delivery.report.acc') }}">
                        <span class="menu-item text-truncate" data-i18n="Dn Report Acc">Dn Report</span>
                      </a>
                    </li>
                  @endcan
                  
                  @can('delivery-report-acc')
                    <li class="{{ \Request::is(['deliveryReportSoAcc'])  ? 'active' : '' }}">
                      <a class="d-flex align-items-center" href="{{ route('delivery.report.so.acc') }}">
                        <span class="menu-item text-truncate" data-i18n="Dn Report Acc">SO Report</span>
                      </a>
                    </li>
                  @endcan
                </ul>
              </li>

              @can('bank-index')
                <li class="{{ \Request::segment(1) == 'jurnalUmum'  ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('jurnalUmum.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">General Journal</span>
                  </a>
                </li>
              @endcan
              
              @can('ap-index')
              <li class="{{ \Request::segment(1) == 'bukuBesar'  ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('bukuBesar.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">Buku Besar</span>
                </a>
              </li>
              @endcan

              {{-- @can('bank-index')
                <li class="{{ \Request::segment(1) == 'banks'  ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('banks.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Input">Bank Master</span>
                  </a>
                </li>
              @endcan --}}

            </ul>
          </li>
          <li class=" {{ in_array(\Request::segment(1), ['accounts','groups','accTypes','account']) ? 'active' : '' }} nav-item">
            <a class="d-flex align-items-center" href="javascript:void(0);">
              <i data-feather="book"></i>
              <span class="menu-title text-truncate" data-i18n="Form Elements">COA
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
              {{-- <li><a class="d-flex align-items-center" href="#"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Account Setting">Account default</span></a>
                <ul class="menu-content">
                    <li class="{{ \Request::is(['account/setting/mataUang'])  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('accountSetting.mataUang') }}"><span class="menu-item text-truncate" data-i18n="Mata Uang">Mata uang</span></a>
                    </li>
                    <li class="{{ \Request::is(['account/setting/barang']) ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('accountSetting.barang') }}"><span class="menu-item text-truncate" data-i18n="Barang">Barang</span></a>
                    </li>
                </ul>
              </li> --}}
              {{-- @can('group-index')
              <li class="{{ \Request::segment(1) == 'groups'  ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('groups.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">Group Account</span>
                </a>
              </li>
              @endcan --}}
              {{-- @can('jobPosition-index')
              <li class="{{ \Request::segment(1) == 'jobPositionsjjj'  ? 'active' : '' }} disabled">
                <a class="d-flex align-items-center" href="{{ route('jobPositions.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">Reports</span>
                </a>
              </li>
              @endcan --}}
            </ul>
          </li>
        @endcan

        <li class=" navigation-header"><span data-i18n="hrm">HRM</span><i data-feather="more-horizontal"></i>
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
        <li class=" navigation-header"><span data-i18n="Settings">Settings</span><i data-feather="more-horizontal"></i>
        </li>
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
        <li class=" navigation-header"><span data-i18n="Logs"></span><i data-feather="more-horizontal"></i>
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

  