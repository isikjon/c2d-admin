<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            // Organizations
            'organizations.view',
            'organizations.create',
            'organizations.edit',
            'organizations.delete',
            
            // Users
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.block',
            
            // Clients
            'clients.view',
            'clients.create',
            'clients.edit',
            'clients.delete',
            'clients.import',
            'clients.export',
            
            // Campaigns
            'campaigns.view',
            'campaigns.create',
            'campaigns.edit',
            'campaigns.delete',
            'campaigns.launch',
            
            // Mailings
            'mailings.view',
            'mailings.create',
            'mailings.edit',
            'mailings.delete',
            
            // Dialogs
            'dialogs.view',
            'dialogs.manage',
            
            // Statistics
            'statistics.view',
            'statistics.export',
            
            // Settings
            'settings.view',
            'settings.edit',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Roles
        // Главный администратор - полный доступ
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Админ ЛК - управление внутри организации
        $adminLk = Role::create(['name' => 'admin_lk']);
        $adminLk->givePermissionTo([
            'users.view', 'users.create', 'users.edit',
            'clients.view', 'clients.create', 'clients.edit', 'clients.delete', 'clients.import', 'clients.export',
            'campaigns.view', 'campaigns.create', 'campaigns.edit', 'campaigns.delete', 'campaigns.launch',
            'mailings.view', 'mailings.create', 'mailings.edit', 'mailings.delete',
            'dialogs.view', 'dialogs.manage',
            'statistics.view', 'statistics.export',
        ]);

        // Пользователь ЛК - базовый функционал
        $userLk = Role::create(['name' => 'user_lk']);
        $userLk->givePermissionTo([
            'clients.view', 'clients.create', 'clients.edit', 'clients.import',
            'campaigns.view', 'campaigns.create', 'campaigns.edit', 'campaigns.launch',
            'mailings.view', 'mailings.create', 'mailings.edit',
            'dialogs.view',
            'statistics.view',
        ]);

        // Саппорт - просмотр всего, без управления доступами
        $support = Role::create(['name' => 'support']);
        $support->givePermissionTo([
            'organizations.view',
            'users.view',
            'clients.view', 'clients.export',
            'campaigns.view',
            'mailings.view',
            'dialogs.view', 'dialogs.manage',
            'statistics.view', 'statistics.export',
        ]);
    }
}
