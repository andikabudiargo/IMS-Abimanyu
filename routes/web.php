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
	Route::post('roles/create',['as'=>'roles.store','uses'=>'RoleController@store','middleware' => ['permission:role-create']]);
	Route::get('roles/{id}',['as'=>'roles.show','uses'=>'RoleController@show']);
	Route::get('roles/{id}/edit',['as'=>'roles.edit','uses'=>'RoleController@edit','middleware' => ['permission:role-edit']]);
	Route::post('roles/update',['as'=>'roles.update','uses'=>'RoleController@update','middleware' => ['permission:role-edit']]);
	// Route::delete('roles/{id}',['as'=>'roles.destroy','uses'=>'RoleController@destroy','middleware' => ['permission:role-delete']]);
	Route::post('roles/delete',['as'=>'roles.destroy','uses'=>'RoleController@destroy']);
	Route::get('rolesList',['as'=>'roles.list','uses'=>'RoleController@listRole']);
	Route::get('permissionListAll',['as'=>'permission.list.all','uses'=>'RoleController@listAllPermission']);
	

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

	Route::get('suppliers',['as'=>'suppliers.index','uses'=>'SupplierController@index','middleware' => ['permission:suppier-index']]);
	Route::get('suppliers/create',['as'=>'supplier.create','uses'=>'SupplierController@create','middleware' => ['permission:suppier-create']]);
	Route::post('suppliers/store',['as'=>'supplier.store','uses'=>'SupplierController@store']);
	Route::get('suppliers/list',['as'=>'supplier.list','uses'=>'SupplierController@list']);
	Route::get('suppliers/show',['as'=>'supplier.show','uses'=>'SupplierController@show']);
	Route::get('suppliers/edit',['as'=>'supplier.edit','uses'=>'SupplierController@edit','middleware' => ['permission:suppier-edit']]);
	Route::get('suppliers/update',['as'=>'supplier.update','uses'=>'SupplierController@update']);
	Route::post('suppliers/delete',['as'=>'supplier.destroy','uses'=>'SupplierController@destroy']);

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
	Route::post('uoms/update',['as'=>'uom.update','uses'=>'UomController@update']);
	Route::post('uoms/delete',['as'=>'uom.destroy','uses'=>'UomController@destroy']);

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
	Route::get('articles/list',['as'=>'article.list','uses'=>'ArticleController@list']);
	Route::get('articles/show',['as'=>'article.show','uses'=>'ArticleController@show']);
	Route::get('articles/edit',['as'=>'article.edit','uses'=>'ArticleController@edit','middleware' => ['permission:article-edit']]);
	Route::post('articles/update',['as'=>'article.update','uses'=>'ArticleController@update']);
	Route::post('articles/delete',['as'=>'article.destroy','uses'=>'ArticleController@destroy']);
	Route::get('articles/code/create',['as'=>'article.code.create','uses'=>'ArticleController@articleCodeCreate']);

	Route::post('dynamic/dependent',['as'=>'dynamic.dependent','uses'=>'DependentController@dependentFetch']);

	Route::get('add-to-log', ['as'=>'add.to.log','uses'=>'LogActivityController@myTestAddToLog']);
	Route::get('showLogLists', ['as'=>'show.log.lists','uses'=>'LogActivityController@showLogLists']);
	Route::get('logActivity',['as'=>'log.activity','uses'=>'LogActivityController@index']);

	
	//kalo routing nya tidak di temukan maka keluar error 404
	Route::any('{all}', function(){
	    return view('errors.404_2');
	})->where('all', '.*');
    
});
