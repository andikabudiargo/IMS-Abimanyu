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
	Route::get('customers/edit',['as'=>'customer.edit','uses'=>'CustomerController@edit']);
	Route::get('customers/update',['as'=>'customer.update','uses'=>'CustomerController@update']);
	Route::post('customers/delete',['as'=>'customer.destroy','uses'=>'CustomerController@destroy']);

	Route::get('suppliers',['as'=>'suppliers.index','uses'=>'SupplierController@index']);
	Route::get('suppliers/create',['as'=>'supplier.create','uses'=>'SupplierController@create']);
	Route::post('suppliers/store',['as'=>'supplier.store','uses'=>'SupplierController@store']);
	Route::get('suppliers/list',['as'=>'supplier.list','uses'=>'SupplierController@list']);
	Route::get('suppliers/show',['as'=>'supplier.show','uses'=>'SupplierController@show']);
	Route::get('suppliers/edit',['as'=>'supplier.edit','uses'=>'SupplierController@edit']);
	Route::get('suppliers/update',['as'=>'supplier.update','uses'=>'SupplierController@update']);
	Route::post('suppliers/delete',['as'=>'supplier.destroy','uses'=>'SupplierController@destroy']);

	Route::post('dynamic/dependent',['as'=>'dynamic.dependent','uses'=>'DependentController@dependentFetch']);

	Route::get('add-to-log', ['as'=>'add.to.log','uses'=>'LogActivityController@myTestAddToLog']);
	Route::get('showLogLists', ['as'=>'show.log.lists','uses'=>'LogActivityController@showLogLists']);
	Route::get('logActivity',['as'=>'log.activity','uses'=>'LogActivityController@index']);

	Route::get('rubahpsw',['as'=>'rubahpsw','uses'=>'UserController@rubahpsw']);
	Route::get('cariuser', 'UserController@search');
	
	//kalo routing nya tidak di temukan maka keluar error 404
	Route::any('{all}', function(){
	    return view('errors.404_2');
	})->where('all', '.*');
    
});
