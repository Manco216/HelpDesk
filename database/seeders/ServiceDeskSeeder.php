<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ServiceDeskSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $prefix = env('DB_PREFIX', '');
        $t = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };

        $procesoId = DB::table($t('tbl_proceso'))->insertGetId([
            'nombre_proceso' => 'Mesa de ayuda TI',
            'fase_proceso' => true,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);

        $ansIds = [];
        $ansIds['critico'] = DB::table($t('tbl_ans'))->insertGetId([
            'medida' => 'horas',
            'tiempo' => 2,
            'fase_ans' => true,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);
        $ansIds['normal'] = DB::table($t('tbl_ans'))->insertGetId([
            'medida' => 'horas',
            'tiempo' => 8,
            'fase_ans' => true,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);

        $rolAdminId = DB::table($t('tbl_rol'))->insertGetId([
            'nombre_rol' => 'Administrador',
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);
        $rolAgenteId = DB::table($t('tbl_rol'))->insertGetId([
            'nombre_rol' => 'Agente',
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);
        $rolUsuarioId = DB::table($t('tbl_rol'))->insertGetId([
            'nombre_rol' => 'Usuario final',
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);

        $estadoAbiertoId = DB::table($t('tbl_estado'))->insertGetId([
            'nombre_estado' => 'Abierto',
            'nivel' => 1,
            'fase_estado' => true,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);
        $estadoEnProcesoId = DB::table($t('tbl_estado'))->insertGetId([
            'nombre_estado' => 'En proceso',
            'nivel' => 2,
            'fase_estado' => true,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);
        $estadoCerradoId = DB::table($t('tbl_estado'))->insertGetId([
            'nombre_estado' => 'Cerrado',
            'nivel' => 3,
            'fase_estado' => true,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);

        $tareaSoporteId = DB::table($t('tbl_tarea'))->insertGetId([
            'nombre_tarea' => 'Soporte técnico',
            'estado' => 'activo',
            'fecha_creacion' => $now,
            'fecha_modificacion' => $now,
        ]);
        $tareaMantenimientoId = DB::table($t('tbl_tarea'))->insertGetId([
            'nombre_tarea' => 'Mantenimiento preventivo',
            'estado' => 'activo',
            'fecha_creacion' => $now,
            'fecha_modificacion' => $now,
        ]);

        $prioridadAltaId = DB::table($t('tbl_prioridad'))->insertGetId([
            'nombre_prioridad' => 'Alta',
            'orden' => 1,
            'color' => '#ef4444',
            'fase_prioridad' => true,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);
        $prioridadMediaId = DB::table($t('tbl_prioridad'))->insertGetId([
            'nombre_prioridad' => 'Media',
            'orden' => 2,
            'color' => '#f97316',
            'fase_prioridad' => true,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);
        $prioridadBajaId = DB::table($t('tbl_prioridad'))->insertGetId([
            'nombre_prioridad' => 'Baja',
            'orden' => 3,
            'color' => '#22c55e',
            'fase_prioridad' => true,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);

        $permisoVerId = DB::table($t('tbl_permisos'))->insertGetId([
            'name' => 'tickets.view',
            'guard_per' => 'web',
            'fecha_actualizacion' => $now,
        ]);
        $permisoGestionId = DB::table($t('tbl_permisos'))->insertGetId([
            'name' => 'tickets.manage',
            'guard_per' => 'web',
            'fecha_actualizacion' => $now,
        ]);

        $areaTiId = DB::table($t('tbl_area'))->insertGetId([
            'nombre_area' => 'Tecnología de la información',
            'fase_area' => true,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
            'fk_id_proceso' => $procesoId,
        ]);
        $areaSoporteId = DB::table($t('tbl_area'))->insertGetId([
            'nombre_area' => 'Soporte de usuarios',
            'fase_area' => true,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
            'fk_id_proceso' => $procesoId,
        ]);

        $departamentoInfraId = DB::table($t('tbl_departamento'))->insertGetId([
            'nombre_departamento' => 'Infraestructura',
            'fk_id_area' => $areaTiId,
            'estado' => 'activo',
            'created_at' => $now,
        ]);
        $departamentoAppsId = DB::table($t('tbl_departamento'))->insertGetId([
            'nombre_departamento' => 'Aplicaciones',
            'fk_id_area' => $areaTiId,
            'estado' => 'activo',
            'created_at' => $now,
        ]);

        $adminId = DB::table($t('tbl_usuario'))->insertGetId([
            'nombre_usuario' => 'Admin Service Desk',
            'correo' => 'admin@servicedesk.test',
            'password' => Hash::make('password'),
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
            'fk_id_area' => $areaTiId,
            'fk_id_departamento' => $departamentoInfraId,
            'fk_id_rol' => $rolAdminId,
            'estado' => 'activo',
        ]);
        $agenteId = DB::table($t('tbl_usuario'))->insertGetId([
            'nombre_usuario' => 'Agente Service Desk',
            'correo' => 'agente@servicedesk.test',
            'password' => Hash::make('password'),
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
            'fk_id_area' => $areaSoporteId,
            'fk_id_departamento' => $departamentoInfraId,
            'fk_id_rol' => $rolAgenteId,
            'estado' => 'activo',
        ]);
        $usuarioFinalId = DB::table($t('tbl_usuario'))->insertGetId([
            'nombre_usuario' => 'Usuario Final',
            'correo' => 'usuario@empresa.test',
            'password' => Hash::make('password'),
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
            'fk_id_area' => $areaSoporteId,
            'fk_id_departamento' => $departamentoAppsId,
            'fk_id_rol' => $rolUsuarioId,
            'estado' => 'activo',
        ]);

        $catNetId = DB::table($t('tbl_categoria'))->insertGetId([
            'nombre_categoria' => 'Incidencias de red',
            'siglas' => 'NET',
            'fase_categoria' => true,
            'fk_id_ans' => $ansIds['critico'],
            'fk_id_area' => $areaTiId,
            'fk_id_departamento' => $departamentoInfraId,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
            'fk_id_usuarios' => $adminId,
            'fk_id_tarea' => $tareaSoporteId,
        ]);
        $catAppId = DB::table($t('tbl_categoria'))->insertGetId([
            'nombre_categoria' => 'Soporte aplicaciones',
            'siglas' => 'APP',
            'fase_categoria' => true,
            'fk_id_ans' => $ansIds['normal'],
            'fk_id_area' => $areaSoporteId,
            'fk_id_departamento' => $departamentoAppsId,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
            'fk_id_usuarios' => $agenteId,
            'fk_id_tarea' => $tareaMantenimientoId,
        ]);
        
        $procesos = DB::table($t('tbl_proceso'))->select('id_proceso','nombre_proceso')->get();
        foreach ($procesos as $proc) {
            $areaRow = DB::table($t('tbl_area'))->where('fk_id_proceso', $proc->id_proceso)->first();
            $areaIdForProc = $areaRow ? $areaRow->id_area : DB::table($t('tbl_area'))->insertGetId([
                'nombre_area' => 'General ' . $proc->nombre_proceso,
                'fase_area' => true,
                'fecha_creacion' => $now,
                'fecha_actualizacion' => $now,
                'fk_id_proceso' => $proc->id_proceso,
            ]);
            $deptRow = DB::table($t('tbl_departamento'))->where('fk_id_area', $areaIdForProc)->first();
            $deptIdForProc = $deptRow ? $deptRow->id_departamento : DB::table($t('tbl_departamento'))->insertGetId([
                'nombre_departamento' => 'General',
                'fk_id_area' => $areaIdForProc,
                'estado' => 'activo',
                'created_at' => $now,
            ]);
            DB::table($t('tbl_categoria'))->insert([
                [
                    'nombre_categoria' => 'Incidencias',
                    'siglas' => 'INC',
                    'fase_categoria' => true,
                    'fk_id_ans' => $ansIds['critico'],
                    'fk_id_area' => $areaIdForProc,
                    'fk_id_departamento' => $deptIdForProc,
                    'fecha_creacion' => $now,
                    'fecha_actualizacion' => $now,
                    'fk_id_usuarios' => $adminId,
                    'fk_id_tarea' => $tareaSoporteId,
                ],
                [
                    'nombre_categoria' => 'Accesos',
                    'siglas' => 'ACC',
                    'fase_categoria' => true,
                    'fk_id_ans' => $ansIds['normal'],
                    'fk_id_area' => $areaIdForProc,
                    'fk_id_departamento' => $deptIdForProc,
                    'fecha_creacion' => $now,
                    'fecha_actualizacion' => $now,
                    'fk_id_usuarios' => $agenteId,
                    'fk_id_tarea' => $tareaMantenimientoId,
                ],
                [
                    'nombre_categoria' => 'Mantenimientos',
                    'siglas' => 'MNT',
                    'fase_categoria' => true,
                    'fk_id_ans' => $ansIds['normal'],
                    'fk_id_area' => $areaIdForProc,
                    'fk_id_departamento' => $deptIdForProc,
                    'fecha_creacion' => $now,
                    'fecha_actualizacion' => $now,
                    'fk_id_usuarios' => $adminId,
                    'fk_id_tarea' => $tareaMantenimientoId,
                ],
            ]);
        }

        DB::table($t('tbl_permiso_rol'))->insert([
            [
                'fk_id_permisos' => $permisoVerId,
                'fk_id_rol' => $rolUsuarioId,
            ],
            [
                'fk_id_permisos' => $permisoVerId,
                'fk_id_rol' => $rolAgenteId,
            ],
            [
                'fk_id_permisos' => $permisoGestionId,
                'fk_id_rol' => $rolAdminId,
            ],
        ]);

        DB::table($t('tbl_vacaciones'))->insert([
            [
                'fk_id_usuario' => $agenteId,
                'fecha_inicio' => $now->copy()->addDays(7)->toDateString(),
                'fecha_fin' => $now->copy()->addDays(14)->toDateString(),
                'fk_id_reemplazo' => $adminId,
                'observaciones' => 'Vacaciones programadas del agente de soporte',
                'created_at' => $now,
            ],
        ]);

        $ticketAbiertoId = DB::table($t('tbl_tickets'))->insertGetId([
            'descripcion' => 'Usuario no puede acceder al sistema ERP',
            'fk_id_prioridad' => $prioridadAltaId,
            'fk_id_estado' => $estadoAbiertoId,
            'fk_id_categoria' => $catNetId,
            'fk_id_usuario' => $usuarioFinalId,
            'fk_id_area' => $areaSoporteId,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);

        $ticketCerradoId = DB::table($t('tbl_tickets'))->insertGetId([
            'descripcion' => 'Error al imprimir reportes de ventas',
            'fk_id_prioridad' => $prioridadMediaId,
            'fk_id_estado' => $estadoCerradoId,
            'fk_id_categoria' => $catAppId,
            'fk_id_usuario' => $usuarioFinalId,
            'fk_id_area' => $areaSoporteId,
            'fecha_creacion' => $now->copy()->subDays(2),
            'fecha_actualizacion' => $now,
            'fecha_cierre' => $now,
        ]);

        DB::table($t('tbl_adjunto'))->insert([
            [
                'adjunto' => 'captura_error_erp.png',
                'tipo_adjunto' => 'image/png',
                'fecha_creacion' => $now,
                'fecha_actualizacion' => $now,
                'fk_id_ticket' => $ticketAbiertoId,
            ],
            [
                'adjunto' => 'log_impresion.txt',
                'tipo_adjunto' => 'text/plain',
                'fecha_creacion' => $now,
                'fecha_actualizacion' => $now,
                'fk_id_ticket' => $ticketCerradoId,
            ],
        ]);

        DB::table($t('tbl_evaluacion'))->insert([
            [
                'calificacion' => 5,
                'fk_id_usuario' => $usuarioFinalId,
                'fk_id_ticket' => $ticketCerradoId,
                'tiempo_respuesta' => 5,
                'eficiencia' => 5,
                'actitud' => 5,
                'conocimiento' => 5,
            ],
        ]);

        DB::table($t('tbl_configuracion'))->insert([
            'smtp_host' => 'smtp.mailtrap.io',
            'smtp_port' => 2525,
            'smtp_user' => 'servicedesk_user',
            'smtp_password' => 'servicedesk_pass',
            'smtp_encryption' => 'tls',
            'smtp_from_address' => 'no-reply@servicedesk.test',
            'smtp_from_name' => 'Service Desk',
            'created_at' => $now,
            'updated_at' => $now,
            'max_file_size' => 5242880,
            'allowed_file_types' => 'jpg,jpeg,png,pdf,doc,docx',
            'nombre_sitio' => 'Service Desk Demo',
            'base_url' => 'http://localhost',
            'nombre_administrador' => 'Admin Service Desk',
            'correo_administrador' => 'admin@servicedesk.test',
            'correo_base' => 'no-reply@servicedesk.test',
            'tipo_correo' => 'smtp',
            'nivel_prioridad_localizador' => 'alta',
            'notificar_actualizaciones_usuario' => 'si',
            'acceso_base_conocimientos' => 'any',
            'busquedas_sql_base_conocimientos' => 'disable',
            'prioridad_por_omision' => 'Media',
            'estado_por_omision' => 'Abierto',
            'estado_cerrado' => 'Cerrado',
            'tipo_autenticacion' => 'database',
            'seleccion_usuario' => 'no',
            'tablero_in_out' => 'no',
            'permitir_imagen' => 'si',
            'tamano_maximo_imagen' => 100000,
        ]);

        DB::table($t('tbl_correo'))->insert([
            [
                'asunto' => 'Ticket creado',
                'cuerpo' => 'Se ha creado un nuevo ticket en el service desk.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'asunto' => 'Ticket cerrado',
                'cuerpo' => 'Su ticket ha sido cerrado. Gracias por utilizar el servicio.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
