<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The supplier changed (5sim -> SMSPool): its order ids are alphanumeric strings, and the
     * column is no longer tied to one supplier's name.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['fivesim_order_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('fivesim_order_id', 'provider_order_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('provider_order_id')->change();
            $table->unique('provider_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['provider_order_id']);
            $table->renameColumn('provider_order_id', 'fivesim_order_id');
        });
    }
};
