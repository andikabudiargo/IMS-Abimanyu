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
           {{-- 
            <li class="{{ \Request::segment(1) == 'uomCons' ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('uomCons.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">UOM Conversion (Lama)</span>
              </a>
            </li>
            --}}
            @can('uomCon-index')
           
             <li class="{{ \Request::segment(1) == 'uomConsv2'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('uomConsv2.index') }}">
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

            <li class="{{ \Request::is(['forecastSales','forecastSales/create','forecastSales/edit','forecastSales/show']) ? 'active' : '' }}">
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

            {{-- @can('conversion-index') --}}
            <li class=" {{ in_array(\Request::segment(1), ['conversion1','conversionSetting1']) ? 'active' : '' }} nav-item">
              <a class="d-flex align-items-center" href="javascript:void(0);">
                <i data-feather="repeat"></i>
                <span class="text-truncate" data-i18n="Form Elements">Conversion
                </span>
              </a>
              <ul class="menu-content">
                <li class="{{ \Request::segment(1) == 'conversion'  ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('conversion.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Conversion">Conversion</span>
                  </a>
                </li>
                <li class="{{ \Request::segment(1) == 'conversionSetting'  ? 'active' : '' }}">
                  <a class="d-flex align-items-center" href="{{ route('conversionSetting.index') }}">
                    <i data-feather="circle"></i>
                    <span class="menu-item text-truncate" data-i18n="Conversion setting">Setting</span>
                  </a>
                </li>
              </ul>
            </li>
            {{-- @endcan --}}
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
        
          </ul>
        </li>
        
        <li class=" {{ in_array(\Request::segment(1), ['warehouse','transferIn']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather='box'></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">Warehouse
            </span>
          </a>
          <ul class="menu-content">
        
            @can('warehouse-index')
            {{--
            <li class="{{ \Request::is(['warehouse/article']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('warehouse.article') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Stock</span>
              </a>
            </li>
            --}}
            <li class="{{ \Request::is(['warehouse/articlev2']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('warehouse.articlev2') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Stock</span>
              </a>
            </li> 
            @endcan
            
        
            {{-- 
            @can('transferIn-index')
            <li class="{{ \Request::is(['transferInV1','transferInV1/create','transferInV1/show','transferInV1/edit']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('transferInV1.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Transfer in V1</span>
              </a>
            </li>
            @endcan
             --}}
             
@if(auth()->user()->hasAnyRole(['Superuser', 'accounting']))
  @can('transferIn-index')
  <li class="{{ \Request::is(['transferIn','transferIn/create','transferIn/show','transferIn/edit']) ? 'active' : '' }}">
    <a class="d-flex align-items-center" href="{{ route('transferIn.index') }}">
      <i data-feather="circle"></i>
      <span class="menu-item text-truncate" data-i18n="Input">Transfer in</span>
    </a>
  </li>
  @endcan
