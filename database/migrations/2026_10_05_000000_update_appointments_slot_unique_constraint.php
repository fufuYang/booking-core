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
        // 1. 先把帶有 cascadeOnDelete 的外鍵約束與舊的 unique 索引移除
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['available_slot_id']);
            $table->dropUnique('appointments_available_slot_id_unique');
        });

        // 2. 重新建立不帶 cascade 的外鍵（MySQL 規範：Generated Column 所參照的欄位不得包含 ON DELETE CASCADE）
        //    並加入條件式唯一索引
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('available_slot_id')
                ->references('id')
                ->on('available_slots')
                ->restrictOnDelete();

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
            $table->dropForeign(['available_slot_id']);
            $table->dropUnique(['active_slot_id']);
            $table->dropColumn('active_slot_id');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('available_slot_id')
                ->references('id')
                ->on('available_slots')
                ->cascadeOnDelete();

            $table->unique('available_slot_id');
        });
    }
};
