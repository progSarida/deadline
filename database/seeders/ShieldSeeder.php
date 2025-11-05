<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["view_role","view_any_role","create_role","update_role","delete_role","delete_any_role","view_city","view_any_city","create_city","update_city","restore_city","restore_any_city","replicate_city","reorder_city","delete_city","delete_any_city","force_delete_city","force_delete_any_city","view_deadline","view_any_deadline","create_deadline","update_deadline","restore_deadline","restore_any_deadline","replicate_deadline","reorder_deadline","delete_deadline","delete_any_deadline","force_delete_deadline","force_delete_any_deadline","view_province","view_any_province","create_province","update_province","restore_province","restore_any_province","replicate_province","reorder_province","delete_province","delete_any_province","force_delete_province","force_delete_any_province","view_region","view_any_region","create_region","update_region","restore_region","restore_any_region","replicate_region","reorder_region","delete_region","delete_any_region","force_delete_region","force_delete_any_region","view_scope::type","view_any_scope::type","create_scope::type","update_scope::type","restore_scope::type","restore_any_scope::type","replicate_scope::type","reorder_scope::type","delete_scope::type","delete_any_scope::type","force_delete_scope::type","force_delete_any_scope::type","view_state","view_any_state","create_state","update_state","restore_state","restore_any_state","replicate_state","reorder_state","delete_state","delete_any_state","force_delete_state","force_delete_any_state","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","replicate_user","reorder_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user"]}]';
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
