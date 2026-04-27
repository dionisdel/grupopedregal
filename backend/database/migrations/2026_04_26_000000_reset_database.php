<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop ALL existing tables from the old schema.
     * This migration runs first to ensure a clean slate for the v2 schema.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Old v1 tables — dependent/child tables first, then parent tables
        // Quote & contact
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('contact_messages');

        // Price & product detail tables
        Schema::dropIfExists('price_history');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('product_codes');
        Schema::dropIfExists('product_costs');
        Schema::dropIfExists('product_specs');
        Schema::dropIfExists('unit_conversions');

        // Shipping
        Schema::dropIfExists('shipping_rates');

        // Core domain tables
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('customer_types');

        // Shipping & logistics nomenclators
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('units');

        // Auth & roles
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('personal_access_tokens');

        // Users & sessions
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');

        // Cache & jobs (Laravel infrastructure)
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');

        // NOTE: Do NOT drop the 'migrations' table here.
        // migrate:fresh already handles it, and dropping it mid-migration
        // causes "Table 'migrations' doesn't exist" errors.

        Schema::enableForeignKeyConstraints();
    }

    /**
     * One-way reset — no rollback.
     */
    public function down(): void
    {
        // Intentionally empty: this is a one-way reset migration.
    }
};
