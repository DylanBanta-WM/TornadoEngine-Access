<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roles an import (Access-Google, Access-AD, …) gave, so each only takes back its own,
        // and roles given by hand are never taken back.
        Schema::create('access_imported_roles', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('access_roles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('source', 40);
            $table->primary(['role_id', 'user_id', 'source']);
        });

        // People every import leaves out.
        Schema::create('access_exclusions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('reason');
            $table->foreignId('excluded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_exclusions');
        Schema::dropIfExists('access_imported_roles');
    }
};
