<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Http\Request;

Route::get('testPrint', function (Request $request) {
	return view("testPrint"); 
});

// Route::auth();
Auth::routes();
Route::group( ['middleware' => ['auth']], function() {
	Route::get('/logout' , 'Auth\LoginController@logout');
	Route::get('/home', 'HomeController@index')->name('home');
    Route::get('/', 'HomeController@index')->name('home');
    Route::get('/welcome', 'HomeController@welcome')->name('welcome');
	
    Route::resource('users', 'UserController');
    Route::resource('roles', 'RoleController');
	
	Route::resource('users','UserController');
	Route::get('user/profile',['as'=>'user.profile','uses'=>'UserController@userProfile']);
	Route::post('change/password',['as'=>'change.password','uses'=>'UserController@changePassword']);
	Route::post('change/profile',['as'=>'change.profile','uses'=>'UserController@changeProfile']);
	Route::get('userLists',['as'=>'user.lists','uses'=>'UserController@userLists']);
	Route::post('userUpdateStatus',['as'=>'user.update.status','uses'=>'UserController@userUpdateStatus']);
	Route::get('cariuser', 'UserController@search');

	Route::post('file/upload', ['as'=>'file.upload.post','uses'=>'FileUploadController@fileUploadPost']);
	
	Route::delete('userdelete',['as'=>'users.delete','uses'=>'UserController@delete']);
	Route::get('roles',['as'=>'roles.index','uses'=>'RoleController@index','middleware' => ['permission:role-list|role-create|role-edit|role-delete']]);
	Route::get('roles/create',['as'=>'roles.create','uses'=>'RoleController@create','middleware' => ['permission:role-create']]);
	Route::post('roles/store',['as'=>'roles.store','uses'=>'RoleController@store','middleware' => ['permission:role-create']]);
	Route::get('roles/{id}',['as'=>'roles.show','uses'=>'RoleController@show']);
	Route::get('roles/{id}/edit',['as'=>'roles.edit','uses'=>'RoleController@edit','middleware' => ['permission:role-edit']]);
	Route::post('roles/update',['as'=>'roles.update','uses'=>'RoleController@update','middleware' => ['permission:role-edit']]);
	// Route::delete('roles/{id}',['as'=>'roles.destroy','uses'=>'RoleController@destroy','middleware' => ['permission:role-delete']]);
	Route::post('roles/delete',['as'=>'roles.destroy','uses'=>'RoleController@destroy']);
	Route::get('rolesList',['as'=>'roles.list','uses'=>'RoleController@listRole']);
	Route::get('permissionListAll',['as'=>'permission.list.all','uses'=>'RoleController@listAllPermission']);

	Route::get('company',['as'=>'company.index','uses'=>'CompanyController@index']);
	Route::post('company/store',['as'=>'company.store','uses'=>'CompanyController@store']);	

	Route::get('setting',['as'=>'setting.index','uses'=>'AttributeController@index']);
	Route::post('setting/store',['as'=>'setting.store','uses'=>'AttributeController@store']);	

	Route::get('show.menu',['as'=>'show.menu','uses'=>'MenuController@showmenu']);
	Route::get('daftar.menu',['as'=>'daftar.menu','uses'=>'MenuController@daftarmenu']);
	Route::get('list.menu',['as'=>'list.menu','uses'=>'MenuController@listmenu']);
	Route::post('delete.menu',['as'=>'delete.menu','uses'=>'MenuController@deletemenu']);

	Route::get('permissions',['as'=>'permissions.index','uses'=>'PermissionController@index']);
	Route::get('permission/list',['as'=>'permission.list','uses'=>'PermissionController@listPermission']);
	Route::post('permission/store',['as'=>'store.permission','uses'=>'PermissionController@store']);
	Route::post('permission/delete',['as'=>'delete.permission','uses'=>'PermissionController@destroy']);
	Route::get('permission/dd',['as'=>'dd.permission','uses'=>'PermissionController@ddpermission']);

	Route::get('customers',['as'=>'customers.index','uses'=>'CustomerController@index','middleware' => ['permission:customer-index']]);
	Route::get('customers/create',['as'=>'customer.create','uses'=>'CustomerController@create','middleware' => ['permission:customer-create']]);
	Route::post('customers/store',['as'=>'customer.store','uses'=>'CustomerController@store']);
	Route::get('customers/list',['as'=>'customer.list','uses'=>'CustomerController@list']);
	Route::get('customers/show',['as'=>'customer.show','uses'=>'CustomerController@show']);
	Route::get('customers/edit',['as'=>'customer.edit','uses'=>'CustomerController@edit','middleware' => ['permission:customer-edit']]);
	Route::post('customers/update',['as'=>'customer.update','uses'=>'CustomerController@update']);
	Route::post('customers/delete',['as'=>'customer.destroy','uses'=>'CustomerController@destroy']);

	Route::get('suppliers',['as'=>'suppliers.index','uses'=>'SupplierController@index','middleware' => ['permission:supplier-index']]);
	Route::get('suppliers/create',['as'=>'supplier.create','uses'=>'SupplierController@create','middleware' => ['permission:supplier-create']]);
	Route::post('suppliers/store',['as'=>'supplier.store','uses'=>'SupplierController@store']);
	Route::get('suppliers/list',['as'=>'supplier.list','uses'=>'SupplierController@list']);
	Route::get('suppliers/show',['as'=>'supplier.show','uses'=>'SupplierController@show']);
	Route::get('suppliers/edit',['as'=>'supplier.edit','uses'=>'SupplierController@edit','middleware' => ['permission:supplier-edit']]);
	Route::post('suppliers/update',['as'=>'supplier.update','uses'=>'SupplierController@update']);
	Route::post('suppliers/delete',['as'=>'supplier.destroy','uses'=>'SupplierController@destroy']);

	Route::get('subContracts',['as'=>'subContracts.index','uses'=>'SubContractController@index','middleware' => ['permission:subContract-index']]);
	Route::get('subContracts/create',['as'=>'subContract.create','uses'=>'SubContractController@create','middleware' => ['permission:subContract-create']]);
	Route::get('subContracts/delivery',['as'=>'subContract.delivery','uses'=>'SubContractController@delivery','middleware' => ['permission:subContract-create']]);
	Route::post('subContracts/store',['as'=>'subContract.store','uses'=>'SubContractController@store']);
	Route::get('subContracts/list',['as'=>'subContract.list','uses'=>'SubContractController@list']);
	Route::get('subContracts/show',['as'=>'subContract.show','uses'=>'SubContractController@show']);
	Route::get('subContracts/edit',['as'=>'subContract.edit','uses'=>'SubContractController@edit','middleware' => ['permission:subContract-edit']]);
	Route::post('subContracts/update',['as'=>'subContract.update','uses'=>'SubContractController@update']);
	Route::post('subContracts/delete',['as'=>'subContract.destroy','uses'=>'SubContractController@destroy']);

	Route::get('accounts',['as'=>'accounts.index','uses'=>'AccountController@index']);
	Route::get('accounts/create',['as'=>'account.create','uses'=>'AccountController@create']);
	Route::post('accounts/store',['as'=>'account.store','uses'=>'AccountController@store']);
	Route::get('accounts/list',['as'=>'account.list','uses'=>'AccountController@list']);
	Route::get('accounts/show',['as'=>'account.show','uses'=>'AccountController@show']);
	Route::get('accounts/edit',['as'=>'account.edit','uses'=>'AccountController@edit']);
	Route::post('accounts/update',['as'=>'account.update','uses'=>'AccountController@update']);
	Route::post('accounts/delete',['as'=>'account.destroy','uses'=>'AccountController@destroy']);

	Route::get('account/setting/barang',['as'=>'accountSetting.barang','uses'=>'AccountSettingController@barang']);
	Route::post('account/setting/store',['as'=>'accountSetting.store','uses'=>'AccountSettingController@store']);
	Route::get('account/setting/mataUang',['as'=>'accountSetting.mataUang','uses'=>'AccountSettingController@mataUang']);

	Route::get('accTypes',['as'=>'accTypes.index','uses'=>'AccTypeController@index','middleware' => ['permission:accType-index']]);
	Route::get('accTypes/create',['as'=>'accType.create','uses'=>'AccTypeController@create','middleware' => ['permission:accType-create']]);
	Route::post('accTypes/store',['as'=>'accType.store','uses'=>'AccTypeController@store']);
	Route::get('accTypes/list',['as'=>'accType.list','uses'=>'AccTypeController@list']);
	Route::get('accTypes/show',['as'=>'accType.show','uses'=>'AccTypeController@show']);
	Route::get('accTypes/edit',['as'=>'accType.edit','uses'=>'AccTypeController@edit','middleware' => ['permission:accType-edit']]);
	Route::post('accTypes/update',['as'=>'accType.update','uses'=>'AccTypeController@update']);
	Route::post('accTypes/delete',['as'=>'accType.destroy','uses'=>'AccTypeController@destroy']);

	Route::get('groups',['as'=>'groups.index','uses'=>'GroupController@index','middleware' => ['permission:group-index']]);
	Route::get('groups/create',['as'=>'group.create','uses'=>'GroupController@create','middleware' => ['permission:group-create']]);
	Route::post('groups/store',['as'=>'group.store','uses'=>'GroupController@store']);
	Route::get('groups/list',['as'=>'group.list','uses'=>'GroupController@list']);
	Route::get('groups/show',['as'=>'group.show','uses'=>'GroupController@show']);
	Route::get('groups/edit',['as'=>'group.edit','uses'=>'GroupController@edit','middleware' => ['permission:group-edit']]);
	Route::post('groups/update',['as'=>'group.update','uses'=>'GroupController@update']);
	Route::post('groups/delete',['as'=>'group.destroy','uses'=>'GroupController@destroy']);

	Route::get('groupMaterials',['as'=>'groupMaterials.index','uses'=>'GroupMaterialController@index','middleware' => ['permission:groupMaterial-index']]);
	Route::get('groupMaterials/create',['as'=>'groupMaterial.create','uses'=>'GroupMaterialController@create','middleware' => ['permission:groupMaterial-create']]);
	Route::post('groupMaterials/store',['as'=>'groupMaterial.store','uses'=>'GroupMaterialController@store']);
	Route::get('groupMaterials/list',['as'=>'groupMaterial.list','uses'=>'GroupMaterialController@list']);
	Route::get('groupMaterials/show',['as'=>'groupMaterial.show','uses'=>'GroupMaterialController@show']);
	Route::get('groupMaterials/edit',['as'=>'groupMaterial.edit','uses'=>'GroupMaterialController@edit','middleware' => ['permission:groupMaterial-edit']]);
	Route::post('groupMaterials/update',['as'=>'groupMaterial.update','uses'=>'GroupMaterialController@update']);
	Route::post('groupMaterials/delete',['as'=>'groupMaterial.destroy','uses'=>'GroupMaterialController@destroy']);

	Route::get('depts',['as'=>'depts.index','uses'=>'DeptController@index','middleware' => ['permission:department-index']]);
	Route::get('depts/create',['as'=>'dept.create','uses'=>'DeptController@create','middleware' => ['permission:department-create']]);
	Route::post('depts/store',['as'=>'dept.store','uses'=>'DeptController@store']);
	Route::get('depts/list',['as'=>'dept.list','uses'=>'DeptController@list']);
	Route::get('depts/show',['as'=>'dept.show','uses'=>'DeptController@show']);
	Route::get('depts/edit',['as'=>'dept.edit','uses'=>'DeptController@edit','middleware' => ['permission:department-edit']]);
	Route::post('depts/update',['as'=>'dept.update','uses'=>'DeptController@update']);
	Route::post('depts/delete',['as'=>'dept.destroy','uses'=>'DeptController@destroy']);

	Route::get('uoms',['as'=>'uoms.index','uses'=>'UomController@index','middleware' => ['permission:uom-index']]);
	Route::get('uoms/create',['as'=>'uom.create','uses'=>'UomController@create','middleware' => ['permission:uom-create']]);
	Route::post('uoms/store',['as'=>'uom.store','uses'=>'UomController@store']);
	Route::get('uoms/list',['as'=>'uom.list','uses'=>'UomController@list']);
	Route::get('uoms/show',['as'=>'uom.show','uses'=>'UomController@show']);
	Route::get('uoms/edit',['as'=>'uom.edit','uses'=>'UomController@edit','middleware' => ['permission:uom-edit']]);
	Route::post('uom/update',['as'=>'uom.update','uses'=>'UomController@update']);
	Route::post('uoms/delete',['as'=>'uom.destroy','uses'=>'UomController@destroy']);

	Route::get('uomCons',['as'=>'uomCons.index','uses'=>'UomConController@index','middleware' => ['permission:uomCon-index']]);
	Route::get('uomCons/create',['as'=>'uomCon.create','uses'=>'UomConController@create','middleware' => ['permission:uomCon-create']]);
	Route::post('uomCons/store',['as'=>'uomCon.store','uses'=>'UomConController@store']);
	Route::get('uomCons/list',['as'=>'uomCon.list','uses'=>'UomConController@list']);
	Route::get('uomCons/show',['as'=>'uomCon.show','uses'=>'UomConController@show']);
	Route::get('uomCons/edit',['as'=>'uomCon.edit','uses'=>'UomConController@edit','middleware' => ['permission:uomCon-edit']]);
	// Route::post('uomCons/update',['as'=>'uomCon.update','uses'=>'UomConController@update']);
	Route::post('uomCons/delete',['as'=>'uomCon.destroy','uses'=>'UomConController@destroy']);
	Route::get('uomCons/get/factor',['as'=>'uomCon.get.factor','uses'=>'UomConController@getFactor']);

	Route::get('jobPositions',['as'=>'jobPositions.index','uses'=>'JobPositionController@index','middleware' => ['permission:jobPosition-index']]);
	Route::get('jobPositions/create',['as'=>'jobPosition.create','uses'=>'JobPositionController@create','middleware' => ['permission:jobPosition-create']]);
	Route::post('jobPositions/store',['as'=>'jobPosition.store','uses'=>'JobPositionController@store']);
	Route::get('jobPositions/list',['as'=>'jobPosition.list','uses'=>'JobPositionController@list']);
	Route::get('jobPositions/show',['as'=>'jobPosition.show','uses'=>'JobPositionController@show']);
	Route::get('jobPositions/edit',['as'=>'jobPosition.edit','uses'=>'JobPositionController@edit','middleware' => ['permission:jobPosition-edit']]);
	Route::post('jobPositions/update',['as'=>'jobPosition.update','uses'=>'JobPositionController@update']);
	Route::post('jobPositions/delete',['as'=>'jobPosition.destroy','uses'=>'JobPositionController@destroy']);

	Route::get('employees',['as'=>'employees.index','uses'=>'EmployeeController@index','middleware' => ['permission:employee-index']]);
	Route::get('employees/create',['as'=>'employee.create','uses'=>'EmployeeController@create','middleware' => ['permission:employee-create']]);
	Route::post('employees/store',['as'=>'employee.store','uses'=>'EmployeeController@store']);
	Route::get('employees/list',['as'=>'employee.list','uses'=>'EmployeeController@list']);
	Route::get('employees/show',['as'=>'employee.show','uses'=>'EmployeeController@show']);
	Route::get('employees/edit',['as'=>'employee.edit','uses'=>'EmployeeController@edit','middleware' => ['permission:employee-edit']]);
	Route::post('employees/update',['as'=>'employee.update','uses'=>'EmployeeController@update']);
	Route::post('employees/delete',['as'=>'employee.destroy','uses'=>'EmployeeController@destroy']);

	Route::get('articles',['as'=>'articles.index','uses'=>'ArticleController@index','middleware' => ['permission:article-index']]);
	Route::get('articles/create',['as'=>'article.create','uses'=>'ArticleController@create','middleware' => ['permission:article-create']]);
	Route::post('articles/store',['as'=>'article.store','uses'=>'ArticleController@store']);
	Route::post('articles/image/store',['as'=>'article.image.store','uses'=>'ArticleController@storeImage']);
	Route::get('articles/list',['as'=>'article.list','uses'=>'ArticleController@list']);
	Route::get('articles/show',['as'=>'article.show','uses'=>'ArticleController@show']);
	Route::get('articles/edit',['as'=>'article.edit','uses'=>'ArticleController@edit','middleware' => ['permission:article-edit']]);
	Route::post('articles/update',['as'=>'article.update','uses'=>'ArticleController@update']);
	Route::post('articles/delete',['as'=>'article.destroy','uses'=>'ArticleController@destroy']);
	Route::get('articles/code/create',['as'=>'article.code.create','uses'=>'ArticleController@articleCodeCreate']);
	Route::post('articles/get/supplier',['as'=>'get.supplier','uses'=>'ArticleController@getSupplier']);
	Route::get('articles/movement',['as'=>'article.movement','uses'=>'ArticleController@movement']);

	Route::get('articles/request',['as'=>'article.request','uses'=>'ArticleController@requestIndex']);
	Route::get('articles/request/list',['as'=>'article.request.list','uses'=>'ArticleController@requestList']);
	Route::get('articles/request/create',['as'=>'article.request.create','uses'=>'ArticleController@requestCreate']);
	Route::post('articles/request/store',['as'=>'article.request.store','uses'=>'ArticleController@requestStore']);
	Route::post('articles/request/delete',['as'=>'article.request.destroy','uses'=>'ArticleController@requestDestroy']);
	Route::get('articles/request/edit',['as'=>'article.request.edit','uses'=>'ArticleController@requestEdit']);
	Route::post('articles/request/update',['as'=>'article.request.update','uses'=>'ArticleController@requestUpdate']);
	Route::get('articles/request/show',['as'=>'article.request.show','uses'=>'ArticleController@requestshow']);
	Route::post('articles/request/submit',['as'=>'article.request.submit','uses'=>'ArticleController@requestSubmit']);
	Route::post('articles/request/approve',['as'=>'article.request.approve','uses'=>'ArticleController@requestApprove']);
	
	Route::get('articleTypes',['as'=>'articleTypes.index','uses'=>'ArticleTypeController@index','middleware' => ['permission:articleType-index']]);
	Route::get('articleTypes/create',['as'=>'articleType.create','uses'=>'ArticleTypeController@create','middleware' => ['permission:articleType-create']]);
	Route::post('articleTypes/store',['as'=>'articleType.store','uses'=>'ArticleTypeController@store']);
	Route::get('articleTypes/list',['as'=>'articleType.list','uses'=>'ArticleTypeController@list']);
	Route::get('articleTypes/show',['as'=>'articleType.show','uses'=>'ArticleTypeController@show']);
	Route::get('articleTypes/edit',['as'=>'articleType.edit','uses'=>'ArticleTypeController@edit','middleware' => ['permission:articleType-edit']]);
	Route::post('articleTypes/update',['as'=>'articleType.update','uses'=>'ArticleTypeController@update']);
	Route::post('articleTypes/delete',['as'=>'articleType.destroy','uses'=>'ArticleTypeController@destroy']);

	Route::get('banks',['as'=>'banks.index','uses'=>'BankController@index','middleware' => ['permission:bank-index']]);
	Route::get('banks/create',['as'=>'bank.create','uses'=>'BankController@create','middleware' => ['permission:bank-create']]);
	Route::post('banks/store',['as'=>'bank.store','uses'=>'BankController@store']);
	Route::get('banks/list',['as'=>'bank.list','uses'=>'BankController@list']);
	Route::get('banks/show',['as'=>'bank.show','uses'=>'BankController@show']);
	Route::get('banks/edit',['as'=>'bank.edit','uses'=>'BankController@edit','middleware' => ['permission:bank-edit']]);
	Route::post('banks/update',['as'=>'bank.update','uses'=>'BankController@update']);
	Route::post('banks/delete',['as'=>'bank.destroy','uses'=>'BankController@destroy']);

	Route::get('salesOrders',['as'=>'salesOrders.index','uses'=>'SalesOrderController@index','middleware' => ['permission:salesOrder-index']]);
	Route::get('salesOrders/create',['as'=>'salesOrder.create','uses'=>'SalesOrderController@create','middleware' => ['permission:salesOrder-create']]);
	Route::post('salesOrders/store',['as'=>'salesOrder.store','uses'=>'SalesOrderController@store']);
	Route::get('salesOrders/list',['as'=>'salesOrder.list','uses'=>'SalesOrderController@list']);
	Route::get('salesOrders/list/detail',['as'=>'salesOrder.list.detail','uses'=>'SalesOrderController@listDetail']);
	Route::get('salesOrders/show',['as'=>'salesOrder.show','uses'=>'SalesOrderController@show']);
	Route::get('salesOrders/edit',['as'=>'salesOrder.edit','uses'=>'SalesOrderController@edit','middleware' => ['permission:salesOrder-edit']]);
	Route::get('salesOrders/close',['as'=>'salesOrder.close','uses'=>'SalesOrderController@close','middleware' => ['permission:salesOrder-edit']]);
	Route::post('salesOrders/update/close',['as'=>'salesOrder.update.close','uses'=>'SalesOrderController@updateClose']);
	Route::post('salesOrders/update',['as'=>'salesOrder.update','uses'=>'SalesOrderController@update']);
	Route::post('salesOrders/delete',['as'=>'salesOrder.destroy','uses'=>'SalesOrderController@destroy']);
	Route::get('salesOrders/code/create',['as'=>'salesOrder.code.create','uses'=>'SalesOrderController@articleCodeCreate']);
	Route::get('salesOrders/print',['as'=>'salesOrder.print','uses'=>'SalesOrderController@print']);
	Route::get('salesOrder/approve',['as'=>'salesOrder.approve','uses'=>'SalesOrderController@approve']);
	// Route::post('salesOrder/revision',['as'=>'salesOrder.revision','uses'=>'SalesOrderController@revision']);

	Route::get('salesOrderReport',['as'=>'salesOrder.report','uses'=>'SalesOrderController@report','middleware' => ['permission:salesOrder-index']]);
	Route::get('salesOrderReport/list',['as'=>'salesOrder.list.report','uses'=>'SalesOrderController@listReport','middleware' => ['permission:salesOrder-index']]);

	Route::get('purchaseOrders',['as'=>'purchaseOrders.index','uses'=>'PurchaseOrderController@index','middleware' => ['permission:purchaseOrder-index']]);
	Route::get('purchaseOrders/create',['as'=>'purchaseOrder.create','uses'=>'PurchaseOrderController@create','middleware' => ['permission:purchaseOrder-create']]);
	Route::post('purchaseOrders/store',['as'=>'purchaseOrder.store','uses'=>'PurchaseOrderController@store']);
	Route::get('purchaseOrders/list',['as'=>'purchaseOrder.list','uses'=>'PurchaseOrderController@list']);
	Route::get('purchaseOrders/list/detail',['as'=>'purchaseOrder.list.detail','uses'=>'PurchaseOrderController@listDetail']);
	Route::post('purchaseOrders/listDetail',['as'=>'purchaseOrder.listDetail','uses'=>'PurchaseOrderController@listDetail']);
	Route::get('purchaseOrders/show',['as'=>'purchaseOrder.show','uses'=>'PurchaseOrderController@show']);
	Route::get('purchaseOrders/edit',['as'=>'purchaseOrder.edit','uses'=>'PurchaseOrderController@edit','middleware' => ['permission:purchaseOrder-edit']]);
	Route::post('purchaseOrders/update',['as'=>'purchaseOrder.update','uses'=>'PurchaseOrderController@update']);
	Route::post('purchaseOrders/delete',['as'=>'purchaseOrder.destroy','uses'=>'PurchaseOrderController@destroy']);
	Route::post('purchaseOrders/clear',['as'=>'purchaseOrder.clear','uses'=>'PurchaseOrderController@clear']);
	Route::get('purchaseOrders/decline',['as'=>'purchaseOrder.decline','uses'=>'PurchaseOrderController@decline']);
	Route::get('purchaseOrders/code/create',['as'=>'purchaseOrder.code.create','uses'=>'PurchaseOrderController@articleCodeCreate']);
	Route::get('purchaseOrders/print',['as'=>'purchaseOrder.print','uses'=>'PurchaseOrderController@print']);
	Route::get('purchaseOrders/price/list',['as'=>'purchaseOrder.price.list','uses'=>'PurchaseOrderController@priceList']);
	Route::post('purchaseOrders/revision',['as'=>'purchaseOrder.revision','uses'=>'PurchaseOrderController@revision','middleware' => ['permission:purchaseOrder-revision']]);
	// Route::get('purchaseOrders/revision',['as'=>'purchaseOrder.revision','uses'=>'PurchaseOrderController@revision','middleware' => ['permission:purchaseOrder-revision']]);
	Route::get('purchaseOrders/approve',['as'=>'purchaseOrder.approve','uses'=>'PurchaseOrderController@approve']);
	Route::get('purchaseOrders/listArticle/pr',['as'=>'purchaseOrder.listArticle.pr','uses'=>'PurchaseOrderController@listArticleByPr']);

	Route::get('purchaseOrdersReport',['as'=>'purchaseOrders.report','uses'=>'PurchaseOrderController@report','middleware' => ['permission:purchaseOrder-index']]);
	Route::get('purchaseOrdersReport/list',['as'=>'purchaseOrders.listReport','uses'=>'PurchaseOrderController@listReport','middleware' => ['permission:purchaseOrder-index']]);

	Route::get('targetSo',['as'=>'targetSo.index','uses'=>'TargetSoController@index','middleware' => ['permission:targetSo-index']]);
	Route::get('targetSo/create',['as'=>'targetSo.create','uses'=>'TargetSoController@create','middleware' => ['permission:targetSo-create']]);
	Route::post('targetSo/store',['as'=>'targetSo.store','uses'=>'TargetSoController@store']);
	Route::get('targetSo/list',['as'=>'targetSo.list','uses'=>'TargetSoController@list']);
	Route::get('targetSo/list/detail',['as'=>'targetSo.list.detail','uses'=>'TargetSoController@listDetail']);
	Route::get('targetSo/show',['as'=>'targetSo.show','uses'=>'TargetSoController@show']);
	Route::get('targetSo/edit',['as'=>'targetSo.edit','uses'=>'TargetSoController@edit','middleware' => ['permission:targetSo-edit']]);
	Route::post('targetSo/update',['as'=>'targetSo.update','uses'=>'TargetSoController@update']);
	Route::post('targetSo/delete',['as'=>'targetSo.destroy','uses'=>'TargetSoController@destroy']);
	Route::get('targetSo/code/create',['as'=>'targetSo.code.create','uses'=>'TargetSoController@articleCodeCreate']);
	Route::get('targetSo/print',['as'=>'targetSo.print','uses'=>'TargetSoController@print']);
	Route::get('targetSo/approve',['as'=>'targetSo.approve','uses'=>'TargetSoController@approve']);
	Route::get('targetSo/itemList',['as'=>'targetSo.itemList','uses'=>'TargetSoController@listItemByCustomer']);
	Route::post('targetSo/revision',['as'=>'targetSo.revision','uses'=>'TargetSoController@revision','middleware' => ['permission:targetSo-revision']]);
	
	Route::get('receivings',['as'=>'receivings.index','uses'=>'ReceivingController@index','middleware' => ['permission:receiving-index']]);
	Route::get('receivings/create',['as'=>'receiving.create','uses'=>'ReceivingController@create','middleware' => ['permission:receiving-create']]);
	Route::get('receivings/search',['as'=>'receiving.search','uses'=>'ReceivingController@search']);
	Route::get('receivings/list/po',['as'=>'receiving.list.po','uses'=>'ReceivingController@listPo']);
	Route::get('receivings/list/uom',['as'=>'receiving.list.uom','uses'=>'ReceivingController@listUom']);
	Route::get('receivings/po/det',['as'=>'receiving.po.det','uses'=>'ReceivingController@poDetail']);
	Route::post('receivings/store',['as'=>'receiving.store','uses'=>'ReceivingController@store']);
	Route::get('receivings/list',['as'=>'receiving.list','uses'=>'ReceivingController@list']);
	Route::get('receivings/list/detail',['as'=>'receiving.list.detail','uses'=>'ReceivingController@listDetail']);
	Route::get('receivings/show',['as'=>'receiving.show','uses'=>'ReceivingController@show']);
	Route::get('receivings/edit',['as'=>'receiving.edit','uses'=>'ReceivingController@edit','middleware' => ['permission:receiving-edit']]);
	Route::post('receivings/update',['as'=>'receiving.update','uses'=>'ReceivingController@update']);
	Route::post('receivings/delete',['as'=>'receiving.destroy','uses'=>'ReceivingController@destroy']);
	Route::get('receivings/code/create',['as'=>'receiving.code.create','uses'=>'ReceivingController@articleCodeCreate']);
	Route::get('receivings/print',['as'=>'receiving.print','uses'=>'ReceivingController@print']);
	Route::post('receivings/posting',['as'=>'receiving.posting','uses'=>'ReceivingController@posting']);
	Route::post('receiving/approve',['as'=>'receiving.approve','uses'=>'ReceivingController@approve']);
	Route::post('receiving/cancel',['as'=>'receiving.cancel','uses'=>'ReceivingController@cancel']);
	Route::post('receiving/revision',['as'=>'receiving.revision','uses'=>'ReceivingController@revision']);

	Route::get('delivery',['as'=>'delivery.index','uses'=>'DeliveryController@index','middleware' => ['permission:delivery-index']]);
	Route::get('delivery/create',['as'=>'delivery.create','uses'=>'DeliveryController@create','middleware' => ['permission:delivery-create']]);
	Route::get('delivery/search',['as'=>'delivery.search','uses'=>'DeliveryController@search']);
	Route::get('delivery/list/so',['as'=>'delivery.list.so','uses'=>'DeliveryController@listSo']);
	Route::get('delivery/list/uom',['as'=>'delivery.list.uom','uses'=>'DeliveryController@listUom']);
	Route::get('delivery/so/det',['as'=>'delivery.so.det','uses'=>'DeliveryController@soDetail']);
	Route::post('delivery/store',['as'=>'delivery.store','uses'=>'DeliveryController@store']);
	Route::get('delivery/list',['as'=>'delivery.list','uses'=>'DeliveryController@list']);
	Route::get('delivery/show',['as'=>'delivery.show','uses'=>'DeliveryController@show']);
	Route::get('delivery/edit',['as'=>'delivery.edit','uses'=>'DeliveryController@edit','middleware' => ['permission:delivery-edit']]);
	Route::post('delivery/update',['as'=>'delivery.update','uses'=>'DeliveryController@update']);
	Route::post('delivery/delete',['as'=>'delivery.destroy','uses'=>'DeliveryController@destroy']);
	Route::get('delivery/code/create',['as'=>'delivery.code.create','uses'=>'DeliveryController@articleCodeCreate']);
	Route::get('delivery/print',['as'=>'delivery.print','uses'=>'DeliveryController@print']);
	Route::post('delivery/posting',['as'=>'delivery.posting','uses'=>'DeliveryController@posting']);
	Route::post('delivery/approve',['as'=>'delivery.approve','uses'=>'DeliveryController@approve']);
	Route::post('delivery/revision',['as'=>'delivery.revision','uses'=>'DeliveryController@revision']);
	Route::get('delivery/notif/approve',['as'=>'delivery.notif.approve','uses'=>'DeliveryController@approve']);

	Route::get('deliveryReport',['as'=>'delivery.report','uses'=>'DeliveryController@report','middleware' => ['permission:delivery-report']]);
	Route::post('eliveryReport/list/report',['as'=>'delivery.list.report','uses'=>'DeliveryController@listReport']);
	Route::get('deliveryReportAcc',['as'=>'delivery.report.acc','uses'=>'DeliveryController@reportAcc','middleware' => ['permission:delivery-report-acc']]);
	Route::post('deliveryReportAcc/list/report',['as'=>'delivery.list.report.acc','uses'=>'DeliveryController@listReportAcc']);
	
	Route::get('deliveryReportSoAcc',['as'=>'delivery.report.so.acc','uses'=>'DeliveryController@reportSoAcc','middleware' => ['permission:delivery-report-acc']]);
	Route::get('deliveryReportSoAcc/print',['as'=>'delivery.print.so','uses'=>'DeliveryController@printReportSo']);
	Route::get('deliveryReportSoAcc/export',['as'=>'delivery.export.so','uses'=>'DeliveryController@exportSo']);
	
	Route::get('dnReceipt',['as'=>'dnReceipt.index','uses'=>'DeliveryReceiptController@index','middleware' => ['permission:dnReceipt-index']]);
	// Route::get('dnReceipt/create',['as'=>'dnReceipt.create','uses'=>'DeliveryReceiptController@create','middleware' => ['permission:dnReceipt-create']]);
	Route::post('dnReceipt/store',['as'=>'dnReceipt.store','uses'=>'DeliveryReceiptController@store']);
	Route::post('dnReceipt/list',['as'=>'dnReceipt.list','uses'=>'DeliveryReceiptController@list']);
	Route::post('dnReceipt/delete',['as'=>'dnReceipt.destroy','uses'=>'DeliveryReceiptController@destroy']);
	Route::get('dnReceipt/create',['as'=>'dnReceipt.create','uses'=>'DeliveryReceiptController@create','middleware' => ['permission:dnReceipt-create']]);
	Route::get('dnReceipt/edit',['as'=>'dnReceipt.edit','uses'=>'DeliveryReceiptController@edit','middleware' => ['permission:dnReceipt-edit']]);
	Route::post('dnReceipt/update',['as'=>'dnReceipt.update','uses'=>'DeliveryReceiptController@update']);

	Route::get('invoice',['as'=>'invoice.index','uses'=>'InvoiceController@index','middleware' => ['permission:receiving-index']]);
	Route::get('invoice/create',['as'=>'invoice.create','uses'=>'InvoiceController@create','middleware' => ['permission:receiving-create']]);
	Route::get('invoice/search',['as'=>'invoice.search','uses'=>'InvoiceController@search']);
	Route::get('invoice/list/so',['as'=>'invoice.list.so','uses'=>'InvoiceController@listSo']);
	Route::get('invoice/list/dn',['as'=>'invoice.list.dn','uses'=>'InvoiceController@listDn']);
	Route::get('invoice/list/uom',['as'=>'invoice.list.uom','uses'=>'InvoiceController@listUom']);
	Route::get('invoice/dn/det',['as'=>'invoice.dn.det','uses'=>'InvoiceController@dnDetail']);
	Route::post('invoice/store',['as'=>'invoice.store','uses'=>'InvoiceController@store']);
	Route::get('invoice/list',['as'=>'invoice.list','uses'=>'InvoiceController@list']);
	Route::get('invoice/show',['as'=>'invoice.show','uses'=>'InvoiceController@show']);
	Route::get('invoice/edit',['as'=>'invoice.edit','uses'=>'InvoiceController@edit','middleware' => ['permission:receiving-edit']]);
	Route::post('invoice/update',['as'=>'invoice.update','uses'=>'InvoiceController@update']);
	Route::post('invoice/delete',['as'=>'invoice.destroy','uses'=>'InvoiceController@destroy']);
	Route::get('invoice/code/create',['as'=>'invoice.code.create','uses'=>'InvoiceController@articleCodeCreate']);
	Route::get('invoice/print',['as'=>'invoice.print','uses'=>'InvoiceController@print']);
	Route::post('invoice/posting',['as'=>'invoice.posting','uses'=>'InvoiceController@posting']);
	Route::post('invoice/approve',['as'=>'invoice.approve','uses'=>'InvoiceController@approve']);
	Route::get('invoice/notif/approve',['as'=>'invoice.notif.approve','uses'=>'InvoiceController@approve']);

	Route::get('aps',['as'=>'aps.index','uses'=>'AccountPayableController@index','middleware' => ['permission:ap-index']]);
	Route::get('aps/create',['as'=>'ap.create','uses'=>'AccountPayableController@create','middleware' => ['permission:ap-create']]);
	Route::get('aps/list/sj',['as'=>'ap.list.sj','uses'=>'AccountPayableController@listSj']);
	Route::get('aps/list/po',['as'=>'ap.list.po','uses'=>'AccountPayableController@listPo']);
	Route::get('aps/list/rec',['as'=>'ap.list.rec','uses'=>'AccountPayableController@listRec']);
	Route::get('aps/detail/rec',['as'=>'ap.detail.rec','uses'=>'AccountPayableController@detailRec']);
	Route::post('aps/store',['as'=>'ap.store','uses'=>'AccountPayableController@store']);
	Route::get('aps/show',['as'=>'ap.show','uses'=>'AccountPayableController@show']);
	Route::get('aps/edit',['as'=>'ap.edit','uses'=>'AccountPayableController@edit','middleware' => ['permission:ap-edit']]);
	Route::get('aps/list',['as'=>'ap.list','uses'=>'AccountPayableController@list']);
	Route::post('aps/delete',['as'=>'ap.destroy','uses'=>'AccountPayableController@destroy']);
	Route::post('aps/update',['as'=>'ap.update','uses'=>'AccountPayableController@update']);
	Route::post('aps/posting',['as'=>'ap.posting','uses'=>'AccountPayableController@posting']);
	Route::get('aps/revision',['as'=>'ap.revision','uses'=>'AccountPayableController@revision']);
	Route::get('aps/show',['as'=>'ap.show','uses'=>'AccountPayableController@show']);
	Route::get('aps/print',['as'=>'ap.print','uses'=>'AccountPayableController@print']);
	Route::post('aps/approve',['as'=>'aps.approve','uses'=>'AccountPayableController@approve']);
	Route::get('aps/print/slip/pembayaran',['as'=>'ap.print.slip.pembayaran','uses'=>'AccountPayableController@printSlipPembayaran']);
	Route::get('aps/notif/approve',['as'=>'ap.notif.approve','uses'=>'AccountPayableController@approve']);

	// Route::get('receivings/search',['as'=>'ap.search','uses'=>'ReceivingController@search']);
	// Route::get('receivings/list/po',['as'=>'ap.list.po','uses'=>'ReceivingController@listPo']);
	// Route::get('receivings/list/uom',['as'=>'ap.list.uom','uses'=>'ReceivingController@listUom']);
	// Route::get('receivings/po/det',['as'=>'ap.po.det','uses'=>'ReceivingController@poDetail']);
	
	// Route::get('receivings/list',['as'=>'ap.list','uses'=>'ReceivingController@list']);
	// Route::get('receivings/show',['as'=>'ap.show','uses'=>'ReceivingController@show']);
	// Route::get('receivings/edit',['as'=>'ap.edit','uses'=>'ReceivingController@edit','middleware' => ['permission:ap-edit']]);
	// Route::post('receivings/update',['as'=>'ap.update','uses'=>'ReceivingController@update']);
	// Route::post('receivings/delete',['as'=>'ap.destroy','uses'=>'ReceivingController@destroy']);
	// Route::get('receivings/code/create',['as'=>'ap.code.create','uses'=>'ReceivingController@articleCodeCreate']);
	// Route::get('receivings/print',['as'=>'ap.print','uses'=>'ReceivingController@print']);
	// Route::post('receivings/posting',['as'=>'ap.posting','uses'=>'ReceivingController@posting']);

	Route::get('proforma',['as'=>'apProforma.index','uses'=>'AccountPayableProformaController@index','middleware' => ['permission:ap-proforma-index']]);
	Route::get('proforma/create',['as'=>'apProforma.create','uses'=>'AccountPayableProformaController@create','middleware' => ['permission:ap-proforma-create']]);
	Route::get('proforma/list/po',['as'=>'apProforma.list.po','uses'=>'AccountPayableProformaController@listPo']);
	Route::get('proforma/detail/rec',['as'=>'apProforma.po.detail','uses'=>'AccountPayableProformaController@poDetail']);
	Route::post('proforma/store',['as'=>'apProforma.store','uses'=>'AccountPayableProformaController@store']);
	Route::get('proforma/show',['as'=>'apProforma.show','uses'=>'AccountPayableProformaController@show']);
	Route::get('proforma/edit',['as'=>'apProforma.edit','uses'=>'AccountPayableProformaController@edit','middleware' => ['permission:ap-proforma-edit']]);
	Route::get('proforma/list',['as'=>'apProforma.list','uses'=>'AccountPayableProformaController@list']);
	Route::post('proforma/delete',['as'=>'apProforma.destroy','uses'=>'AccountPayableProformaController@destroy']);
	Route::post('proforma/update',['as'=>'apProforma.update','uses'=>'AccountPayableProformaController@update']);
	Route::post('proforma/posting',['as'=>'apProforma.posting','uses'=>'AccountPayableProformaController@posting']);
	Route::get('proforma/revision',['as'=>'apProforma.revision','uses'=>'AccountPayableProformaController@revision']);
	Route::get('proforma/show',['as'=>'apProforma.show','uses'=>'AccountPayableProformaController@show']);

	Route::get('disbursement',['as'=>'disbursement.index','uses'=>'BankDisbursementController@index','middleware' => ['permission:disbursement-index']]);
	Route::get('disbursement/create',['as'=>'disbursement.create','uses'=>'BankDisbursementController@create','middleware' => ['permission:disbursement-create']]);
	Route::get('disbursement/list/invoice',['as'=>'disbursement.list.invoice','uses'=>'BankDisbursementController@listInvoice']);
	Route::get('disbursement/list/selected',['as'=>'disbursement.list.selected','uses'=>'BankDisbursementController@listSelected']);
	Route::get('disbursement/detail/rec',['as'=>'disbursement.po.detail','uses'=>'BankDisbursementController@poDetail']);
	Route::post('disbursement/store',['as'=>'disbursement.store','uses'=>'BankDisbursementController@store']);
	Route::get('disbursement/show',['as'=>'disbursement.show','uses'=>'BankDisbursementController@show']);
	Route::get('disbursement/edit',['as'=>'disbursement.edit','uses'=>'BankDisbursementController@edit','middleware' => ['permission:disbursement-edit']]);
	Route::get('disbursement/list',['as'=>'disbursement.list','uses'=>'BankDisbursementController@list']);
	Route::post('disbursement/delete',['as'=>'disbursement.destroy','uses'=>'BankDisbursementController@destroy']);
	Route::post('disbursement/update',['as'=>'disbursement.update','uses'=>'BankDisbursementController@update']);
	Route::post('disbursement/approve',['as'=>'disbursement.approve','uses'=>'BankDisbursementController@approve']);
	Route::get('disbursement/revision',['as'=>'disbursement.revision','uses'=>'BankDisbursementController@revision']);
	Route::get('disbursement/show',['as'=>'disbursement.show','uses'=>'BankDisbursementController@show']);

	Route::get('bankReceipt',['as'=>'bankReceipt.index','uses'=>'BankReceiptController@index','middleware' => ['permission:disbursement-index']]);
	Route::get('bankReceipt/create',['as'=>'bankReceipt.create','uses'=>'BankReceiptController@create','middleware' => ['permission:disbursement-create']]);
	Route::get('bankReceipt/list/invoice',['as'=>'bankReceipt.list.invoice','uses'=>'BankReceiptController@listInvoice']);
	Route::get('bankReceipt/list/selected',['as'=>'bankReceipt.list.selected','uses'=>'BankReceiptController@listSelected']);
	Route::get('bankReceipt/detail/rec',['as'=>'bankReceipt.po.detail','uses'=>'BankReceiptController@poDetail']);
	Route::post('bankReceipt/store',['as'=>'bankReceipt.store','uses'=>'BankReceiptController@store']);
	Route::get('bankReceipt/show',['as'=>'bankReceipt.show','uses'=>'BankReceiptController@show']);
	Route::get('bankReceipt/edit',['as'=>'bankReceipt.edit','uses'=>'BankReceiptController@edit','middleware' => ['permission:disbursement-edit']]);
	Route::get('bankReceipt/list',['as'=>'bankReceipt.list','uses'=>'BankReceiptController@list']);
	Route::post('bankReceipt/delete',['as'=>'bankReceipt.destroy','uses'=>'BankReceiptController@destroy']);
	Route::post('bankReceipt/update',['as'=>'bankReceipt.update','uses'=>'BankReceiptController@update']);
	Route::post('bankReceipt/approve',['as'=>'bankReceipt.approve','uses'=>'BankReceiptController@approve']);
	Route::get('bankReceipt/revision',['as'=>'bankReceipt.revision','uses'=>'BankReceiptController@revision']);
	Route::get('bankReceipt/show',['as'=>'bankReceipt.show','uses'=>'BankReceiptController@show']);

	Route::get('receivingsRm',['as'=>'receivingsRm.index','uses'=>'ReceivingRmController@index','middleware' => ['permission:receivingRm-index']]);
	Route::get('receivingsRm/create',['as'=>'receivingRm.create','uses'=>'ReceivingRmController@create','middleware' => ['permission:receivingRm-create']]);
	Route::get('receivingsRm/search',['as'=>'receivingRm.search','uses'=>'ReceivingRmController@search']);
	Route::get('receivingsRm/list/so',['as'=>'receivingRm.list.so','uses'=>'ReceivingRmController@listSo']);
	Route::get('receivingsRm/list/uom',['as'=>'receivingRm.list.uom','uses'=>'ReceivingRmController@listUom']);
	Route::get('receivingsRm/so/det',['as'=>'receivingRm.so.det','uses'=>'ReceivingRmController@soDetail']);
	Route::post('receivingsRm/store',['as'=>'receivingRm.store','uses'=>'ReceivingRmController@store']);
	Route::get('receivingsRm/list',['as'=>'receivingRm.list','uses'=>'ReceivingRmController@list']);
	Route::get('receivingsRm/show',['as'=>'receivingRm.show','uses'=>'ReceivingRmController@show']);
	Route::get('receivingsRm/edit',['as'=>'receivingRm.edit','uses'=>'ReceivingRmController@edit','middleware' => ['permission:receivingRm-edit']]);
	Route::post('receivingsRm/update',['as'=>'receivingRm.update','uses'=>'ReceivingRmController@update']);
	Route::post('receivingsRm/delete',['as'=>'receivingRm.destroy','uses'=>'ReceivingRmController@destroy']);
	Route::get('receivingsRm/code/create',['as'=>'receivingRm.code.create','uses'=>'ReceivingRmController@articleCodeCreate']);
	Route::get('receivingsRm/print',['as'=>'receivingRm.print','uses'=>'ReceivingRmController@print']);
	Route::post('receivingsRm/posting',['as'=>'receivingRm.posting','uses'=>'ReceivingRmController@posting']);

	Route::get('purchaseRequests',['as'=>'purchaseRequests.index','uses'=>'PurchaseRequestController@index','middleware' => ['permission:purchaseRequest-index']]);
	Route::get('purchaseRequests/create',['as'=>'purchaseRequest.create','uses'=>'PurchaseRequestController@create','middleware' => ['permission:purchaseRequest-create']]);
	Route::post('purchaseRequests/store',['as'=>'purchaseRequest.store','uses'=>'PurchaseRequestController@store']);
	Route::get('purchaseRequests/list',['as'=>'purchaseRequest.list','uses'=>'PurchaseRequestController@list']);
	Route::get('purchaseRequests/list/detail',['as'=>'purchaseRequest.list.detail','uses'=>'PurchaseRequestController@listDetail']);
	Route::get('purchaseRequests/show',['as'=>'purchaseRequest.show','uses'=>'PurchaseRequestController@show']);
	Route::get('purchaseRequests/edit',['as'=>'purchaseRequest.edit','uses'=>'PurchaseRequestController@edit','middleware' => ['permission:purchaseRequest-edit']]);
	Route::post('purchaseRequests/update',['as'=>'purchaseRequest.update','uses'=>'PurchaseRequestController@update']);
	Route::post('purchaseRequests/delete',['as'=>'purchaseRequest.destroy','uses'=>'PurchaseRequestController@destroy']);
	Route::get('purchaseRequests/code/create',['as'=>'purchaseRequest.code.create','uses'=>'PurchaseRequestController@articleCodeCreate']);
	Route::get('purchaseRequests/print',['as'=>'purchaseRequest.print','uses'=>'PurchaseRequestController@print']);
	Route::get('purchaseRequests/approve',['as'=>'purchaseRequest.approve','uses'=>'PurchaseRequestController@approve']);
	Route::get('purchaseRequests/article/tso',['as'=>'purchaseRequest.article.tso','uses'=>'PurchaseRequestController@articleTso']);
	Route::post('purchaseRequests/revision',['as'=>'purchaseRequest.revision','uses'=>'PurchaseRequestController@revision']);
	// Route::get('purchaseRequests/revision/tso',['as'=>'purchaseRequest.revision.tso','uses'=>'PurchaseRequestController@revisionFromTso']);
	Route::get('purchaseRequests/warning',['as'=>'purchaseRequest.warning','uses'=>'PurchaseRequestController@warning']);

	Route::get('boms',['as'=>'boms.index','uses'=>'BomController@index','middleware' => ['permission:bom-index']]);
	Route::get('boms/create',['as'=>'bom.create','uses'=>'BomController@create','middleware' => ['permission:bom-create']]);
	Route::post('boms/store',['as'=>'bom.store','uses'=>'BomController@store']);
	Route::get('boms/list',['as'=>'bom.list','uses'=>'BomController@list']);
	Route::get('boms/show',['as'=>'bom.show','uses'=>'BomController@show']);
	Route::get('boms/edit',['as'=>'bom.edit','uses'=>'BomController@edit','middleware' => ['permission:bom-edit']]);
	Route::post('boms/update',['as'=>'bom.update','uses'=>'BomController@update']);
	Route::get('boms/approve',['as'=>'bom.approve','uses'=>'BomController@approve']);
	Route::post('boms/delete',['as'=>'bom.destroy','uses'=>'BomController@destroy']);
	Route::get('boms/code/create',['as'=>'bom.code.create','uses'=>'BomController@articleCodeCreate']);
	Route::get('boms/print',['as'=>'bom.print','uses'=>'BomController@print']);
	Route::post('boms/revision',['as'=>'bom.revision','uses'=>'BomController@revision','middleware' => ['permission:bom-revision']]);

	Route::get('bom/report',['as'=>'bom.report.index','uses'=>'BomReportController@index','middleware' => ['permission:bom-index']]);
	Route::post('bom/report/list',['as'=>'bom.report.list','uses'=>'BomReportController@list']);

	Route::get('deliveryPlan/create',['as'=>'deliveryPlan.create','uses'=>'DeliveryPlanController@create']);
	Route::get('deliveryPlan/generate',['as'=>'deliveryPlan.generate','uses'=>'DeliveryPlanController@generatePlan']);
	Route::get('deliveryPlan/reGenerate',['as'=>'deliveryPlan.reGenerate','uses'=>'DeliveryPlanController@reGeneratePlan']);
	Route::get('deliveryPlan/listSo',['as'=>'deliveryPlan.listSo','uses'=>'DeliveryPlanController@listSo']);
	Route::get('deliveryPlan/listArticle',['as'=>'deliveryPlan.listArticle','uses'=>'DeliveryPlanController@listArticle']);
	Route::post('deliveryPlan/update',['as'=>'deliveryPlan.update','uses'=>'DeliveryPlanController@update']);
	Route::get('deliveryPlan/list/detail',['as'=>'deliveryPlan.detail.list','uses'=>'DeliveryPlanController@listDetail']);

	Route::get('workingOrders',['as'=>'workingOrders.index','uses'=>'WorkingOrderController@index','middleware' => ['permission:workingOrder-index']]);
	Route::get('workingOrders/create',['as'=>'workingOrder.create','uses'=>'WorkingOrderController@create','middleware' => ['permission:workingOrder-create']]);
	Route::post('workingOrders/store',['as'=>'workingOrder.store','uses'=>'WorkingOrderController@store']);
	Route::get('workingOrders/list',['as'=>'workingOrder.list','uses'=>'WorkingOrderController@list']);
	Route::get('workingOrders/list/detail',['as'=>'workingOrder.detail.list','uses'=>'WorkingOrderController@listDetail']);
	Route::get('workingOrders/show',['as'=>'workingOrder.show','uses'=>'WorkingOrderController@show']);
	Route::get('workingOrders/edit',['as'=>'workingOrder.edit','uses'=>'WorkingOrderController@edit','middleware' => ['permission:workingOrder-edit']]);
	Route::post('workingOrders/update',['as'=>'workingOrder.update','uses'=>'WorkingOrderController@update']);
	Route::post('workingOrders/delete',['as'=>'workingOrder.destroy','uses'=>'WorkingOrderController@destroy']);
	Route::get('workingOrders/code/create',['as'=>'workingOrder.code.create','uses'=>'WorkingOrderController@articleCodeCreate']);
	Route::get('workingOrders/print',['as'=>'workingOrder.print','uses'=>'WorkingOrderController@print']);

	Route::get('workOrderSheet',['as'=>'workingOrderSheets.index','uses'=>'WorkingOrderSheetController@index','middleware' => ['permission:workingOrder-index']]);
	Route::get('workOrderSheet/create',['as'=>'workingOrderSheet.create','uses'=>'WorkingOrderSheetController@create','middleware' => ['permission:workingOrder-create']]);
	Route::post('workOrderSheet/store',['as'=>'workingOrderSheet.store','uses'=>'WorkingOrderSheetController@store']);
	Route::get('workOrderSheet/list',['as'=>'workingOrderSheet.list','uses'=>'WorkingOrderSheetController@list']);
	Route::get('workOrderSheet/list/detail',['as'=>'workingOrderSheet.detail.list','uses'=>'WorkingOrderSheetController@listDetail']);
	Route::get('workOrderSheet/show',['as'=>'workingOrderSheet.show','uses'=>'WorkingOrderSheetController@show']);
	Route::get('workOrderSheet/edit',['as'=>'workingOrderSheet.edit','uses'=>'WorkingOrderSheetController@edit','middleware' => ['permission:workingOrder-edit']]);
	Route::post('workOrderSheet/update',['as'=>'workingOrderSheet.update','uses'=>'WorkingOrderSheetController@update']);
	Route::post('workOrderSheet/delete',['as'=>'workingOrderSheet.destroy','uses'=>'WorkingOrderSheetController@destroy']);
	Route::get('workOrderSheet/code/create',['as'=>'workingOrderSheet.code.create','uses'=>'WorkingOrderSheetController@articleCodeCreate']);
	Route::get('workOrderSheet/print',['as'=>'workingOrderSheet.print','uses'=>'WorkingOrderSheetController@print']);
	Route::post('workOrderSheet/approve',['as'=>'workingOrderSheet.approve','uses'=>'WorkingOrderSheetController@approve']);
	Route::get('workOrderSheet/revision',['as'=>'workingOrderSheet.revision','uses'=>'WorkingOrderSheetController@revision','middleware' => ['permission:workingOrder-revision']]);
	Route::get('workOrderSheet/get/tack',['as'=>'workingOrderSheet.get.tack','uses'=>'WorkingOrderSheetController@getTack']);
	Route::get('workOrderSheet/get/qty/so',['as'=>'workingOrderSheet.get.qty.so','uses'=>'WorkingOrderSheetController@getQtySo']);

	Route::get('production',['as'=>'production.index','uses'=>'ProductionController@index','middleware' => ['permission:production-index']]);
	Route::get('production/create',['as'=>'production.create','uses'=>'ProductionController@create','middleware' => ['permission:production-create']]);
	Route::post('production/store',['as'=>'production.store','uses'=>'ProductionController@store']);
	Route::get('production/list',['as'=>'production.list','uses'=>'ProductionController@list']);
	Route::get('production/list/detail',['as'=>'production.detail.list','uses'=>'ProductionController@listDetail']);
	Route::get('production/show',['as'=>'production.show','uses'=>'ProductionController@show']);
	Route::get('production/edit',['as'=>'production.edit','uses'=>'ProductionController@edit','middleware' => ['permission:production-edit']]);
	Route::post('production/update',['as'=>'production.update','uses'=>'ProductionController@update']);
	Route::post('production/delete',['as'=>'production.destroy','uses'=>'ProductionController@destroy']);
	Route::get('production/code/create',['as'=>'production.code.create','uses'=>'ProductionController@articleCodeCreate']);
	Route::get('production/print',['as'=>'production.print','uses'=>'ProductionController@print']);
	Route::post('production/posting',['as'=>'production.posting','uses'=>'ProductionController@posting']);
	Route::get('production/wos/detail',['as'=>'production.wos.detail','uses'=>'ProductionController@wosDetail']);
	Route::post('production/approve',['as'=>'production.approve','uses'=>'ProductionController@approve']);
	Route::get('production/revision',['as'=>'production.revision','uses'=>'ProductionController@revision','middleware' => ['permission:production-revision']]);

	Route::get('actualLoading',['as'=>'production.actualLoading.index','uses'=>'Production\ActualLoadingController@index','middleware' => ['permission:actualLoading-index']]);
	Route::get('actualLoading/create',['as'=>'production.actualLoading.create','uses'=>'Production\ActualLoadingController@create','middleware' => ['permission:actualLoading-create']]);
	Route::post('actualLoading/store',['as'=>'production.actualLoading.store','uses'=>'Production\ActualLoadingController@store']);
	Route::get('actualLoading/list',['as'=>'production.actualLoading.list','uses'=>'Production\ActualLoadingController@list']);
	Route::get('actualLoading/list/detail',['as'=>'production.actualLoading.list.detail','uses'=>'Production\ActualLoadingController@listDetail']);
	Route::get('actualLoading/show',['as'=>'production.actualLoading.show','uses'=>'Production\ActualLoadingController@show']);
	Route::get('actualLoading/edit',['as'=>'production.actualLoading.edit','uses'=>'Production\ActualLoadingController@edit','middleware' => ['permission:actualLoading-edit']]);
	Route::post('actualLoading/update',['as'=>'production.actualLoading.update','uses'=>'Production\ActualLoadingController@update']);
	Route::post('actualLoading/delete',['as'=>'production.actualLoading.destroy','uses'=>'Production\ActualLoadingController@destroy']);
	Route::get('actualLoading/code/create',['as'=>'production.actualLoading.code.create','uses'=>'Production\ActualLoadingController@articleCodeCreate']);
	Route::get('actualLoading/print',['as'=>'production.actualLoading.print','uses'=>'Production\ActualLoadingController@print']);
	Route::post('actualLoading/posting',['as'=>'production.actualLoading.posting','uses'=>'Production\ActualLoadingController@posting']);
	Route::get('actualLoading/wos/detail',['as'=>'production.actualLoading.wos.detail','uses'=>'Production\ActualLoadingController@wosDetail']);
	Route::post('actualLoading/approve',['as'=>'production.actualLoading.approve','uses'=>'Production\ActualLoadingController@approve']);
	Route::get('actualLoading/revision',['as'=>'production.actualLoading.revision','uses'=>'Production\ActualLoadingController@revision','middleware' => ['permission:actualLoading-revision']]);

	Route::get('actualFinishGoods',['as'=>'production.actualFinishGoods.index','uses'=>'Production\ActualFinishGoodsController@index','middleware' => ['permission:actualFinishGoods-index']]);
	Route::get('actualFinishGoods/list',['as'=>'production.actualFinishGoods.list','uses'=>'Production\ActualFinishGoodsController@list']);
	Route::get('actualFinishGoods/list/detail',['as'=>'production.actualFinishGoods.list.detail','uses'=>'Production\ActualFinishGoodsController@listDetail']);
	Route::get('actualFinishGoods/show',['as'=>'production.actualFinishGoods.show','uses'=>'Production\ActualFinishGoodsController@show']);
	Route::get('actualFinishGoods/edit',['as'=>'production.actualFinishGoods.edit','uses'=>'Production\ActualFinishGoodsController@edit','middleware' => ['permission:actualFinishGoods-edit']]);
	Route::post('actualFinishGoods/update',['as'=>'production.actualFinishGoods.update','uses'=>'Production\ActualFinishGoodsController@update']);
	Route::post('actualFinishGoods/delete',['as'=>'production.actualFinishGoods.destroy','uses'=>'Production\ActualFinishGoodsController@destroy']);
	Route::get('actualFinishGoods/code/create',['as'=>'production.actualFinishGoods.code.create','uses'=>'Production\ActualFinishGoodsController@articleCodeCreate']);
	Route::get('actualFinishGoods/print',['as'=>'production.actualFinishGoods.print','uses'=>'Production\ActualFinishGoodsController@print']);
	Route::post('actualFinishGoods/posting',['as'=>'production.actualFinishGoods.posting','uses'=>'Production\ActualFinishGoodsController@posting']);
	Route::post('actualFinishGoods/approve',['as'=>'production.actualFinishGoods.approve','uses'=>'Production\ActualFinishGoodsController@approve']);
	Route::get('actualFinishGoods/revision',['as'=>'production.actualFinishGoods.revision','uses'=>'Production\ActualFinishGoodsController@revision','middleware' => ['permission:actualFinishGoods-revision']]);

	Route::get('pettyCashs',['as'=>'pettyCashs.index','uses'=>'PettyCashController@index','middleware' => ['permission:pettyCash-index']]);
	Route::get('pettyCashs/create',['as'=>'pettyCash.create','uses'=>'PettyCashController@create','middleware' => ['permission:pettyCash-create']]);
	Route::post('pettyCashs/store',['as'=>'pettyCash.store','uses'=>'PettyCashController@store']);
	Route::get('pettyCashs/list',['as'=>'pettyCash.list','uses'=>'PettyCashController@list']);
	Route::get('pettyCashs/show',['as'=>'pettyCash.show','uses'=>'PettyCashController@show']);
	Route::get('pettyCashs/edit',['as'=>'pettyCash.edit','uses'=>'PettyCashController@edit','middleware' => ['permission:pettyCash-edit']]);
	Route::post('pettyCashs/update',['as'=>'pettyCash.update','uses'=>'PettyCashController@update']);
	Route::post('pettyCashs/delete',['as'=>'pettyCash.destroy','uses'=>'PettyCashController@destroy']);
	Route::post('pettyCashs/clear',['as'=>'pettyCash.clear','uses'=>'PettyCashController@clear']);
	Route::get('pettyCashs/code/create',['as'=>'pettyCash.code.create','uses'=>'PettyCashController@articleCodeCreate']);
	Route::get('pettyCashs/print',['as'=>'pettyCash.print','uses'=>'PettyCashController@print']);
	Route::get('pettyCashs/price/list',['as'=>'pettyCash.price.list','uses'=>'PettyCashController@priceList']);
	Route::get('pettyCashs/revision',['as'=>'pettyCash.revision','uses'=>'PettyCashController@revision','middleware' => ['permission:pettyCash-revision']]);
	Route::get('pettyCashs/validate',['as'=>'pettyCash.validate','uses'=>'PettyCashController@validasi']);
	Route::get('pettyCashs/authorize',['as'=>'pettyCash.authorize','uses'=>'PettyCashController@otorisasi']);

	Route::get('approval',['as'=>'approval.index','uses'=>'ApprovalController@index','middleware' => ['permission:approval-index']]);
	Route::get('approval/create/level',['as'=>'approval.create.level','uses'=>'ApprovalController@createLevel','middleware' => ['permission:approval-create']]);
	Route::post('approval/store/level',['as'=>'approval.store.level','uses'=>'ApprovalController@storeLevel']);
	Route::get('approval/list/master',['as'=>'approval.list.master','uses'=>'ApprovalController@listMaster']);
	Route::get('approval/list/level',['as'=>'approval.list.level','uses'=>'ApprovalController@listLevel']);
	Route::get('approval/edit/level',['as'=>'approval.edit.level','uses'=>'ApprovalController@editLevel','middleware' => ['permission:approval-edit']]);
	Route::post('approval/update/level',['as'=>'approval.update.level','uses'=>'ApprovalController@updateLevel']);
	Route::post('approval/delete/level',['as'=>'approval.destroy.level','uses'=>'ApprovalController@destroyLevel']);

	Route::get('warehouse',['as'=>'warehouse.index','uses'=>'WarehouseController@index','middleware' => ['permission:warehouse-index']]);
	// Route::get('warehouse/create',['as'=>'warehouse.create','uses'=>'WarehouseController@create','middleware' => ['permission:warehouse-create']]);
	Route::get('warehouse/transferIn',['as'=>'warehouse.transferIn','uses'=>'WarehouseController@transferIn','middleware' => ['permission:warehouse-create']]);
	Route::get('warehouse/transferOut',['as'=>'warehouse.transferOut','uses'=>'WarehouseController@transferOut','middleware' => ['permission:warehouse-create']]);
	Route::post('warehouse/store',['as'=>'warehouse.store','uses'=>'WarehouseController@store']);
	Route::get('warehouse/list',['as'=>'warehouse.list','uses'=>'WarehouseController@list']);
	Route::get('warehouse/list/detail',['as'=>'warehouse.list.detail','uses'=>'WarehouseController@listDetail']);
	Route::get('warehouse/show',['as'=>'warehouse.show','uses'=>'WarehouseController@show']);
	Route::get('warehouse/edit',['as'=>'warehouse.edit','uses'=>'WarehouseController@edit','middleware' => ['permission:warehouse-edit']]);
	Route::post('warehouse/update',['as'=>'warehouse.update','uses'=>'WarehouseController@update']);
	Route::post('warehouse/delete',['as'=>'warehouse.destroy','uses'=>'WarehouseController@destroy']);
	Route::get('warehouse/code/create',['as'=>'warehouse.code.create','uses'=>'WarehouseController@articleCodeCreate']);
	Route::get('warehouse/approve',['as'=>'warehouse.approve','uses'=>'WarehouseController@approve']);
	Route::get('warehouse/posting',['as'=>'warehouse.posting','uses'=>'WarehouseController@posting']);
	Route::get('warehouse/article',['as'=>'warehouse.article','uses'=>'WarehouseController@article','middleware' => ['permission:warehouse-index']]);
	Route::get('warehouse/articles/list',['as'=>'warehouse.article.list','uses'=>'WarehouseController@listArticle']);

	Route::get('transferIn',['as'=>'transferIn.index','uses'=>'TransferInController@index','middleware' => ['permission:transferIn-index']]);
	Route::get('transferIn/create',['as'=>'transferIn.create','uses'=>'TransferInController@create']);
	Route::post('transferIn/store',['as'=>'transferIn.store','uses'=>'TransferInController@store']);
	Route::get('transferIn/list',['as'=>'transferIn.list','uses'=>'TransferInController@list']);
	Route::get('transferIn/list/detail',['as'=>'transferIn.list.detail','uses'=>'TransferInController@listDetail']);
	Route::get('transferIn/show',['as'=>'transferIn.show','uses'=>'TransferInController@show']);
	Route::get('transferIn/edit',['as'=>'transferIn.edit','uses'=>'TransferInController@edit','middleware' => ['permission:transferIn-edit']]);
	Route::post('transferIn/update',['as'=>'transferIn.update','uses'=>'TransferInController@update']);
	Route::post('transferIn/delete',['as'=>'transferIn.destroy','uses'=>'TransferInController@destroy']);
	Route::get('transferIn/approve',['as'=>'transferIn.approve','uses'=>'TransferInController@approve']);
	Route::post('transferIn/posting',['as'=>'transferIn.posting','uses'=>'TransferInController@posting']);
	Route::post('transferIn/cancel',['as'=>'transferIn.cancel','uses'=>'TransferInController@cancel']);
	Route::get('transferIn/article',['as'=>'transferIn.article','uses'=>'TransferInController@article','middleware' => ['permission:transferIn-index']]);
	Route::get('transferIn/print',['as'=>'transferIn.print','uses'=>'TransferInController@print']);

	Route::get('transferOut',['as'=>'transferOut.index','uses'=>'TransferOutController@index','middleware' => ['permission:transferOut-index']]);
	Route::get('transferOut/create',['as'=>'transferOut.create','uses'=>'TransferOutController@create']);
	Route::post('transferOut/store',['as'=>'transferOut.store','uses'=>'TransferOutController@store']);
	Route::get('transferOut/list',['as'=>'transferOut.list','uses'=>'TransferOutController@list']);
	Route::get('transferOut/list/detail',['as'=>'transferOut.list.detail','uses'=>'TransferOutController@listDetail']);
	Route::get('transferOut/show',['as'=>'transferOut.show','uses'=>'TransferOutController@show']);
	Route::get('transferOut/edit',['as'=>'transferOut.edit','uses'=>'TransferOutController@edit','middleware' => ['permission:transferOut-edit']]);
	Route::post('transferOut/update',['as'=>'transferOut.update','uses'=>'TransferOutController@update']);
	Route::post('transferOut/delete',['as'=>'transferOut.destroy','uses'=>'TransferOutController@destroy']);
	Route::get('transferOut/approve',['as'=>'transferOut.approve','uses'=>'TransferOutController@approve']);
	Route::post('transferOut/posting',['as'=>'transferOut.posting','uses'=>'TransferOutController@posting']);
	Route::post('transferOut/cancel',['as'=>'transferOut.cancel','uses'=>'TransferOutController@cancel']);
	Route::get('transferOut/article',['as'=>'transferOut.article','uses'=>'TransferOutController@article','middleware' => ['permission:transferOut-index']]);
	Route::get('transferOut/print',['as'=>'transferOut.print','uses'=>'TransferOutController@print']);
	Route::get('transferOut/article/tso',['as'=>'transferOut.article.tso','uses'=>'TransferOutController@articleTso']);

	Route::get('wosMixing',['as'=>'wosMixing.index','uses'=>'WosMixingController@index','middleware' => ['permission:wosMixing-index']]);
	Route::get('wosMixing/create',['as'=>'wosMixing.create','uses'=>'WosMixingController@create','middleware' => ['permission:wosMixing-create']]);
	Route::post('wosMixing/store',['as'=>'wosMixing.store','uses'=>'WosMixingController@store']);
	Route::get('wosMixing/list',['as'=>'wosMixing.list','uses'=>'WosMixingController@list']);
	Route::get('wosMixing/list/detail',['as'=>'wosMixing.list.detail','uses'=>'WosMixingController@listDetail']);
	Route::get('wosMixing/show',['as'=>'wosMixing.show','uses'=>'WosMixingController@show']);
	Route::get('wosMixing/edit',['as'=>'wosMixing.edit','uses'=>'WosMixingController@edit','middleware' => ['permission:wosMixing-edit']]);
	Route::post('wosMixing/update',['as'=>'wosMixing.update','uses'=>'WosMixingController@update']);
	Route::post('wosMixing/delete',['as'=>'wosMixing.destroy','uses'=>'WosMixingController@destroy']);
	Route::get('wosMixing/approve',['as'=>'wosMixing.approve','uses'=>'WosMixingController@approve']);
	Route::post('wosMixing/posting',['as'=>'wosMixing.posting','uses'=>'WosMixingController@posting']);
	Route::get('wosMixing/article/mix',['as'=>'wosMixing.article.mix','uses'=>'WosMixingController@articleMix']);
	Route::get('wosMixing/print',['as'=>'wosMixing.print','uses'=>'WosMixingController@print']);
	Route::post('wosMixing/cancel',['as'=>'wosMixing.cancel','uses'=>'WosMixingController@cancel']);
	
	Route::get('deliveryInstruction',['as'=>'deliveryInstruction.index','uses'=>'DeliveryInstructionController@index','middleware' => ['permission:deliveryInstruction-index']]);
	Route::get('deliveryInstruction/create',['as'=>'deliveryInstruction.create','uses'=>'DeliveryInstructionController@create','middleware' => ['permission:deliveryInstruction-create']]);
	Route::post('deliveryInstruction/store',['as'=>'deliveryInstruction.store','uses'=>'DeliveryInstructionController@store']);
	Route::get('deliveryInstruction/list',['as'=>'deliveryInstruction.list','uses'=>'DeliveryInstructionController@list']);
	Route::get('deliveryInstruction/list/detail',['as'=>'deliveryInstruction.list.detail','uses'=>'DeliveryInstructionController@listDetail']);
	Route::get('deliveryInstruction/show',['as'=>'deliveryInstruction.show','uses'=>'DeliveryInstructionController@show']);
	Route::get('deliveryInstruction/edit',['as'=>'deliveryInstruction.edit','uses'=>'DeliveryInstructionController@edit','middleware' => ['permission:deliveryInstruction-edit']]);
	Route::post('deliveryInstruction/update',['as'=>'deliveryInstruction.update','uses'=>'DeliveryInstructionController@update']);
	Route::post('deliveryInstruction/delete',['as'=>'deliveryInstruction.destroy','uses'=>'DeliveryInstructionController@destroy']);
	Route::post('deliveryInstruction/clear',['as'=>'deliveryInstruction.clear','uses'=>'DeliveryInstructionController@clear']);
	Route::get('deliveryInstruction/code/create',['as'=>'deliveryInstruction.code.create','uses'=>'DeliveryInstructionController@articleCodeCreate']);
	Route::get('deliveryInstruction/print',['as'=>'deliveryInstruction.print','uses'=>'DeliveryInstructionController@print']);
	Route::get('deliveryInstruction/revision',['as'=>'deliveryInstruction.revision','uses'=>'DeliveryInstructionController@revision','middleware' => ['permission:deliveryInstruction-revision']]);
	Route::get('deliveryInstruction/approve',['as'=>'deliveryInstruction.approve','uses'=>'DeliveryInstructionController@approve']);
	Route::get('deliveryInstruction/article/list',['as'=>'deliveryInstruction.article.list','uses'=>'DeliveryInstructionController@articleList']);
	Route::get('deliveryInstruction/qty/po',['as'=>'deliveryInstruction.qty.po','uses'=>'DeliveryInstructionController@qtyPo']);

	Route::get('kasPenerimaan',['as'=>'kasPenerimaan.index','uses'=>'Accounting\KasPenerimaanController@index','middleware' => ['permission:pettyCash-index']]);
	// Route::get('kasPenerimaan/create',['as'=>'kasPenerimaan.create','uses'=>'Accounting\KasPenerimaanController@create','middleware' => ['permission:kasPenerimaan-create']]);
	Route::get('kasPenerimaan/create',['as'=>'kasPenerimaan.create','uses'=>'Accounting\KasPenerimaanController@create']);
	Route::post('kasPenerimaan/store',['as'=>'kasPenerimaan.store','uses'=>'Accounting\KasPenerimaanController@store']);
	Route::get('kasPenerimaan/list',['as'=>'kasPenerimaan.list','uses'=>'Accounting\KasPenerimaanController@list']);
	Route::get('kasPenerimaan/show',['as'=>'kasPenerimaan.show','uses'=>'Accounting\KasPenerimaanController@show']);
	// Route::get('kasPenerimaan/edit',['as'=>'kasPenerimaan.edit','uses'=>'Accounting\KasPenerimaanController@edit','middleware' => ['permission:kasPenerimaan-edit']]);
	Route::get('kasPenerimaan/edit',['as'=>'kasPenerimaan.edit','uses'=>'Accounting\KasPenerimaanController@edit']);
	Route::post('kasPenerimaan/update',['as'=>'kasPenerimaan.update','uses'=>'Accounting\KasPenerimaanController@update']);
	Route::post('kasPenerimaan/delete',['as'=>'kasPenerimaan.destroy','uses'=>'Accounting\KasPenerimaanController@destroy']);
	// Route::post('kasPenerimaan/clear',['as'=>'kasPenerimaan.clear','uses'=>'Accounting\KasPenerimaanController@clear']);
	// Route::get('kasPenerimaan/code/create',['as'=>'kasPenerimaan.code.create','uses'=>'Accounting\KasPenerimaanController@articleCodeCreate']);
	Route::get('kasPenerimaan/print',['as'=>'kasPenerimaan.print','uses'=>'Accounting\KasPenerimaanController@print']);
	// Route::get('kasPenerimaan/revision',['as'=>'kasPenerimaan.revision','uses'=>'Accounting\KasPenerimaanController@revision','middleware' => ['permission:kasPenerimaan-revision']]);
	// Route::get('kasPenerimaan/validate',['as'=>'kasPenerimaan.validate','uses'=>'Accounting\KasPenerimaanController@validasi']);
	Route::get('kasPenerimaan/approve',['as'=>'kasPenerimaan.approve','uses'=>'Accounting\KasPenerimaanController@approve']);
	Route::get('kasPenerimaan/get/invoice/ammount',['as'=>'kasPenerimaan.get.invoice.amount','uses'=>'Accounting\KasPenerimaanController@getInvoiceAmount']);

	Route::get('kasKeluar',['as'=>'kasKeluar.index','uses'=>'Accounting\KasKeluarController@index','middleware' => ['permission:pettyCash-index']]);
	Route::get('kasKeluar/create',['as'=>'kasKeluar.create','uses'=>'Accounting\KasKeluarController@create']);
	Route::post('kasKeluar/store',['as'=>'kasKeluar.store','uses'=>'Accounting\KasKeluarController@store']);
	Route::get('kasKeluar/list',['as'=>'kasKeluar.list','uses'=>'Accounting\KasKeluarController@list']);
	Route::get('kasKeluar/show',['as'=>'kasKeluar.show','uses'=>'Accounting\KasKeluarController@show']);
	Route::get('kasKeluar/edit',['as'=>'kasKeluar.edit','uses'=>'Accounting\KasKeluarController@edit']);
	Route::post('kasKeluar/update',['as'=>'kasKeluar.update','uses'=>'Accounting\KasKeluarController@update']);
	Route::post('kasKeluar/delete',['as'=>'kasKeluar.destroy','uses'=>'Accounting\KasKeluarController@destroy']);
	Route::get('kasKeluar/print',['as'=>'kasKeluar.print','uses'=>'Accounting\KasKeluarController@print']);
	Route::get('kasKeluar/approve',['as'=>'kasKeluar.approve','uses'=>'Accounting\KasKeluarController@approve']);
	Route::get('kasKeluar/get/invoice/ammount',['as'=>'kasKeluar.get.invoice.amount','uses'=>'Accounting\KasKeluarController@getInvoiceAmount']);

	Route::get('bankPenerimaan',['as'=>'bankPenerimaan.index','uses'=>'Accounting\BankPenerimaanController@index','middleware' => ['permission:pettyCash-index']]);
	Route::get('bankPenerimaan/create',['as'=>'bankPenerimaan.create','uses'=>'Accounting\BankPenerimaanController@create']);
	Route::post('bankPenerimaan/store',['as'=>'bankPenerimaan.store','uses'=>'Accounting\BankPenerimaanController@store']);
	Route::get('bankPenerimaan/list',['as'=>'bankPenerimaan.list','uses'=>'Accounting\BankPenerimaanController@list']);
	Route::get('bankPenerimaan/show',['as'=>'bankPenerimaan.show','uses'=>'Accounting\BankPenerimaanController@show']);
	Route::get('bankPenerimaan/edit',['as'=>'bankPenerimaan.edit','uses'=>'Accounting\BankPenerimaanController@edit']);
	Route::post('bankPenerimaan/update',['as'=>'bankPenerimaan.update','uses'=>'Accounting\BankPenerimaanController@update']);
	Route::post('bankPenerimaan/delete',['as'=>'bankPenerimaan.destroy','uses'=>'Accounting\BankPenerimaanController@destroy']);
	Route::get('bankPenerimaan/print',['as'=>'bankPenerimaan.print','uses'=>'Accounting\BankPenerimaanController@print']);
	Route::get('bankPenerimaan/approve',['as'=>'bankPenerimaan.approve','uses'=>'Accounting\BankPenerimaanController@approve']);
	Route::get('bankPenerimaan/get/invoice/ammount',['as'=>'bankPenerimaan.get.invoice.amount','uses'=>'Accounting\BankPenerimaanController@getInvoiceAmount']);

	Route::get('bankKeluar',['as'=>'bankKeluar.index','uses'=>'Accounting\BankKeluarController@index','middleware' => ['permission:pettyCash-index']]);
	Route::get('bankKeluar/create',['as'=>'bankKeluar.create','uses'=>'Accounting\BankKeluarController@create']);
	Route::post('bankKeluar/store',['as'=>'bankKeluar.store','uses'=>'Accounting\BankKeluarController@store']);
	Route::get('bankKeluar/list',['as'=>'bankKeluar.list','uses'=>'Accounting\BankKeluarController@list']);
	Route::get('bankKeluar/show',['as'=>'bankKeluar.show','uses'=>'Accounting\BankKeluarController@show']);
	Route::get('bankKeluar/edit',['as'=>'bankKeluar.edit','uses'=>'Accounting\BankKeluarController@edit']);
	Route::post('bankKeluar/update',['as'=>'bankKeluar.update','uses'=>'Accounting\BankKeluarController@update']);
	Route::post('bankKeluar/delete',['as'=>'bankKeluar.destroy','uses'=>'Accounting\BankKeluarController@destroy']);
	Route::get('bankKeluar/print',['as'=>'bankKeluar.print','uses'=>'Accounting\BankKeluarController@print']);
	Route::get('bankKeluar/approve',['as'=>'bankKeluar.approve','uses'=>'Accounting\BankKeluarController@approve']);
	Route::get('bankKeluar/get/invoice/ammount',['as'=>'bankKeluar.get.invoice.amount','uses'=>'Accounting\BankKeluarController@getInvoiceAmount']);

	// Route::get('generalJournal',['as'=>'generalJournal.index','uses'=>'Accounting\GeneralJournalController@index','middleware' => ['permission:pettyCash-index']]);
	// Route::get('generalJournal/create',['as'=>'generalJournal.create','uses'=>'Accounting\GeneralJournalController@create']);
	// Route::post('generalJournal/store',['as'=>'generalJournal.store','uses'=>'Accounting\GeneralJournalController@store']);
	// Route::get('generalJournal/list',['as'=>'generalJournal.list','uses'=>'Accounting\GeneralJournalController@list']);
	// Route::get('generalJournal/show',['as'=>'generalJournal.show','uses'=>'Accounting\GeneralJournalController@show']);
	// Route::get('generalJournal/edit',['as'=>'generalJournal.edit','uses'=>'Accounting\GeneralJournalController@edit']);
	// Route::post('generalJournal/update',['as'=>'generalJournal.update','uses'=>'Accounting\GeneralJournalController@update']);
	// Route::post('generalJournal/delete',['as'=>'generalJournal.destroy','uses'=>'Accounting\GeneralJournalController@destroy']);
	// Route::get('generalJournal/print',['as'=>'generalJournal.print','uses'=>'Accounting\GeneralJournalController@print']);
	// Route::get('generalJournal/approve',['as'=>'generalJournal.approve','uses'=>'Accounting\GeneralJournalController@approve']);
	// Route::get('generalJournal/get/invoice/ammount',['as'=>'generalJournal.get.invoice.amount','uses'=>'Accounting\GeneralJournalController@getInvoiceAmount']);

	Route::get('forecastSales',['as'=>'forecastSales.index','uses'=>'Forecasting\ForcastingSalesController@index']);
	Route::get('forecastSales/create',['as'=>'forecastSales.create','uses'=>'Forecasting\ForcastingSalesController@create']);
	Route::post('forecastSales/store',['as'=>'forecastSales.store','uses'=>'Forecasting\ForcastingSalesController@store']);
	Route::get('forecastSales/list',['as'=>'forecastSales.list','uses'=>'Forecasting\ForcastingSalesController@list']);
	Route::get('forecastSales/show',['as'=>'forecastSales.show','uses'=>'Forecasting\ForcastingSalesController@show']);
	Route::get('forecastSales/edit',['as'=>'forecastSales.edit','uses'=>'Forecasting\ForcastingSalesController@edit']);
	Route::post('forecastSales/update',['as'=>'forecastSales.update','uses'=>'Forecasting\ForcastingSalesController@update']);
	Route::post('forecastSales/delete',['as'=>'forecastSales.destroy','uses'=>'Forecasting\ForcastingSalesController@destroy']);
	Route::get('forecastSales/print',['as'=>'forecastSales.print','uses'=>'Forecasting\ForcastingSalesController@print']);
	Route::get('forecastSales/approve',['as'=>'forecastSales.approve','uses'=>'Forecasting\ForcastingSalesController@approve']);

	Route::post('forecastSales/get/article',['as'=>'forecastSales.get.article','uses'=>'Forecasting\ForcastingSalesController@getArticle']);
	Route::post('forecastSales/get/qty/article',['as'=>'forecastSales.get.qty.article','uses'=>'Forecasting\ForcastingSalesController@getQtyArticle']);
	Route::post('forecastSales/get/list/article',['as'=>'forecastSales.get.list.article','uses'=>'Forecasting\ForcastingSalesController@getListArticle']);


	Route::get('forecastPurchase',['as'=>'forecastPurchase.index','uses'=>'Forecasting\ForcastingPurchaseController@index']);
	Route::get('forecastPurchase/create',['as'=>'forecastPurchase.create','uses'=>'Forecasting\ForcastingPurchaseController@create']);
	Route::post('forecastPurchase/store',['as'=>'forecastPurchase.store','uses'=>'Forecasting\ForcastingPurchaseController@store']);
	Route::post('forecastPurchase/delete',['as'=>'forecastPurchase.destroy','uses'=>'Forecasting\ForcastingPurchaseController@destroy']);
	Route::get('forecastPurchase/print',['as'=>'forecastPurchase.print','uses'=>'Forecasting\ForcastingPurchaseController@print']);
	Route::post('forecastPurchase/get/article',['as'=>'forecastPurchase.get.article','uses'=>'Forecasting\ForcastingPurchaseController@getArticle']);
	Route::post('forecastPurchase/get/qty/article',['as'=>'forecastPurchase.get.qty.article','uses'=>'Forecasting\ForcastingPurchaseController@getQtyArticle']);
	Route::post('forecastPurchase/get/list/article',['as'=>'forecastPurchase.get.list.article','uses'=>'Forecasting\ForcastingPurchaseController@getListArticle']);

	Route::get('stockTake',['as'=>'stockTake.index','uses'=>'StockTake\StockTakeController@index']);
	Route::post('stockTake/import',['as'=>'stockTake.import','uses'=>'StockTake\StockTakeController@import']);
	Route::get('stockTake/export',['as'=>'stockTake.export','uses'=>'StockTake\StockTakeController@export']);

	Route::post('dynamic/dependent',['as'=>'dynamic.dependent','uses'=>'DependentController@dependentFetch']);

	Route::get('monitoring/qtyNotBalance',['as'=>'monitoring.qtyNotBalance','uses'=>'MonitoringController@qtyNotBalance']);
	Route::post('monitoring/qtyNotBalance/list',['as'=>'monitoring.qtyNotBalance.list','uses'=>'MonitoringController@qtyNotBalanceList']);

	Route::get('add-to-log', ['as'=>'add.to.log','uses'=>'LogActivityController@myTestAddToLog']);
	Route::get('showLogLists', ['as'=>'show.log.lists','uses'=>'LogActivityController@showLogLists']);
	Route::get('logActivity',['as'=>'log.activity','uses'=>'LogActivityController@index']);

	Route::get('database-backup', function (Request $request) {
		Artisan::call('database:backup');
		$message  = "Backup Database Success";
		\LogActivity::addToLog('Command',"Jobs $message");
		return redirect()->back()->with('success',$message); 

	})->name('database.backup');

	Route::get('git-pull', function (Request $request) {
		Artisan::call('app:git_pull');
		$message  = "Git Pull Success";
		\LogActivity::addToLog('Command',"Jobs $message");
		return redirect()->back()->with('success',$message); 
	})->name('git.pull');

	// clear chace untuk browser
	Route::get('/clear-cache', function() {
		Artisan::call('cache:clear');
		return "Cache is cleared";
	});
	
	// kalo routing nya tidak di temukan maka keluar error 404
	// Route::any('{all}', function(){
	//     return view('errors.404_2');
	// })->where('all', '.*');
    
});
