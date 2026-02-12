<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $prefix = env('DB_PREFIX', '');
        $t = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };
        $proceso = $t('tbl_proceso');
        $categoria = $t('tbl_categoria');
        $departamento = $t('tbl_departamento');

        foreach ([$proceso, $categoria, $departamento] as $tb) {
            if (!Schema::hasTable($tb)) {
                return;
            }
        }

        Schema::disableForeignKeyConstraints();

        Schema::table($proceso, function (Blueprint $table) use ($departamento) {
            if (!Schema::hasColumn($table->getTable(), 'fk_id_departamento')) {
                $table->unsignedInteger('fk_id_departamento')->nullable()->after('id_proceso');
                $table->index('fk_id_departamento');
            }
        });

        Schema::table($categoria, function (Blueprint $table) use ($proceso) {
            if (!Schema::hasColumn($table->getTable(), 'fk_id_proceso')) {
                $table->unsignedInteger('fk_id_proceso')->nullable()->after('id_categoria');
                $table->index('fk_id_proceso');
            }
        });

        if (Schema::hasColumn($departamento, 'fk_id_proceso')) {
            $departamentos = DB::table($departamento)->select('id_departamento', 'fk_id_proceso')->get();
            $mapProcToDept = [];
            foreach ($departamentos as $d) {
                if ($d->fk_id_proceso !== null) {
                    $pid = (int)$d->fk_id_proceso;
                    $did = (int)$d->id_departamento;
                    if (!isset($mapProcToDept[$pid]) || $did < $mapProcToDept[$pid]) {
                        $mapProcToDept[$pid] = $did;
                    }
                }
            }
            if (Schema::hasColumn($proceso, 'fk_id_departamento')) {
                $procs = DB::table($proceso)->select('id_proceso')->get();
                foreach ($procs as $p) {
                    $pid = (int)$p->id_proceso;
                    $did = $mapProcToDept[$pid] ?? null;
                    if ($did !== null) {
                        DB::table($proceso)->where('id_proceso', $pid)->update(['fk_id_departamento' => $did]);
                    }
                }
            }
            if (Schema::hasColumn($categoria, 'fk_id_departamento') && Schema::hasColumn($categoria, 'fk_id_proceso')) {
                $cats = DB::table($categoria)->select('id_categoria', 'fk_id_departamento')->get();
                foreach ($cats as $c) {
                    $did = $c->fk_id_departamento !== null ? (int)$c->fk_id_departamento : null;
                    $pid = null;
                    if ($did !== null) {
                        $depRow = DB::table($departamento)->where('id_departamento', $did)->select('fk_id_proceso')->first();
                        $pid = $depRow && $depRow->fk_id_proceso !== null ? (int)$depRow->fk_id_proceso : null;
                    }
                    if ($pid !== null) {
                        DB::table($categoria)->where('id_categoria', (int)$c->id_categoria)->update(['fk_id_proceso' => $pid]);
                    }
                }
            }
        }

        Schema::table($proceso, function (Blueprint $table) use ($departamento) {
            if (Schema::hasColumn($table->getTable(), 'fk_id_departamento')) {
                $table->foreign('fk_id_departamento')->references('id_departamento')->on($departamento)->onDelete('restrict');
            }
        });
        Schema::table($categoria, function (Blueprint $table) use ($proceso) {
            if (Schema::hasColumn($table->getTable(), 'fk_id_proceso')) {
                $table->foreign('fk_id_proceso')->references('id_proceso')->on($proceso)->onDelete('restrict');
            }
        });

        Schema::table($departamento, function (Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'fk_id_proceso')) {
                try { $table->dropForeign(['fk_id_proceso']); } catch (\Throwable $e) {}
                try { $table->dropIndex(['fk_id_proceso']); } catch (\Throwable $e) {}
            }
        });
        Schema::table($categoria, function (Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'fk_id_departamento')) {
                try { $table->dropForeign(['fk_id_departamento']); } catch (\Throwable $e) {}
                try { $table->dropIndex(['fk_id_departamento']); } catch (\Throwable $e) {}
            }
        });
        Schema::table($departamento, function (Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'fk_id_proceso')) {
                $table->dropColumn('fk_id_proceso');
            }
        });
        Schema::table($categoria, function (Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'fk_id_departamento')) {
                $table->dropColumn('fk_id_departamento');
            }
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        $prefix = env('DB_PREFIX', '');
        $t = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };
        $proceso = $t('tbl_proceso');
        $categoria = $t('tbl_categoria');
        $departamento = $t('tbl_departamento');

        foreach ([$proceso, $categoria, $departamento] as $tb) {
            if (!Schema::hasTable($tb)) {
                return;
            }
        }

        Schema::disableForeignKeyConstraints();

        Schema::table($departamento, function (Blueprint $table) use ($proceso) {
            if (!Schema::hasColumn($table->getTable(), 'fk_id_proceso')) {
                $table->unsignedInteger('fk_id_proceso')->nullable()->after('id_departamento');
                $table->index('fk_id_proceso');
                $table->foreign('fk_id_proceso')->references('id_proceso')->on($proceso)->onDelete('restrict');
            }
        });
        Schema::table($categoria, function (Blueprint $table) use ($departamento) {
            if (!Schema::hasColumn($table->getTable(), 'fk_id_departamento')) {
                $table->unsignedInteger('fk_id_departamento')->nullable()->after('id_categoria');
                $table->index('fk_id_departamento');
                $table->foreign('fk_id_departamento')->references('id_departamento')->on($departamento)->onDelete('restrict');
            }
        });

        if (Schema::hasColumn($proceso, 'fk_id_departamento')) {
            $procs = DB::table($proceso)->select('id_proceso', 'fk_id_departamento')->get();
            foreach ($procs as $p) {
                $pid = (int)$p->id_proceso;
                $did = $p->fk_id_departamento !== null ? (int)$p->fk_id_departamento : null;
                if ($did !== null) {
                    DB::table($departamento)->where('id_departamento', $did)->update(['fk_id_proceso' => $pid]);
                }
            }
        }
        if (Schema::hasColumn($categoria, 'fk_id_proceso') && Schema::hasColumn($categoria, 'fk_id_departamento')) {
            $cats = DB::table($categoria)->select('id_categoria', 'fk_id_proceso')->get();
            foreach ($cats as $c) {
                $pid = $c->fk_id_proceso !== null ? (int)$c->fk_id_proceso : null;
                if ($pid !== null) {
                    $depRow = DB::table($departamento)->where('fk_id_proceso', $pid)->orderBy('id_departamento', 'asc')->first();
                    if ($depRow) {
                        DB::table($categoria)->where('id_categoria', (int)$c->id_categoria)->update(['fk_id_departamento' => (int)$depRow->id_departamento]);
                    }
                }
            }
        }

        Schema::table($proceso, function (Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'fk_id_departamento')) {
                try { $table->dropForeign(['fk_id_departamento']); } catch (\Throwable $e) {}
                $table->dropColumn('fk_id_departamento');
            }
        });
        Schema::table($categoria, function (Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'fk_id_proceso')) {
                try { $table->dropForeign(['fk_id_proceso']); } catch (\Throwable $e) {}
                $table->dropColumn('fk_id_proceso');
            }
        });

        Schema::enableForeignKeyConstraints();
    }
};
