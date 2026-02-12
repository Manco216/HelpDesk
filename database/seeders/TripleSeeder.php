<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class TripleSeeder extends Seeder
{
    public function run(): void
    {
        $prefix = env('DB_PREFIX', '');
        $t = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };
        $now = now();

        $ids = [
            'roles' => [],
            'procesos' => [],
            'departamentos' => [],
            'usuarios' => [],
            'ans' => [],
            'tareas' => [],
            'estados' => [],
            'prioridades' => [],
            'categorias' => [],
            'tickets' => [],
        ];

        if (Schema::hasTable($t('tbl_rol'))) {
            $ids['roles'][] = DB::table($t('tbl_rol'))->insertGetId(['nombre' => 'usuario', 'descripcion' => 'Usuario', 'permisos' => json_encode([]), 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['roles'][] = DB::table($t('tbl_rol'))->insertGetId(['nombre' => 'gestor', 'descripcion' => 'Gestor', 'permisos' => json_encode([]), 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['roles'][] = DB::table($t('tbl_rol'))->insertGetId(['nombre' => 'admin', 'descripcion' => 'Administrador', 'permisos' => json_encode([]), 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
        }

        if (Schema::hasTable($t('tbl_proceso'))) {
            $ids['procesos'][] = DB::table($t('tbl_proceso'))->insertGetId(['nombre' => 'Proceso 1', 'descripcion' => 'Demo 1', 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['procesos'][] = DB::table($t('tbl_proceso'))->insertGetId(['nombre' => 'Proceso 2', 'descripcion' => 'Demo 2', 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['procesos'][] = DB::table($t('tbl_proceso'))->insertGetId(['nombre' => 'Proceso 3', 'descripcion' => 'Demo 3', 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
        }

        if (Schema::hasTable($t('tbl_departamento'))) {
            $p1 = $ids['procesos'][0] ?? null;
            $p2 = $ids['procesos'][1] ?? null;
            $p3 = $ids['procesos'][2] ?? null;
            $ids['departamentos'][] = DB::table($t('tbl_departamento'))->insertGetId(['nombre' => 'Departamento 1', 'codigo' => 'DEP_1', 'descripcion' => 'Demo', 'fk_id_proceso' => $p1, 'fk_id_padre' => null, 'metadata' => json_encode([]), 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['departamentos'][] = DB::table($t('tbl_departamento'))->insertGetId(['nombre' => 'Departamento 2', 'codigo' => 'DEP_2', 'descripcion' => 'Demo', 'fk_id_proceso' => $p2, 'fk_id_padre' => null, 'metadata' => json_encode([]), 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['departamentos'][] = DB::table($t('tbl_departamento'))->insertGetId(['nombre' => 'Departamento 3', 'codigo' => 'DEP_3', 'descripcion' => 'Demo', 'fk_id_proceso' => $p3, 'fk_id_padre' => null, 'metadata' => json_encode([]), 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
        }

        if (Schema::hasTable($t('tbl_usuario'))) {
            $d1 = $ids['departamentos'][0] ?? null;
            $d2 = $ids['departamentos'][1] ?? null;
            $d3 = $ids['departamentos'][2] ?? null;
            $r1 = $ids['roles'][0] ?? null;
            $r2 = $ids['roles'][1] ?? null;
            $r3 = $ids['roles'][2] ?? null;
            $ids['usuarios'][] = DB::table($t('tbl_usuario'))->insertGetId(['nombre' => 'Usuario 1', 'email' => 'user1@local.test', 'password' => Hash::make('password'), 'remember_token' => null, 'firebase_uid' => null, 'email_verified_at' => null, 'telefono' => null, 'avatar' => null, 'fk_id_departamento' => $d1, 'fk_id_rol' => $r1, 'preferencias' => json_encode([]), 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['usuarios'][] = DB::table($t('tbl_usuario'))->insertGetId(['nombre' => 'Usuario 2', 'email' => 'user2@local.test', 'password' => Hash::make('password'), 'remember_token' => null, 'firebase_uid' => null, 'email_verified_at' => null, 'telefono' => null, 'avatar' => null, 'fk_id_departamento' => $d2, 'fk_id_rol' => $r2, 'preferencias' => json_encode([]), 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['usuarios'][] = DB::table($t('tbl_usuario'))->insertGetId(['nombre' => 'Usuario 3', 'email' => 'user3@local.test', 'password' => Hash::make('password'), 'remember_token' => null, 'firebase_uid' => null, 'email_verified_at' => null, 'telefono' => null, 'avatar' => null, 'fk_id_departamento' => $d3, 'fk_id_rol' => $r3, 'preferencias' => json_encode([]), 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
        }

        if (Schema::hasTable($t('tbl_ans'))) {
            $ids['ans'][] = DB::table($t('tbl_ans'))->insertGetId(['tiempo' => 8, 'unidad_tiempo' => 'horas', 'descripcion' => 'SLA 8h', 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['ans'][] = DB::table($t('tbl_ans'))->insertGetId(['tiempo' => 24, 'unidad_tiempo' => 'horas', 'descripcion' => 'SLA 24h', 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['ans'][] = DB::table($t('tbl_ans'))->insertGetId(['tiempo' => 72, 'unidad_tiempo' => 'horas', 'descripcion' => 'SLA 72h', 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
        }

        if (Schema::hasTable($t('tbl_tarea'))) {
            $ids['tareas'][] = DB::table($t('tbl_tarea'))->insertGetId(['nombre_tarea' => 'Diagnóstico', 'codigo' => 'TASK_DIAG', 'descripcion' => 'Diagnóstico inicial', 'metricas' => json_encode([]), 'orden' => 1, 'color' => null, 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['tareas'][] = DB::table($t('tbl_tarea'))->insertGetId(['nombre_tarea' => 'Instalación', 'codigo' => 'TASK_INST', 'descripcion' => 'Instalación de software', 'metricas' => json_encode([]), 'orden' => 2, 'color' => null, 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['tareas'][] = DB::table($t('tbl_tarea'))->insertGetId(['nombre_tarea' => 'Mantenimiento', 'codigo' => 'TASK_MANT', 'descripcion' => 'Mantenimiento preventivo', 'metricas' => json_encode([]), 'orden' => 3, 'color' => null, 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
        }

        if (Schema::hasTable($t('tbl_estado'))) {
            $ids['estados'][] = DB::table($t('tbl_estado'))->insertGetId(['nombre' => 'Abierto', 'color' => 'green', 'orden' => 1, 'es_inicial' => true, 'es_final' => false, 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['estados'][] = DB::table($t('tbl_estado'))->insertGetId(['nombre' => 'En Progreso', 'color' => 'orange', 'orden' => 2, 'es_inicial' => false, 'es_final' => false, 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['estados'][] = DB::table($t('tbl_estado'))->insertGetId(['nombre' => 'Cerrado', 'color' => 'gray', 'orden' => 3, 'es_inicial' => false, 'es_final' => true, 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
        }

        if (Schema::hasTable($t('tbl_prioridad'))) {
            $ids['prioridades'][] = DB::table($t('tbl_prioridad'))->insertGetId(['nombre' => 'Baja', 'orden' => 1, 'color' => 'blue', 'sla_horas' => 72, 'sla_resolucion_horas' => 120, 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['prioridades'][] = DB::table($t('tbl_prioridad'))->insertGetId(['nombre' => 'Media', 'orden' => 2, 'color' => 'yellow', 'sla_horas' => 48, 'sla_resolucion_horas' => 96, 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
            $ids['prioridades'][] = DB::table($t('tbl_prioridad'))->insertGetId(['nombre' => 'Alta', 'orden' => 3, 'color' => 'red', 'sla_horas' => 24, 'sla_resolucion_horas' => 48, 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now]);
        }

        if (Schema::hasTable($t('tbl_categoria'))) {
            $d1 = $ids['departamentos'][0] ?? null;
            $d2 = $ids['departamentos'][1] ?? null;
            $d3 = $ids['departamentos'][2] ?? null;
            $u1 = $ids['usuarios'][0] ?? null;
            $ans1 = $ids['ans'][0] ?? null;
            $ans2 = $ids['ans'][1] ?? null;
            $ans3 = $ids['ans'][2] ?? null;
            $task1 = $ids['tareas'][0] ?? null;
            $task2 = $ids['tareas'][1] ?? null;
            $task3 = $ids['tareas'][2] ?? null;
            $ids['categorias'][] = DB::table($t('tbl_categoria'))->insertGetId(array_filter(['nombre' => 'Categoría 1', 'codigo' => 'CAT_1', 'descripcion' => 'Demo', 'sla_respuesta_horas' => 8, 'sla_resolucion_horas' => 24, 'fk_id_departamento' => $d1, 'fk_id_responsable' => $u1, 'fk_id_ans' => Schema::hasColumn($t('tbl_categoria'),'fk_id_ans') ? $ans1 : null, 'fk_id_tarea' => Schema::hasColumn($t('tbl_categoria'),'fk_id_tarea') ? $task1 : null, 'configuracion' => json_encode([]), 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now], function ($v){ return $v !== null; }));
            $ids['categorias'][] = DB::table($t('tbl_categoria'))->insertGetId(array_filter(['nombre' => 'Categoría 2', 'codigo' => 'CAT_2', 'descripcion' => 'Demo', 'sla_respuesta_horas' => 8, 'sla_resolucion_horas' => 24, 'fk_id_departamento' => $d2, 'fk_id_responsable' => $u1, 'fk_id_ans' => Schema::hasColumn($t('tbl_categoria'),'fk_id_ans') ? $ans2 : null, 'fk_id_tarea' => Schema::hasColumn($t('tbl_categoria'),'fk_id_tarea') ? $task2 : null, 'configuracion' => json_encode([]), 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now], function ($v){ return $v !== null; }));
            $ids['categorias'][] = DB::table($t('tbl_categoria'))->insertGetId(array_filter(['nombre' => 'Categoría 3', 'codigo' => 'CAT_3', 'descripcion' => 'Demo', 'sla_respuesta_horas' => 24, 'sla_resolucion_horas' => 72, 'fk_id_departamento' => $d3, 'fk_id_responsable' => $u1, 'fk_id_ans' => Schema::hasColumn($t('tbl_categoria'),'fk_id_ans') ? $ans3 : null, 'fk_id_tarea' => Schema::hasColumn($t('tbl_categoria'),'fk_id_tarea') ? $task3 : null, 'configuracion' => json_encode([]), 'estado' => 'activo', 'created_at' => $now, 'updated_at' => $now], function ($v){ return $v !== null; }));
        }

        if (Schema::hasTable($t('tbl_tickets'))) {
            $c1 = $ids['categorias'][0] ?? null;
            $c2 = $ids['categorias'][1] ?? null;
            $c3 = $ids['categorias'][2] ?? null;
            $p1 = $ids['prioridades'][0] ?? null;
            $p2 = $ids['prioridades'][1] ?? null;
            $p3 = $ids['prioridades'][2] ?? null;
            $e1 = $ids['estados'][0] ?? null;
            $e2 = $ids['estados'][1] ?? null;
            $e3 = $ids['estados'][2] ?? null;
            $u1 = $ids['usuarios'][0] ?? null;
            $u2 = $ids['usuarios'][1] ?? null;
            $ids['tickets'][] = DB::table($t('tbl_tickets'))->insertGetId(array_filter(['codigo' => 'TCK-0001', 'asunto' => 'Ticket 1', 'descripcion' => 'Demo 1', 'fk_id_categoria' => $c1, 'fk_id_prioridad' => $p1, 'fk_id_estado' => $e1, 'fk_id_usuario_creador' => $u1, 'fk_id_usuario_asignado' => $u2, 'historial' => json_encode([]), 'comentarios' => json_encode([]), 'etiquetas' => json_encode(['demo']), 'metadata' => json_encode([]), 'created_at' => $now, 'updated_at' => $now], function ($v){ return $v !== null; }));
            $ids['tickets'][] = DB::table($t('tbl_tickets'))->insertGetId(array_filter(['codigo' => 'TCK-0002', 'asunto' => 'Ticket 2', 'descripcion' => 'Demo 2', 'fk_id_categoria' => $c2, 'fk_id_prioridad' => $p2, 'fk_id_estado' => $e2, 'fk_id_usuario_creador' => $u1, 'fk_id_usuario_asignado' => $u2, 'historial' => json_encode([]), 'comentarios' => json_encode([]), 'etiquetas' => json_encode(['demo']), 'metadata' => json_encode([]), 'created_at' => $now, 'updated_at' => $now], function ($v){ return $v !== null; }));
            $ids['tickets'][] = DB::table($t('tbl_tickets'))->insertGetId(array_filter(['codigo' => 'TCK-0003', 'asunto' => 'Ticket 3', 'descripcion' => 'Demo 3', 'fk_id_categoria' => $c3, 'fk_id_prioridad' => $p3, 'fk_id_estado' => $e3, 'fk_id_usuario_creador' => $u1, 'fk_id_usuario_asignado' => $u2, 'historial' => json_encode([]), 'comentarios' => json_encode([]), 'etiquetas' => json_encode(['demo']), 'metadata' => json_encode([]), 'created_at' => $now, 'updated_at' => $now], function ($v){ return $v !== null; }));
        }

        if (Schema::hasTable($t('tbl_adjunto'))) {
            $tk1 = $ids['tickets'][0] ?? null;
            $tk2 = $ids['tickets'][1] ?? null;
            $tk3 = $ids['tickets'][2] ?? null;
            $u1 = $ids['usuarios'][0] ?? null;
            DB::table($t('tbl_adjunto'))->insert(['nombre_original' => 'sample1.txt', 'ruta' => 'assets/adjuntos/sample1.txt', 'tipo_mime' => 'text/plain', 'tamano_bytes' => 1234, 'fk_id_ticket' => $tk1, 'fk_id_usuario' => $u1, 'created_at' => $now, 'updated_at' => $now]);
            DB::table($t('tbl_adjunto'))->insert(['nombre_original' => 'sample2.txt', 'ruta' => 'assets/adjuntos/sample2.txt', 'tipo_mime' => 'text/plain', 'tamano_bytes' => 2345, 'fk_id_ticket' => $tk2, 'fk_id_usuario' => $u1, 'created_at' => $now, 'updated_at' => $now]);
            DB::table($t('tbl_adjunto'))->insert(['nombre_original' => 'sample3.txt', 'ruta' => 'assets/adjuntos/sample3.txt', 'tipo_mime' => 'text/plain', 'tamano_bytes' => 3456, 'fk_id_ticket' => $tk3, 'fk_id_usuario' => $u1, 'created_at' => $now, 'updated_at' => $now]);
        }

        if (Schema::hasTable($t('tbl_evaluacion'))) {
            $tk1 = $ids['tickets'][0] ?? null;
            $tk2 = $ids['tickets'][1] ?? null;
            $tk3 = $ids['tickets'][2] ?? null;
            $u2 = $ids['usuarios'][1] ?? null;
            DB::table($t('tbl_evaluacion'))->insert(['fk_id_ticket' => $tk1, 'fk_id_usuario_evaluado' => $u2, 'calificacion' => 5, 'detalles' => json_encode(['tiempo_respuesta'=>5]), 'comentario' => 'Excelente', 'created_at' => $now, 'updated_at' => $now]);
            DB::table($t('tbl_evaluacion'))->insert(['fk_id_ticket' => $tk2, 'fk_id_usuario_evaluado' => $u2, 'calificacion' => 4, 'detalles' => json_encode(['tiempo_respuesta'=>4]), 'comentario' => 'Muy bien', 'created_at' => $now, 'updated_at' => $now]);
            DB::table($t('tbl_evaluacion'))->insert(['fk_id_ticket' => $tk3, 'fk_id_usuario_evaluado' => $u2, 'calificacion' => 3, 'detalles' => json_encode(['tiempo_respuesta'=>3]), 'comentario' => 'Bien', 'created_at' => $now, 'updated_at' => $now]);
        }
    }
}

