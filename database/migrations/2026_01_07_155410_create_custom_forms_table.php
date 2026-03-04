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
        if (!Schema::hasTable('custom_forms')) {
            Schema::create('custom_forms', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->json('schema')->nullable(); // The component definition
                $table->json('accounting_config')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('allowed_roles')->nullable();

                // Workflow columns
                $table->boolean('enable_workflow')->default(false);
                $table->json('reviewer_roles')->nullable();
                $table->json('approver_roles')->nullable();
                $table->json('reviewer_users')->nullable(); // Array of User IDs
                $table->json('approver_users')->nullable(); // Array of User IDs

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_forms');
    }
};
