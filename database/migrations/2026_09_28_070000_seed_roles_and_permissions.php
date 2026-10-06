<?php

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Replaces the flat users.role column with spatie/laravel-permission's
 * dynamic roles. 'Admin' gets every known permission and is the seed for
 * App\Providers\AppServiceProvider's Gate::before bypass — that bypass, not
 * this row, is what actually makes Admin all-powerful, so editing this row
 * later can't lock every admin out. 'Staff' gets what the old hardcoded
 * 'staff' role could do, so existing behaviour doesn't change. Both roles
 * stay ordinary, editable rows after this — only the code-level Admin
 * bypass is special.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(Permissions::ALL) as $key) {
            Permission::firstOrCreate(['name' => $key, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions(array_keys(Permissions::ALL));

        $staff = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);
        $staff->syncPermissions(Permissions::STAFF_DEFAULTS);

        $rows = collect();

        foreach (DB::table('users')->where('role', 'admin')->pluck('id') as $id) {
            $rows->push(['role_id' => $admin->id, 'model_type' => User::class, 'model_id' => $id]);
        }

        foreach (DB::table('users')->where('role', '!=', 'admin')->pluck('id') as $id) {
            $rows->push(['role_id' => $staff->id, 'model_type' => User::class, 'model_id' => $id]);
        }

        if ($rows->isNotEmpty()) {
            DB::table('model_has_roles')->insert($rows->toArray());
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('staff');
        });

        DB::table('users')->update(['role' => 'staff']);

        DB::table('users')->whereIn('id', function ($q) {
            $q->select('model_has_roles.model_id')
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', 'Admin');
        })->update(['role' => 'admin']);

        // Role/permission rows themselves are left in place — by the time
        // this runs there may be custom roles an admin created, and this
        // migration didn't create those, so it doesn't own removing them.
    }
};
