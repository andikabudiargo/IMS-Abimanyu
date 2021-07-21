<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       $permissions = [
            [
                'name' => 'role-menu',
                'group_name' => 'role',
                'display_name' => 'Menu Role',
                'description' => 'Menu of Role'
            ],
            [
                'name' => 'role-list',
                'group_name' => 'role',
                'display_name' => 'Display Role Listing',
                'description' => 'See only Listing Of Role'
            ],
            [
                'name' => 'role-create',
                'group_name' => 'role',
                'display_name' => 'Create Role',
                'description' => 'Create New Role'
            ],
            [
                'name' => 'role-edit',
                'group_name' => 'role',
                'display_name' => 'Edit Role',
                'description' => 'Edit Role'
            ],
            [
                'name' => 'role-delete',
                'group_name' => 'role',
                'display_name' => 'Delete Role',
                'description' => 'Delete Role'
            ],

            [
                'name' => 'user-menu',
                'group_name' => 'user',
                'display_name' => 'Menu User',
                'description' => 'Menu of User'
            ],

            [
                'name' => 'user-list',
                'group_name' => 'user',
                'display_name' => 'Display User Listing',
                'description' => 'See only Listing Of User'
            ],
            [
                'name' => 'user-create',
                'group_name' => 'user',
                'display_name' => 'Create User',
                'description' => 'Create New User'
            ],
            [
                'name' => 'user-edit',
                'group_name' => 'user',
                'display_name' => 'Edit User',
                'description' => 'Edit User'
            ],
            [
                'name' => 'user-delete',
                'group_name' => 'user',
                'display_name' => 'Delete User',
                'description' => 'Delete User'
            ],

            [
                'name' => 'permission-menu',
                'group_name' => 'permission',
                'display_name' => 'Menu Permission',
                'description' => 'Menu of Permission'
            ],

            [
                'name' => 'permission-list',
                'group_name' => 'permission',
                'display_name' => 'Display Permission Listing',
                'description' => 'See only Listing Of Permission'
            ],
            [
                'name' => 'permission-create',
                'group_name' => 'permission',
                'display_name' => 'Create Permission',
                'description' => 'Create New Permission'
            ],
            [
                'name' => 'permission-edit',
                'group_name' => 'permission',
                'display_name' => 'Edit Permission',
                'description' => 'Edit Permission'
            ],
            [
                'name' => 'permission-delete',
                'group_name' => 'permission',
                'display_name' => 'Delete Permission',
                'description' => 'Delete Permission'
            ],

            [
                'name' => 'log-menu',
                'group_name' => 'log',
                'display_name' => 'Menu Log',
                'description' => 'Menu of Log'
            ],

            [
                'name' => 'log-list',
                'group_name' => 'log',
                'display_name' => 'Display Log Listing',
                'description' => 'See only Listing Of Log'
            ],
            [
                'name' => 'log-create',
                'group_name' => 'log',
                'display_name' => 'Create Log',
                'description' => 'Create New Log'
            ],
            [
                'name' => 'log-edit',
                'group_name' => 'log',
                'display_name' => 'Edit Log',
                'description' => 'Edit Log'
            ],
            [
                'name' => 'log-delete',
                'group_name' => 'log',
                'display_name' => 'Delete Log',
                'description' => 'Delete Log'
            ],

        ];

        foreach ($permissions as $key => $value) {
        	Permission::create($value);
        }

        // foreach ($permissions as $permission) {
        //      Permission::create(['name' => $permission]);
        // }

    }

}
