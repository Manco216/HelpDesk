<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = env('DB_PREFIX', '');
        $tname = function (string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };
        $table = $tname('tbl_categoria');
        if (!Schema::hasTable($table)) {
            return;
        }
        Schema::table($table, function (Blueprint $tbl) use ($table) {
            if (!Schema::hasColumn($table, 'fk_id_ans')) {
                $tbl->unsignedInteger('fk_id_ans')->nullable();
                $tbl->index('fk_id_ans');
            }
            if (!Schema::hasColumn($table, 'fk_id_tarea')) {
                $tbl->unsignedInteger('fk_id_tarea')->nullable();
                $tbl->index('fk_id_tarea');
            }
        });
    }

    public function down(): void
    {
        $prefix = env('DB_PREFIX', '');
        $tname = function (string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };
        $table = $tname('tbl_categoria');
        if (!Schema::hasTable($table)) {
            return;
        }
        Schema::table($table, function (Blueprint $tbl) use ($table) {
            if (Schema::hasColumn($table, 'fk_id_ans')) {
                $tbl->dropColumn('fk_id_ans');
            }
            if (Schema::hasColumn($table, 'fk_id_tarea')) {
                $tbl->dropColumn('fk_id_tarea');
            }
        });
    }
};
