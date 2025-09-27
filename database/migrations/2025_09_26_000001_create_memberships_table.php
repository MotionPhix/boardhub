<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Role within this specific tenant
            $table->string('role')->default('member'); // owner, admin, manager, member, viewer

            // Status of membership
            $table->enum('status', ['active', 'inactive', 'pending', 'suspended'])->default('active');

            // Permissions specific to this tenant
            $table->json('permissions')->nullable();

            // Invitation details
            $table->string('invited_by_user_id')->nullable();
            $table->string('invitation_token')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();

            // Access control
            $table->timestamp('last_accessed_at')->nullable();
            $table->json('access_restrictions')->nullable(); // IP restrictions, time-based access, etc.

            $table->timestamps();

            // Ensure unique membership per user-tenant combination
            $table->unique(['user_id', 'tenant_id']);

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['tenant_id', 'role']);
            $table->index(['tenant_id', 'status']);
            $table->index('invitation_token');
            $table->index('last_accessed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};