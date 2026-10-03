<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->unsignedInteger('total_qty')->default(1)->after('code');
            $table->unsignedInteger('available_qty')->default(1)->after('total_qty');
        });

        Schema::table('equipment_loans', function (Blueprint $table) {
            $table->unsignedInteger('qty')->default(1)->after('equipment_id');
            $table->text('notes')->nullable()->after('return_condition');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_loans', function (Blueprint $table) {
            $table->dropColumn(['qty', 'notes']);
        });

        Schema::table('equipments', function (Blueprint $table) {
            $table->dropColumn(['total_qty', 'available_qty']);
        });
    }
};
