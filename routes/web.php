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
	Route::get('salesOrders/show',['as'=>'salesOrder.show','uses'=>'SalesOrderController@show']);
	Route::get('salesOrders/edit',['as'=>'salesOrder.edit','uses'=>'SalesOrderController@edit','middleware' => ['permission:salesOrder-edit']]);
	Route::post('salesOrders/update',['as'=>'salesOrder.update','uses'=>'SalesOrderController@update']);
	Route::post('salesOrders/delete',['as'=>'salesOrder.destroy','uses'=>'SalesOrderController@destroy']);
	Route::get('salesOrders/code/create',['as'=>'salesOrder.code.create','uses'=>'SalesOrderController@articleCodeCreate']);

	Route::get('purchaseOrders',['as'=>'purchaseOrders.index','uses'=>'PurchaseOrderController@index','middleware' => ['permission:purchaseOrder-index']]);
	Route::get('purchaseOrders/create',['as'=>'purchaseOrder.create','uses'=>'PurchaseOrderController@create','middleware' => ['permission:purchaseOrder-create']]);
	Route::post('purchaseOrders/store',['as'=>'purchaseOrder.store','uses'=>'PurchaseOrderController@store']);
	Route::get('purchaseOrders/list',['as'=>'purchaseOrder.list','uses'=>'PurchaseOrderController@list']);
	Route::get('purchaseOrders/show',['as'=>'purchaseOrder.show','uses'=>'PurchaseOrderController@show']);
	Route::get('purchaseOrders/edit',['as'=>'purchaseOrder.edit','uses'=>'PurchaseOrderController@edit','middleware' => ['permission:purchaseOrder-edit']]);
	Route::post('purchaseOrders/update',['as'=>'purchaseOrder.update','uses'=>'PurchaseOrderController@update']);
	Route::post('purchaseOrders/delete',['as'=>'purchaseOrder.destroy','uses'=>'PurchaseOrderController@destroy']);
	Route::post('purchaseOrders/clear',['as'=>'purchaseOrder.clear','uses'=>'PurchaseOrderController@clear']);
	Route::get('purchaseOrders/code/create',['as'=>'purchaseOrder.code.create','uses'=>'PurchaseOrderController@articleCodeCreate']);
	Route::get('purchaseOrders/print',['as'=>'purchaseOrder.print','uses'=>'PurchaseOrderController@print']);
	Route::get('purchaseOrders/price/list',['as'=>'purchaseOrder.price.list','uses'=>'PurchaseOrderController@priceList']);
	Route::get('purchaseOrders/revision',['as'=>'purchaseOrder.revision','uses'=>'PurchaseOrderController@revision','middleware' => ['permission:purchaseOrder-revision']]);
	Route::get('purchaseOrders/validate',['as'=>'purchaseOrder.validate','uses'=>'PurchaseOrderController@validasi']);
	Route::get('purchaseOrders/authorize',['as'=>'purchaseOrder.authorize','uses'=>'PurchaseOrderController@otorisasi']);

	Route::get('receivings',['as'=>'receivings.index','uses'=>'ReceivingController@index','middleware' => ['permission:receiving-index']]);
	Route::get('receivings/create',['as'=>'receiving.create','uses'=>'ReceivingController@create','middleware' => ['permission:receiving-create']]);
	Route::get('receivings/search',['as'=>'receiving.search','uses'=>'ReceivingController@search']);
	Route::get('receivings/list/po',['as'=>'receiving.list.po','uses'=>'ReceivingController@listPo']);
	Route::get('receivings/list/uom',['as'=>'receiving.list.uom','uses'=>'ReceivingController@listUom']);
	Route::get('receivings/po/det',['as'=>'receiving.po.det','uses'=>'ReceivingController@poDetail']);
	Route::post('receivings/store',['as'=>'receiving.store','uses'=>'ReceivingController@store']);
	Route::get('receivings/list',['as'=>'receiving.list','uses'=>'ReceivingController@list']);
	Route::get('receivings/show',['as'=>'receiving.show','uses'=>'ReceivingController@show']);
	Route::get('receivings/edit',['as'=>'receiving.edit','uses'=>'ReceivingController@edit','middleware' => ['permission:receiving-edit']]);
	Route::post('receivings/update',['as'=>'receiving.update','uses'=>'ReceivingController@update']);
	Route::post('receivings/delete',['as'=>'receiving.destroy','uses'=>'ReceivingController@destroy']);
	Route::get('receivings/code/create',['as'=>'receiving.code.create','uses'=>'ReceivingController@articleCodeCreate']);
	Route::get('receivings/print',['as'=>'receiving.print','uses'=>'ReceivingController@print']);
	Route::post('receivings/posting',['as'=>'receiving.posting','uses'=>'ReceivingController@posting']);


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
	Route::post('disbursement/posting',['as'=>'disbursement.posting','uses'=>'BankDisbursementController@posting']);
	Route::get('disbursement/revision',['as'=>'disbursement.revision','uses'=>'BankDisbursementController@revision']);
	Route::get('disbursement/show',['as'=>'disbursement.show','uses'=>'BankDisbursementController@show']);

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
	Route::get('purchaseRequests/show',['as'=>'purchaseRequest.show','uses'=>'PurchaseRequestController@show']);
	Route::get('purchaseRequests/edit',['as'=>'purchaseRequest.edit','uses'=>'PurchaseRequestController@edit','middleware' => ['permission:purchaseRequest-edit']]);
	Route::post('purchaseRequests/update',['as'=>'purchaseRequest.update','uses'=>'PurchaseRequestController@update']);
	Route::post('purchaseRequests/delete',['as'=>'purchaseRequest.destroy','uses'=>'PurchaseRequestController@destroy']);
	Route::get('purchaseRequests/code/create',['as'=>'purchaseRequest.code.create','uses'=>'PurchaseRequestController@articleCodeCreate']);
	Route::get('purchaseRequests/print',['as'=>'purchaseRequest.print','uses'=>'PurchaseRequestController@print']);

	Route::get('boms',['as'=>'boms.index','uses'=>'BomController@index','middleware' => ['permission:bom-index']]);
	Route::get('boms/create',['as'=>'bom.create','uses'=>'BomController@create','middleware' => ['permission:bom-create']]);
	Route::post('boms/store',['as'=>'bom.store','uses'=>'BomController@store']);
	Route::get('boms/list',['as'=>'bom.list','uses'=>'BomController@list']);
	Route::get('boms/show',['as'=>'bom.show','uses'=>'BomController@show']);
	Route::get('boms/edit',['as'=>'bom.edit','uses'=>'BomController@edit','middleware' => ['permission:bom-edit']]);
	Route::post('boms/update',['as'=>'bom.update','uses'=>'BomController@update']);
	Route::post('boms/delete',['as'=>'bom.destroy','uses'=>'BomController@destroy']);
	Route::get('boms/code/create',['as'=>'bom.code.create','uses'=>'BomController@articleCodeCreate']);
	Route::get('boms/print',['as'=>'bom.print','uses'=>'BomController@print']);

	Route::get('deliveryPlan/create',['as'=>'deliveryPlan.create','uses'=>'DeliveryPlanController@create','middleware' => ['permission:workingOrder-create']]);
	Route::get('deliveryPlan/generate',['as'=>'deliveryPlan.generate','uses'=>'DeliveryPlanController@generatePlan']);
	Route::get('deliveryPlan/reGenerate',['as'=>'deliveryPlan.reGenerate','uses'=>'DeliveryPlanController@reGeneratePlan']);
	Route::get('deliveryPlan/listSo',['as'=>'deliveryPlan.listSo','uses'=>'DeliveryPlanController@listSo']);
	Route::get('deliveryPlan/listArticle',['as'=>'deliveryPlan.listArticle','uses'=>'DeliveryPlanController@listArticle']);
	Route::post('deliveryPlan/update',['as'=>'deliveryPlan.update','uses'=>'DeliveryPlanController@update']);
	Route::get('deliveryPlan/list/detail',['as'=>'deliveryPlan.detail.list','uses'=>'DeliveryPlanController@listDetail']);

	Route::get('workingOrders',['as'=>'workingOrders.index','uses'=>'workingOrderController@index','middleware' => ['permission:workingOrder-index']]);
	Route::get('workingOrders/create',['as'=>'workingOrder.create','uses'=>'workingOrderController@create','middleware' => ['permission:workingOrder-create']]);
	Route::post('workingOrders/store',['as'=>'workingOrder.store','uses'=>'workingOrderController@store']);
	Route::get('workingOrders/list',['as'=>'workingOrder.list','uses'=>'workingOrderController@list']);
	Route::get('workingOrders/list/detail',['as'=>'workingOrder.detail.list','uses'=>'workingOrderController@listDetail']);
	Route::get('workingOrders/show',['as'=>'workingOrder.show','uses'=>'workingOrderController@show']);
	Route::get('workingOrders/edit',['as'=>'workingOrder.edit','uses'=>'workingOrderController@edit','middleware' => ['permission:workingOrder-edit']]);
	Route::post('workingOrders/update',['as'=>'workingOrder.update','uses'=>'workingOrderController@update']);
	Route::post('workingOrders/delete',['as'=>'workingOrder.destroy','uses'=>'workingOrderController@destroy']);
	Route::get('workingOrders/code/create',['as'=>'workingOrder.code.create','uses'=>'workingOrderController@articleCodeCreate']);
	Route::get('workingOrders/print',['as'=>'workingOrder.print','uses'=>'workingOrderController@print']);

	Route::get('workingOrderSheets',['as'=>'workingOrderSheets.index','uses'=>'workingOrderSheetController@index','middleware' => ['permission:workingOrder-index']]);
	Route::get('workingOrderSheets/create',['as'=>'workingOrderSheet.create','uses'=>'workingOrderSheetController@create','middleware' => ['permission:workingOrder-create']]);
	Route::post('workingOrderSheets/store',['as'=>'workingOrderSheet.store','uses'=>'workingOrderSheetController@store']);
	Route::get('workingOrderSheets/list',['as'=>'workingOrderSheet.list','uses'=>'workingOrderSheetController@list']);
	Route::get('workingOrderSheets/list/detail',['as'=>'workingOrderSheet.detail.list','uses'=>'workingOrderSheetController@listDetail']);
	Route::get('workingOrderSheets/show',['as'=>'workingOrderSheet.show','uses'=>'workingOrderSheetController@show']);
	Route::get('workingOrderSheets/edit',['as'=>'workingOrderSheet.edit','uses'=>'workingOrderSheetController@edit','middleware' => ['permission:workingOrder-edit']]);
	Route::post('workingOrderSheets/update',['as'=>'workingOrderSheet.update','uses'=>'workingOrderSheetController@update']);
	Route::post('workingOrderSheets/delete',['as'=>'workingOrderSheet.destroy','uses'=>'workingOrderSheetController@destroy']);
	Route::get('workingOrderSheets/code/create',['as'=>'workingOrderSheet.code.create','uses'=>'workingOrderSheetController@articleCodeCreate']);
	Route::get('workingOrderSheets/print',['as'=>'workingOrderSheet.print','uses'=>'workingOrderSheetController@print']);

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

	Route::post('dynamic/dependent',['as'=>'dynamic.dependent','uses'=>'DependentController@dependentFetch']);

	Route::get('add-to-log', ['as'=>'add.to.log','uses'=>'LogActivityController@myTestAddToLog']);
	Route::get('showLogLists', ['as'=>'show.log.lists','uses'=>'LogActivityController@showLogLists']);
	Route::get('logActivity',['as'=>'log.activity','uses'=>'LogActivityController@index']);

	// clear chace untuk browser
	Route::get('/clear-cache', function() {
		Artisan::call('cache:clear');
		return "Cache is cleared";
	});
	
	//kalo routing nya tidak di temukan maka keluar error 404
	Route::any('{all}', function(){
	    return view('errors.404_2');
	})->where('all', '.*');
    
});
