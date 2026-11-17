<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A role can have every permission, including ones modules add later (Admin).
        Schema::table('access_roles', function (Blueprint $table) {
            $table->boolean('all_permissions')->default(false)->after('description');
        });

        // Email pattern rules: give a role to everyone whose address matches.
        Schema::create('access_pattern_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('access_roles')->cascadeOnDelete();
            $table->string('pattern', 500);
            $table->unsignedInteger('matched')->nullable();
            $table->timestamps();
        });

        // Every district starts with these; they can be renamed or removed.
        $existing = array_map('strtolower', DB::table('access_roles')->pluck('name')->all());
        foreach ([['Admin', null, true], ['Staff', null, false], ['Student', null, false]] as [$name, $description, $all]) {
            if (! in_array(strtolower($name), $existing, true)) {
                DB::table('access_roles')->insert(['name' => $name, 'description' => $description, 'all_permissions' => $all, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('access_pattern_rules');
        Schema::table('access_roles', function (Blueprint $table) {
            $table->dropColumn('all_permissions');
        });
    }
};
