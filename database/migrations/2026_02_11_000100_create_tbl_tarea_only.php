<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = env('DB_PREFIX', '');
        $tname = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_')
                ? preg_replace('/^tbl_/i', $prefix, $nm)
                : $nm;
        };
        $tableName = $tname('tbl_tarea');
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->increments('id_tarea');
                $table->string('nombre_tarea', 180);
                $table->string('codigo', 50)->nullable()->unique();
                $table->text('descripcion')->nullable();
                $table->json('metricas')->nullable();
                $table->integer('orden')->default(0);
                $table->string('color', 20)->nullable();
                $table->enum('estado', ['activo', 'inactivo'])->default('activo');
                $table->timestamps();
                $table->softDeletes();
                $table->index('nombre_tarea');
                $table->index('codigo');
                $table->index('estado');
                $table->index('orden');
            });
        }
    }

    public function down(): void
    {
        $prefix = env('DB_PREFIX', '');
        $tname = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_')
                ? preg_replace('/^tbl_/i', $prefix, $nm)
                : $nm;
        };
        Schema::dropIfExists($tname('tbl_tarea'));
    }
};
