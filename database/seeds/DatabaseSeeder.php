<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        $this->call(PermissionTableSeeder::class);
        $this->command->warn('Permission data seeded.');

        $this->createUser();
        $this->command->info('User data seeded.');
        $this->command->warn('Username:admin');
        $this->command->warn('Password:admin');
        $this->command->warn('All done :)');

    }

    private function createUser()
    {
        $user = User::create([
            'name' => 'admin',
            'username' => 'admin',
            'email' => 'admin@admin.com',
            'status' => '1',
            'username_verified_at' => now(),
            'email_verified_at' => now(),
            'password' => bcrypt('admin'),
            'remember_token' => Str::random(10),

        ]);

        $role = Role::create(['name' => 'Superuser']);
        $permissions = Permission::pluck('id','id')->all();
        $role->syncPermissions($permissions);
        $user->assignRole([$role->id]);
    }

}
