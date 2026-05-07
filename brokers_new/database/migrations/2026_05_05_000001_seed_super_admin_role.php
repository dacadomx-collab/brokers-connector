<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedSuperAdminRole extends Migration
{
    public function up()
    {
        // Insertar solo si no existe — idempotente
        $exists = DB::table('roles')->where('name', 'super_admin')->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name'         => 'super_admin',
                'display_name' => 'Super Administrador',
                'guard_name'   => 'web',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    public function down()
    {
        // Revocar el rol de todos los usuarios antes de eliminar
        $role = DB::table('roles')->where('name', 'super_admin')->first();

        if ($role) {
            DB::table('model_has_roles')->where('role_id', $role->id)->delete();
            DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
            DB::table('roles')->where('id', $role->id)->delete();
        }
    }
}
