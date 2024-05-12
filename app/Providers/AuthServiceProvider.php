<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        $this->defineRolesAndPermissions();

        //
    }

    private function defineRolesAndPermissions()
    {
        // Define roles
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'employee']);
        Role::firstOrCreate(['name' => 'hod']);
        Role::firstOrCreate(['name' => 'ps']);
        Role::firstOrCreate(['name' => 'hrmd']);

        // Define permissions
        Permission::firstOrCreate(['name' => 'create_post']);
        Permission::firstOrCreate(['name' => 'edit_post']);
        Permission::firstOrCreate(['name' => 'delete_post']);
    }
}