@endif
          

            {{--
            @can('transferOut-index')
            <li class="{{ \Request::is(['transferOutV1','transferOutV1/create','transferOutV1/show','transferOutV1/edit']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('transferOutV1.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Transfer out V1</span>
              </a>
            </li>
            @endcan
             --}}

            @if(auth()->user()->hasAnyRole(['Superuser', 'accounting']))
              @can('transferOut-index')
              <li class="{{ \Request::is(['transferOut','transferOut/create','transferOut/show','transferOut/edit']) ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('transferOut.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">Transfer out</span>
                </a>
            </li>
             @endcan
             @endif
              <li class="{{ \Request::is(['stockAdjustment','stockAdjustment/create','stockAdjustment/show','stockAdjustment/edit']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('stockAdjustment.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Stock Adjustment</span>
              </a>
            </li>
             <li class="{{ \Request::is(['transferStock','transferStock/create','transferStock/show','transferStock/edit']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('transferStock.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Stock Transfer</span>
              </a>
            </li>
             {{--<li class="{{ \Request::is(['stocReconciliation','stockReconciliation/create','stockReconciliation/show','stockReconciliation/edit']) ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('stockReconciliation.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Stock Reconciliation</span>
              </a>
            </li>--}}
          </ul>
        </li>

        <li class=" {{ in_array(\Request::segment(1), ['delivery','dnReceipt','deliveryReport','suratJalanSementara','dnReturn','dnReplace','temporaryDn']) ? 'active' : '' }} nav-item">
          <a class="d-flex align-items-center" href="javascript:void(0);">
            <i data-feather='truck'></i>
            <span class="menu-title text-truncate" data-i18n="Form Elements">Delivery
            </span>
          </a>
          <ul class="menu-content">
        
            @can('delivery-index')
              <li><a class="d-flex align-items-center" href="#"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Return&Replace">Return & Replace</span></a>
                <ul class="menu-content">
                    <li class="{{ \Request::segment(1) == 'temporaryDn'  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('suratJalanSementara.index') }}"><span class="menu-item text-truncate" data-i18n="Temporary DN">Temporary DN</span></a>
                    </li>
                    <li class="{{ \Request::segment(1) == 'dnReturn'  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('dnReturn.index') }}"><span class="menu-item text-truncate" data-i18n="DN Return">DN Return</span></a>
                    </li>
                    <li class="{{ \Request::segment(1) == 'dnReplace'  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('dnReplace.index') }}"><span class="menu-item text-truncate" data-i18n="DN Replace">DN Replace</span></a>
                    </li>
                    
                </ul>
              </li>
            @endcan

            @can('delivery-index')
            <li class="{{ \Request::segment(1) == 'delivery' ? 'active' : '' }} " >
              <a class="d-flex align-items-center" href="{{ route('delivery.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Delivery Note">Delivery Note</span>
              </a>
            </li>
            @endcan

             @can('delivery-index')
            <li class="{{ \Request::segment(1) == 'dnGeneral' ? 'active' : '' }} " >
              <a class="d-flex align-items-center" href="{{ route('dnGeneral.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Delivery Note">DN General</span>
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

            @can('bom-index')
            {{-- <li class="{{ \Request::segment(1) == 'boms' ? 'active' : '' }}" > --}}
            <li class="{{ in_array(\Request::segment(1),['boms','bomsUpload']) ? 'active' : '' }}">
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
          <li class=" {{ in_array(\Request::segment(1), ['aps','balanceSheet','labaRugi','trialBalance','invoice','kasPenerimaan','kasKeluar','bankPenerimaan','bankKeluar','deliveryReportAcc','deliveryReportSoAcc','jurnalUmum','accountPayable','debitnote']) ? 'active' : '' }} nav-item">
            <a class="d-flex align-items-center" href="javascript:void(0);">
              <i data-feather="dollar-sign"></i>
              <span class="menu-title text-truncate" data-i18n="Form Elements">Finance & acc
              </span>
            </a>
            <ul class="menu-content">
              
              @can('ap-index')
                <li><a class="d-flex align-items-center" href="#"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Invoice">Invoice</span></a>
                  <ul class="menu-content">
                      <li class="{{ \Request::segment(1) == 'accountPayable'  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('accountPayable.index') }}"><span class="menu-item text-truncate" data-i18n="Invoice supplier">Invoice Supplier V2</span></a>
                      </li>
                      {{-- <li class="{{ \Request::segment(1) == 'aps'  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('aps.index') }}"><span class="menu-item text-truncate" data-i18n="Invoice supplier">Invoice Supplier</span></a>
                      </li> --}}
                      <li class="{{ \Request::segment(1) == 'invoice' ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('invoice.index') }}"><span class="menu-item text-truncate" data-i18n="Invoice customer">Invoice Customer</span></a>
                      </li>
                      <li class="{{ \Request::segment(1) == 'debitnote' ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('debitNote.index') }}"><span class="menu-item text-truncate" data-i18n="Debit Note">Debit Note</span></a>
                      </li>
                  </ul>
                </li>
              @endcan

              @can('ap-index')
                <li><a class="d-flex align-items-center" href="#"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Account Setting">Kas</span></a>
                  <ul class="menu-content">
                      <li class="{{ \Request::is(['kasPenerimaan','kasPenerimaan/create','kasPenerimaan/show','kasPenerimaan/edit'])  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('kasPenerimaan.index') }}"><span class="menu-item text-truncate" data-i18n="Penerimaan">Penerimaan</span></a>
                      </li>
                      <li class="{{ \Request::is(['kasKeluar','kasKeluar/create','kasKeluar/show','kasKeluar/edit'])  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('kasKeluar.index') }}"><span class="menu-item text-truncate" data-i18n="Pembayaran">Pembayaran</span></a>
                      </li>
                  </ul>
                </li>
              @endcan
              
              @can('ap-index')
                <li><a class="d-flex align-items-center" href="#"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Account Setting">Bank</span></a>
                  <ul class="menu-content">
                      <li class="{{ \Request::is(['bankPenerimaan','bankPenerimaan/create','bankPenerimaan/show','bankPenerimaan/edit'])  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('bankPenerimaan.index') }}"><span class="menu-item text-truncate" data-i18n="Penerimaan">Penerimaan</span></a>
                      </li>
                      <li class="{{ \Request::is(['bankKeluar','bankKeluar/create','bankKeluar/show','bankKeluar/edit'])  ? 'active' : '' }}"><a class="d-flex align-items-center" href="{{ route('bankKeluar.index') }}"><span class="menu-item text-truncate" data-i18n="Pembayaran">Pembayaran</span></a>
                      </li>
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

              @can('ap-index')
              <li class="{{ \Request::segment(1) == 'balanceSheet'  ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('balanceSheet.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">Neraca</span>
                </a>
              </li>
              @endcan

              @can('ap-index')
              <li class="{{ \Request::segment(1) == 'labaRugi'  ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('labaRugi.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">Laba Rugi</span>
                </a>
              </li>
              @endcan

              @can('ap-index')
              <li class="{{ \Request::segment(1) == 'trialBalance'  ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('trialBalance.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">Trial Balance</span>
                </a>
              </li>
              @endcan

            </ul>
          </li>
          <li class=" {{ in_array(\Request::segment(1), ['accounts','groups','accTypes','account','asset']) ? 'active' : '' }} nav-item">
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
              <li class="{{ \Request::segment(1) == 'asset'  ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('asset.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">Assets</span>
                </a>
              </li>
              @endcan
              
              {{-- @can('accType-index')
              <li class="{{ \Request::segment(1) == 'accTypes'  ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('accTypes.index') }}">
                  <i data-feather="circle"></i>
                  <span class="menu-item text-truncate" data-i18n="Input">Account Type</span>
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
            
            @can('department-index')
            <li class="{{ \Request::segment(1) == 'depts'  ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('depts.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Departement</span>
              </a>
            </li>
            @endcan

          </ul>
        </li>
        <li class=" navigation-header"><span data-i18n="Settings">Settings</span><i data-feather="more-horizontal"></i>
        </li>
        <li class=" {{ in_array(\Request::segment(1), ['setting','users','roles','permissions','company','approval','lockTransaction','masterPpn']) ? 'active' : '' }} nav-item">
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

            @can('lock-transaction-index')
            <li class="{{ \Request::segment(1) == 'lockTransaction' && \Request::segment(2) == '' ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('lockTransaction.index') }}">
                <i data-feather="lock"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Lock Transaction</span>
              </a>
            </li>
            <li class="{{ \Request::segment(1) == 'masterPpn' && \Request::segment(2) == '' ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('masterPpn.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Master PPN</span>
              </a>
            </li>
            @endcan

            @can('permission-index')
            <li class="{{ \Request::segment(1) == 'permissions' && \Request::segment(2) == '' ? 'active' : '' }}">
              <a class="d-flex align-items-center" href="{{ route('permissions.index') }}">
                <i data-feather="circle"></i>
                <span class="menu-item text-truncate" data-i18n="Input">Permission</span>
              </a>
            </li>
            @endcan

            @can('role-index')
            <li class="{{ \Request::is(['roles','roles/create']) ? 'active' : '' }}">
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

  