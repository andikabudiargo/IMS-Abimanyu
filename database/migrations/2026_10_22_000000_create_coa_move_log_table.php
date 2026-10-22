<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for CoaMoveController: one row per table.column touched by a
 * COA-to-COA move, regardless of whether the period of the affected
 * documents is locked (lock checks are intentionally not applied to this
 * operation -- see CoaMoveController@execute).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coa_move_log', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 36);
            $table->string('source_account', 50);
            $table->string('target_account', 50);
            $table->string('table_name', 100);
            $table->string('column_name', 100);
            $table->integer('row_count');
            $table->jsonb('affected_pks')->nullable();
            $table->string('moved_by', 100);
            $table->timestamp('moved_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coa_move_log');
    }
};
