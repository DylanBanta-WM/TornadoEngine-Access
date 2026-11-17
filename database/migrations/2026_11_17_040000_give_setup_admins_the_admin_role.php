<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Setup admins made before 0.8.1 get the Admin role too; its description goes.
    public function up(): void
    {
        $admin = DB::table('access_roles')->where('all_permissions', true)->orderBy('id')->value('id');
        if ($admin) {
            foreach (DB::table('users')->where('is_admin', true)->pluck('id') as $user) {
                DB::table('access_role_user')->insertOrIgnore(['role_id' => $admin, 'user_id' => $user]);
            }
        }
        DB::table('access_roles')->where('name', 'Admin')->where('description', 'Everything.')->update(['description' => null]);
    }

    public function down(): void {}
};
