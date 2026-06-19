<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('custom_forms', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_forms', 'panel_access')) {
                $table->json('panel_access')->nullable()->after('allowed_roles');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_forms', function (Blueprint $table) {
            if (Schema::hasColumn('custom_forms', 'panel_access')) {
                $table->dropColumn('panel_access');
            }
        });
    }
};
