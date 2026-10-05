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
        Schema::table('appointments', function (Blueprint $table) {
            // 為 available_slot_id 補上一般索引，避免移除 unique 索引時影響 foreign key 約束
            $table->index('available_slot_id');
            $table->dropUnique('appointments_available_slot_id_unique');

            // 條件式唯一：只有未取消的預約才會有 active_slot_id。
            // 已取消的預約其值為 NULL。在 MySQL 中，多筆 NULL 不會違反 UNIQUE 限制，
            // 如此一來時段被釋出後，才能由新的預約再次使用。
            $table->unsignedBigInteger('active_slot_id')
                ->nullable()
                ->storedAs("CASE WHEN status != 'cancelled' THEN available_slot_id ELSE NULL END")
                ->after('available_slot_id');

            $table->unique('active_slot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['active_slot_id']);
            $table->dropColumn('active_slot_id');

            $table->unique('available_slot_id');
            $table->dropIndex(['available_slot_id']);
        });
    }
};
