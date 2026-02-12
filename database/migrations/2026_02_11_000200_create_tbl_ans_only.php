<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $prefix = env('DB_PREFIX', '');
        $tname = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };
        $table = $tname('tbl_ans');
        if (!Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $table) {
                $table->increments('id_ans');
                $table->integer('tiempo')->default(0);
                $table->string('unidad_tiempo', 20);
                $table->string('descripcion', 200)->nullable();
                $table->enum('estado', ['activo', 'inactivo'])->default('activo');
                $table->timestamps();
                $table->softDeletes();
                $table->index('unidad_tiempo');
                $table->index('estado');
            });
        }
    }

    public function down(): void
    {
        $prefix = env('DB_PREFIX', '');
        $tname = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };
        $table = $tname('tbl_ans');
        if (Schema::hasTable($table)) {
            Schema::dropIfExists($table);
        }
    }
};
