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
            // unique：讓資料庫成為「一個時段只能被預約一次」的最後一道防線，
            // 即使兩個請求同時通過應用層檢查，第二筆也會被擋在 DB。
            $table->foreignId('available_slot_id')
                ->after('service_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['available_slot_id']);
            $table->dropUnique(['available_slot_id']);
            $table->dropColumn('available_slot_id');
        });
    }
};
