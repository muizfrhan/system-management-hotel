<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->index(['room_id', 'status', 'check_in_date', 'check_out_date'], 'reservations_inventory_lookup_index');
            $table->index(['status', 'check_in_date'], 'reservations_status_check_in_index');
            $table->index(['created_at', 'status'], 'reservations_created_status_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['reservation_id', 'status'], 'payments_settlement_lookup_index');
            $table->index(['status', 'paid_at'], 'payments_reporting_index');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->index(['room_type_id', 'status'], 'rooms_type_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('rooms_type_status_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_reporting_index');
            $table->dropIndex('payments_settlement_lookup_index');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_created_status_index');
            $table->dropIndex('reservations_status_check_in_index');
            $table->dropIndex('reservations_inventory_lookup_index');
        });
    }
};
