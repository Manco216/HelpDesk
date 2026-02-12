<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * ═══════════════════════════════════════════════════════════
     * MIGRACIÓN MINIMALISTA - SISTEMA DE TICKETS
     * ═══════════════════════════════════════════════════════════
     * 
     * FILOSOFÍA: MÁXIMA FUNCIONALIDAD CON MÍNIMAS TABLAS
     * 
     * TABLAS ELIMINADAS (funcionalidad consolidada):
     * ❌ tbl_area (eliminada - redundante)
     * ❌ tbl_ans (consolidado en categoria como campos)
     * ❌ tbl_tarea (consolidado en categoria)
     * ❌ tbl_permisos + tbl_permiso_rol (usar spatie/laravel-permission)
     * ❌ tbl_vacaciones (no es core del sistema de tickets)
     * ❌ tbl_etiquetas + tbl_tickets_etiquetas (usar JSON en tickets)
     * ❌ tbl_tickets_historial (usar auditing package o JSON)
     * ❌ tbl_plantilla_correo (usar Laravel Notifications)
     * ❌ tbl_notificaciones (usar Laravel Notifications)
     * ❌ tbl_tickets_comentarios (consolidado en tickets como JSON o tabla simple)
     * ❌ tbl_configuracion (usar config/database, .env, y cache)
     * 
     * RESULTADO: 10 TABLAS CORE (vs 23 anteriores)
     * ✅ tbl_proceso (mantenida - importante para organización)
     * ✅ tbl_departamento (jerarquía de departamentos/subdepartamentos)
     * ✅ tbl_rol
     * ✅ tbl_usuario
     * ✅ tbl_categoria (con ANS integrado)
     * ✅ tbl_estado
     * ✅ tbl_prioridad
     * ✅ tbl_tickets (con historial en JSON)
     * ✅ tbl_adjunto
     * ✅ tbl_evaluacion (opcional - puede eliminarse)
     */
    
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // TABLAS DE LARAVEL (AUTENTICACIÓN)
        // ═══════════════════════════════════════════════════════════
        
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
        
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
        
        // ═══════════════════════════════════════════════════════════
        // FUNCIÓN AUXILIAR PARA NOMBRES DE TABLA
        // ═══════════════════════════════════════════════════════════
        
        $prefix = env('DB_PREFIX', '');
        $tname = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') 
                ? preg_replace('/^tbl_/i', $prefix, $nm) 
                : $nm;
        };
        
        // ═══════════════════════════════════════════════════════════
        // TABLA 1: PROCESO (Mantenida - importante)
        // ═══════════════════════════════════════════════════════════
        
        if (!Schema::hasTable($tname('tbl_proceso'))) Schema::create($tname('tbl_proceso'), function (Blueprint $table) {
            $table->increments('id_proceso');
            $table->string('nombre', 180);
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('estado');
            $table->index('nombre');
        });
        
        // ═══════════════════════════════════════════════════════════
        // TABLA 2: DEPARTAMENTO (Jerarquía auto-referencial)
        // ═══════════════════════════════════════════════════════════
        // Consolida: tbl_area + tbl_departamento (antiguo)
        
        if (!Schema::hasTable($tname('tbl_departamento'))) Schema::create($tname('tbl_departamento'), function (Blueprint $table) use ($tname) {
            $table->increments('id_departamento');
            $table->string('nombre', 150);
            $table->string('codigo', 20)->nullable()->unique();
            $table->text('descripcion')->nullable();
            
            // Relación con proceso
            $table->unsignedInteger('fk_id_proceso');
            
            // Jerarquía auto-referencial (permite múltiples niveles)
            $table->unsignedInteger('fk_id_padre')->nullable()->comment('NULL = departamento raíz, si tiene valor = subdepartamento');
            
            // Metadata adicional
            $table->json('metadata')->nullable()->comment('Configuración adicional en JSON');
            
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('fk_id_proceso');
            $table->index('fk_id_padre');
            $table->index('estado');
            $table->index('codigo');
            
            // FKs
            $table->foreign('fk_id_proceso')
                ->references('id_proceso')
                ->on($tname('tbl_proceso'))
                ->onDelete('restrict');
                
            $table->foreign('fk_id_padre')
                ->references('id_departamento')
                ->on($tname('tbl_departamento'))
                ->onDelete('restrict');
        });
        
        // ═══════════════════════════════════════════════════════════
        // TABLA 3: ROL (Simplificado)
        // ═══════════════════════════════════════════════════════════
        
        if (!Schema::hasTable($tname('tbl_rol'))) Schema::create($tname('tbl_rol'), function (Blueprint $table) {
            $table->increments('id_rol');
            $table->string('nombre', 100)->unique();
            $table->text('descripcion')->nullable();
            
            // Permisos en JSON (en vez de tabla separada)
            $table->json('permisos')->nullable()->comment('Array de permisos: ["crear_tickets","editar_tickets",...]');
            
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('nombre');
            $table->index('estado');
        });
        
        // ═══════════════════════════════════════════════════════════
        // TABLA 4: USUARIO (Optimizado)
        // ═══════════════════════════════════════════════════════════
        
        if (!Schema::hasTable($tname('tbl_usuario'))) Schema::create($tname('tbl_usuario'), function (Blueprint $table) use ($tname) {
            $table->increments('id_usuario');
            $table->string('nombre', 150);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            
            // Autenticación
            $table->rememberToken();
            $table->string('firebase_uid', 255)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            
            // Contacto (consolidado)
            $table->string('telefono', 20)->nullable();
            $table->string('avatar', 255)->nullable();
            
            // Relaciones organizacionales
            $table->unsignedInteger('fk_id_departamento');
            $table->unsignedInteger('fk_id_rol');
            
            // Preferencias y configuración en JSON (en vez de tabla configuración)
            $table->json('preferencias')->nullable()->comment('Notificaciones, tema, idioma, etc');
            
            $table->enum('estado', ['activo', 'inactivo', 'suspendido'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['fk_id_departamento', 'estado']);
            $table->index('fk_id_rol');
            $table->index('estado');
            
            // FKs
            $table->foreign('fk_id_departamento')
                ->references('id_departamento')
                ->on($tname('tbl_departamento'))
                ->onDelete('restrict');
                
            $table->foreign('fk_id_rol')
                ->references('id_rol')
                ->on($tname('tbl_rol'))
                ->onDelete('restrict');
        });
        
        // Índice único condicional para firebase_uid
        $realTableName = $tname('tbl_usuario');
        $driver = Schema::getConnection()->getDriverName();
        
        try {
            if ($driver === 'sqlite') {
                DB::statement("CREATE UNIQUE INDEX idx_firebase_uid ON {$realTableName} (firebase_uid) WHERE firebase_uid IS NOT NULL");
            } elseif ($driver === 'sqlsrv') {
                DB::statement("CREATE UNIQUE NONCLUSTERED INDEX idx_firebase_uid ON [{$realTableName}] (firebase_uid) WHERE firebase_uid IS NOT NULL");
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement("CREATE UNIQUE INDEX idx_firebase_uid ON {$realTableName} (firebase_uid)");
            }
        } catch (\Exception $e) {
            Log::error("Error creando índice único para firebase_uid: " . $e->getMessage());
        }
        
        // ═══════════════════════════════════════════════════════════
        // TABLA 5: ESTADO (Flujo de tickets)
        // ═══════════════════════════════════════════════════════════
        
        if (!Schema::hasTable($tname('tbl_estado'))) Schema::create($tname('tbl_estado'), function (Blueprint $table) {
            $table->increments('id_estado');
            $table->string('nombre', 50);
            $table->string('color', 20)->nullable();
            $table->integer('orden')->unsigned()->comment('Orden en el flujo');
            $table->boolean('es_inicial')->default(false);
            $table->boolean('es_final')->default(false);
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('orden');
            $table->index('es_inicial');
            $table->index('es_final');
        });
        
        // ═══════════════════════════════════════════════════════════
        // TABLA 6: PRIORIDAD
        // ═══════════════════════════════════════════════════════════
        
        if (!Schema::hasTable($tname('tbl_prioridad'))) Schema::create($tname('tbl_prioridad'), function (Blueprint $table) {
            $table->increments('id_prioridad');
            $table->string('nombre', 50);
            $table->integer('orden')->unsigned();
            $table->string('color', 20)->nullable();
            
            // SLA integrado (en vez de tabla ANS separada)
            $table->integer('sla_horas')->nullable()->comment('Horas para primera respuesta');
            $table->integer('sla_resolucion_horas')->nullable()->comment('Horas para resolución');
            
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('orden');
            $table->index('estado');
        });
        
        // ═══════════════════════════════════════════════════════════
        // TABLA 7: CATEGORIA (Con ANS y Tarea integrados)
        // ═══════════════════════════════════════════════════════════
        // Consolida: tbl_categoria + tbl_ans + tbl_tarea
        
        if (!Schema::hasTable($tname('tbl_categoria'))) Schema::create($tname('tbl_categoria'), function (Blueprint $table) use ($tname) {
            $table->increments('id_categoria');
            $table->string('nombre', 100);
            $table->string('codigo', 20)->nullable()->unique();
            $table->text('descripcion')->nullable();
            
            // ANS integrado (en vez de FK a tabla ANS)
            $table->integer('sla_respuesta_horas')->nullable()->comment('SLA primera respuesta en horas');
            $table->integer('sla_resolucion_horas')->nullable()->comment('SLA resolución en horas');
            
            // Relaciones
            $table->unsignedInteger('fk_id_departamento');
            $table->unsignedInteger('fk_id_responsable')->nullable()->comment('Usuario responsable por defecto');
            
            // Configuración adicional en JSON
            $table->json('configuracion')->nullable()->comment('Plantillas, workflows, etc');
            
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('fk_id_departamento');
            $table->index('fk_id_responsable');
            $table->index('codigo');
            $table->index('estado');
            
            // FKs
            $table->foreign('fk_id_departamento')
                ->references('id_departamento')
                ->on($tname('tbl_departamento'))
                ->onDelete('restrict');
                
            $table->foreign('fk_id_responsable')
                ->references('id_usuario')
                ->on($tname('tbl_usuario'))
                ->onDelete('set null');
        });
        
        // ═══════════════════════════════════════════════════════════
        // TABLA 8: TICKETS (Con historial y comentarios en JSON)
        // ═══════════════════════════════════════════════════════════
        // Consolida: tbl_tickets + historial + comentarios + etiquetas
        
        if (!Schema::hasTable($tname('tbl_tickets'))) Schema::create($tname('tbl_tickets'), function (Blueprint $table) use ($tname) {
            $table->increments('id_tickets');
            $table->string('codigo', 50)->unique();
            $table->string('asunto', 255);
            $table->text('descripcion');
            
            // Relaciones
            $table->unsignedInteger('fk_id_categoria');
            $table->unsignedInteger('fk_id_prioridad');
            $table->unsignedInteger('fk_id_estado');
            $table->unsignedInteger('fk_id_usuario_creador');
            $table->unsignedInteger('fk_id_usuario_asignado')->nullable();
            
            // Fechas
            $table->timestamp('fecha_vencimiento_sla')->nullable();
            $table->timestamp('fecha_primera_respuesta')->nullable();
            $table->timestamp('fecha_resolucion')->nullable();
            $table->timestamp('fecha_cierre')->nullable();
            
            // Métricas
            $table->boolean('sla_cumplido')->nullable();
            $table->integer('tiempo_respuesta_minutos')->nullable();
            $table->integer('tiempo_resolucion_minutos')->nullable();
            
            // CONSOLIDACIÓN EN JSON (reemplaza múltiples tablas)
            $table->json('historial')->nullable()->comment('Array de cambios [{fecha, usuario, accion, datos}]');
            $table->json('comentarios')->nullable()->comment('Array de comentarios [{fecha, usuario, texto, interno}]');
            $table->json('etiquetas')->nullable()->comment('Array simple ["urgente","bug","facturacion"]');
            $table->json('metadata')->nullable()->comment('Campos personalizados, datos adicionales');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices optimizados
            $table->index('codigo');
            $table->index(['fk_id_usuario_creador', 'fk_id_estado']);
            $table->index(['fk_id_usuario_asignado', 'fk_id_estado']);
            $table->index(['fk_id_categoria', 'fk_id_estado']);
            $table->index('fk_id_prioridad');
            $table->index('fk_id_estado');
            $table->index('fecha_vencimiento_sla');
            
            // FKs
            $table->foreign('fk_id_categoria')
                ->references('id_categoria')
                ->on($tname('tbl_categoria'))
                ->onDelete('restrict');
                
            $table->foreign('fk_id_estado')
                ->references('id_estado')
                ->on($tname('tbl_estado'))
                ->onDelete('restrict');
                
            $table->foreign('fk_id_prioridad')
                ->references('id_prioridad')
                ->on($tname('tbl_prioridad'))
                ->onDelete('restrict');
                
            $table->foreign('fk_id_usuario_creador')
                ->references('id_usuario')
                ->on($tname('tbl_usuario'))
                ->onDelete('restrict');
                
            $table->foreign('fk_id_usuario_asignado')
                ->references('id_usuario')
                ->on($tname('tbl_usuario'))
                ->onDelete('set null');
        });
        
        // ═══════════════════════════════════════════════════════════
        // TABLA 9: ADJUNTO (Simplificado)
        // ═══════════════════════════════════════════════════════════
        
        if (!Schema::hasTable($tname('tbl_adjunto'))) Schema::create($tname('tbl_adjunto'), function (Blueprint $table) use ($tname) {
            $table->increments('id_adjunto');
            $table->string('nombre_original', 255);
            $table->string('ruta', 500);
            $table->string('tipo_mime', 100);
            $table->unsignedInteger('tamano_bytes');
            $table->unsignedInteger('fk_id_ticket');
            $table->unsignedInteger('fk_id_usuario');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('fk_id_ticket');
            $table->index('fk_id_usuario');
            
            $table->foreign('fk_id_ticket')
                ->references('id_tickets')
                ->on($tname('tbl_tickets'))
                ->onDelete('cascade');
                
            $table->foreign('fk_id_usuario')
                ->references('id_usuario')
                ->on($tname('tbl_usuario'))
                ->onDelete('restrict');
        });
        
        // ═══════════════════════════════════════════════════════════
        // TABLA 10: EVALUACION (Opcional - puede eliminarse si no es crítico)
        // ═══════════════════════════════════════════════════════════
        
        if (!Schema::hasTable($tname('tbl_evaluacion'))) Schema::create($tname('tbl_evaluacion'), function (Blueprint $table) use ($tname) {
            $table->increments('id_evaluacion');
            $table->unsignedInteger('fk_id_ticket');
            $table->unsignedInteger('fk_id_usuario_evaluado');
            $table->unsignedTinyInteger('calificacion')->comment('1-5');
            
            // Calificaciones detalladas en JSON (en vez de columnas separadas)
            $table->json('detalles')->nullable()->comment('{tiempo_respuesta:4, eficiencia:5, actitud:5, conocimiento:4}');
            $table->text('comentario')->nullable();
            $table->timestamps();
            
            $table->unique('fk_id_ticket');
            $table->index('fk_id_usuario_evaluado');
            $table->index('calificacion');
            
            $table->foreign('fk_id_ticket')
                ->references('id_tickets')
                ->on($tname('tbl_tickets'))
                ->onDelete('cascade');
                
            $table->foreign('fk_id_usuario_evaluado')
                ->references('id_usuario')
                ->on($tname('tbl_usuario'))
                ->onDelete('restrict');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = env('DB_PREFIX', '');
        $tname = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') 
                ? preg_replace('/^tbl_/i', $prefix, $nm) 
                : $nm;
        };
        
        Schema::dropIfExists($tname('tbl_evaluacion'));
        Schema::dropIfExists($tname('tbl_adjunto'));
        Schema::dropIfExists($tname('tbl_tickets'));
        Schema::dropIfExists($tname('tbl_categoria'));
        Schema::dropIfExists($tname('tbl_prioridad'));
        Schema::dropIfExists($tname('tbl_estado'));
        Schema::dropIfExists($tname('tbl_usuario'));
        Schema::dropIfExists($tname('tbl_rol'));
        Schema::dropIfExists($tname('tbl_departamento'));
        Schema::dropIfExists($tname('tbl_proceso'));
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('password_reset_tokens');
    }
};
