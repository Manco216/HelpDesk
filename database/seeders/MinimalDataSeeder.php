<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class MinimalDataSeeder extends Seeder
{
    public function run(): void
    {
        $prefix = env('DB_PREFIX', '');
        $t = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };
        $now = now();

        if (!Schema::hasTable($t('tbl_proceso'))) return;
        if (!Schema::hasTable($t('tbl_departamento'))) return;
        if (!Schema::hasTable($t('tbl_categoria'))) return;

        $roleForUsers = null;
        if (Schema::hasTable($t('tbl_rol')) && Schema::hasColumn($t('tbl_rol'), 'nombre')) {
            $gest = DB::table($t('tbl_rol'))->where('nombre', 'gestor')->first();
            $usu = DB::table($t('tbl_rol'))->where('nombre', 'usuario')->first();
            if ($gest) $roleForUsers = (int)$gest->id_rol;
            elseif ($usu) $roleForUsers = (int)$usu->id_rol;
        }

        $getProcesoId = function (string $nombre, ?string $descripcion = null) use ($t, $now) {
            $row = DB::table($t('tbl_proceso'))->where('nombre', $nombre)->first();
            if ($row) return (int)$row->id_proceso;
            return (int)DB::table($t('tbl_proceso'))->insertGetId([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        $tiId = $getProcesoId('TI', 'Tecnología de la Información');
        $sgId = $getProcesoId('Servicios Generales', 'Gestión de servicios generales');
        $thId = $getProcesoId('Talento Humano', 'Recursos humanos');

        $getDepartamentoId = function (int $procId, string $nombre, string $codigo) use ($t, $now) {
            $row = DB::table($t('tbl_departamento'))->where('codigo', $codigo)->first();
            if ($row) return (int)$row->id_departamento;
            return (int)DB::table($t('tbl_departamento'))->insertGetId([
                'nombre' => $nombre,
                'codigo' => $codigo,
                'descripcion' => null,
                'fk_id_padre' => null,
                'metadata' => json_encode([]),
                'estado' => 'activo',
                'fk_id_proceso' => $procId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        $depTi = $getDepartamentoId($tiId, 'TI General', 'DEP_TI');
        $depSg = $getDepartamentoId($sgId, 'Servicios Generales', 'DEP_SG');
        $depTh = $getDepartamentoId($thId, 'Talento Humano', 'DEP_TH');

        if (Schema::hasTable($t('tbl_ans'))) {
            $ansRows = [
                ['tiempo' => 8, 'unidad_tiempo' => 'horas', 'descripcion' => 'SLA respuesta 8h'],
                ['tiempo' => 24, 'unidad_tiempo' => 'horas', 'descripcion' => 'SLA respuesta 24h'],
                ['tiempo' => 48, 'unidad_tiempo' => 'horas', 'descripcion' => 'SLA respuesta 48h'],
                ['tiempo' => 72, 'unidad_tiempo' => 'horas', 'descripcion' => 'SLA respuesta 72h'],
            ];
            foreach ($ansRows as $r) {
                DB::table($t('tbl_ans'))->updateOrInsert(
                    ['tiempo' => $r['tiempo'], 'unidad_tiempo' => $r['unidad_tiempo']],
                    ['descripcion' => $r['descripcion'], 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
        if (Schema::hasTable($t('tbl_tarea'))) {
            $taskRows = [
                ['nombre_tarea' => 'Diagnóstico', 'codigo' => 'TASK_DIAG', 'descripcion' => 'Diagnóstico inicial'],
                ['nombre_tarea' => 'Instalación', 'codigo' => 'TASK_INST', 'descripcion' => 'Instalación de software/hardware'],
                ['nombre_tarea' => 'Mantenimiento', 'codigo' => 'TASK_MANT', 'descripcion' => 'Mantenimiento preventivo/correctivo'],
                ['nombre_tarea' => 'Configuración', 'codigo' => 'TASK_CONF', 'descripcion' => 'Configuración de sistemas'],
            ];
            foreach ($taskRows as $r) {
                DB::table($t('tbl_tarea'))->updateOrInsert(
                    ['codigo' => $r['codigo']],
                    ['nombre_tarea' => $r['nombre_tarea'], 'descripcion' => $r['descripcion'], 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        DB::table($t('tbl_categoria'))->upsert([
            [
                'nombre' => 'Incidencias',
                'codigo' => 'CAT_TI_INC',
                'descripcion' => 'Incidencias en TI',
                'sla_respuesta_horas' => 8,
                'sla_resolucion_horas' => 24,
                'fk_id_departamento' => $depTi,
                'fk_id_responsable' => null,
                'configuracion' => json_encode([]),
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'Accesos',
                'codigo' => 'CAT_TI_ACC',
                'descripcion' => 'Gestión de accesos',
                'sla_respuesta_horas' => 8,
                'sla_resolucion_horas' => 24,
                'fk_id_departamento' => $depTi,
                'fk_id_responsable' => null,
                'configuracion' => json_encode([]),
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'Mantenimientos',
                'codigo' => 'CAT_SG_MNT',
                'descripcion' => 'Mantenimientos generales',
                'sla_respuesta_horas' => 24,
                'sla_resolucion_horas' => 72,
                'fk_id_departamento' => $depSg,
                'fk_id_responsable' => null,
                'configuracion' => json_encode([]),
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'Suministros',
                'codigo' => 'CAT_SG_SUM',
                'descripcion' => 'Solicitudes de suministros',
                'sla_respuesta_horas' => 24,
                'sla_resolucion_horas' => 72,
                'fk_id_departamento' => $depSg,
                'fk_id_responsable' => null,
                'configuracion' => json_encode([]),
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'Nómina',
                'codigo' => 'CAT_TH_NOM',
                'descripcion' => 'Gestiones de nómina',
                'sla_respuesta_horas' => 24,
                'sla_resolucion_horas' => 72,
                'fk_id_departamento' => $depTh,
                'fk_id_responsable' => null,
                'configuracion' => json_encode([]),
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nombre' => 'Contratación',
                'codigo' => 'CAT_TH_CON',
                'descripcion' => 'Procesos de contratación',
                'sla_respuesta_horas' => 24,
                'sla_resolucion_horas' => 72,
                'fk_id_departamento' => $depTh,
                'fk_id_responsable' => null,
                'configuracion' => json_encode([]),
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['codigo'], ['nombre', 'descripcion', 'sla_respuesta_horas', 'sla_resolucion_horas', 'fk_id_departamento', 'fk_id_responsable', 'configuracion', 'estado', 'updated_at']);
        
        if ($roleForUsers !== null && Schema::hasTable($t('tbl_usuario'))) {
            $hasName = Schema::hasColumn($t('tbl_usuario'), 'nombre');
            $hasEmail = Schema::hasColumn($t('tbl_usuario'), 'email');
            $hasDept = Schema::hasColumn($t('tbl_usuario'), 'fk_id_departamento');
            $hasRol = Schema::hasColumn($t('tbl_usuario'), 'fk_id_rol');
            if ($hasName && $hasEmail) {
                $u1 = DB::table($t('tbl_usuario'))->where('id_usuario', 1)->first();
                if (!$u1) {
                    DB::table($t('tbl_usuario'))->insert(array_filter([
                        'id_usuario' => 1,
                        'nombre' => 'Usuario 1',
                        'email' => 'user1@local.test',
                        'password' => Hash::make('password'),
                        'fk_id_departamento' => $hasDept ? $depTi : null,
                        'fk_id_rol' => $hasRol ? $roleForUsers : null,
                        'estado' => 'activo',
                        'created_at' => Schema::hasColumn($t('tbl_usuario'), 'created_at') ? $now : null,
                        'updated_at' => Schema::hasColumn($t('tbl_usuario'), 'updated_at') ? $now : null,
                    ], function ($v) { return $v !== null; }));
                }
                $u2 = DB::table($t('tbl_usuario'))->where('id_usuario', 2)->first();
                if (!$u2) {
                    DB::table($t('tbl_usuario'))->insert(array_filter([
                        'id_usuario' => 2,
                        'nombre' => 'Usuario 2',
                        'email' => 'user2@local.test',
                        'password' => Hash::make('password'),
                        'fk_id_departamento' => $hasDept ? $depTi : null,
                        'fk_id_rol' => $hasRol ? $roleForUsers : null,
                        'estado' => 'activo',
                        'created_at' => Schema::hasColumn($t('tbl_usuario'), 'created_at') ? $now : null,
                        'updated_at' => Schema::hasColumn($t('tbl_usuario'), 'updated_at') ? $now : null,
                    ], function ($v) { return $v !== null; }));
                }
            }
        }
        
        if (Schema::hasColumn($t('tbl_categoria'), 'fk_id_responsable')) {
            DB::table($t('tbl_categoria'))
                ->whereIn('codigo', ['CAT_TI_INC','CAT_TI_ACC','CAT_SG_MNT','CAT_SG_SUM'])
                ->update(['fk_id_responsable' => 1, 'updated_at' => $now]);
            DB::table($t('tbl_categoria'))
                ->whereIn('codigo', ['CAT_TH_NOM','CAT_TH_CON'])
                ->update(['fk_id_responsable' => 2, 'updated_at' => $now]);
        }
    }
}
