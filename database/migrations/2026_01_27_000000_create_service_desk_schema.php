<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // TABLAS DE LARAVEL
        // ═══════════════════════════════════════════════════════════
        
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
        
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('tokenable_type');
                $table->unsignedBigInteger('tokenable_id');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->index(['tokenable_type','tokenable_id']);
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
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };

        // ═══════════════════════════════════════════════════════════
        // TABLAS BASE (SIN DEPENDENCIAS)
        // ═══════════════════════════════════════════════════════════

        // 1. TBL_PROCESO
        Schema::create($tname('tbl_proceso'), function (Blueprint $table) {
            $table->increments('id_proceso');
            $table->string('nombre_proceso', 180);
            $table->boolean('fase_proceso')->default(true);
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            $table->timestamp('fecha_inactivacion')->nullable();
            $table->index('fase_proceso');
        });

        // 2. TBL_ANS
        Schema::create($tname('tbl_ans'), function (Blueprint $table) {
            $table->increments('id_ans');
            $table->string('medida', 50);
            $table->integer('tiempo');
            $table->boolean('fase_ans')->default(true);
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            $table->timestamp('fecha_inactivacion')->nullable();
            $table->index('fase_ans');
        });

        // 3. TBL_ROL
        Schema::create($tname('tbl_rol'), function (Blueprint $table) {
            $table->increments('id_rol');
            $table->string('nombre_rol', 100);
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            $table->timestamp('fecha_inactivacion')->nullable();
            $table->index('nombre_rol');
        });

        // 4. TBL_ESTADO
        Schema::create($tname('tbl_estado'), function (Blueprint $table) {
            $table->increments('id_estado');
            $table->string('nombre_estado', 50);
            $table->integer('nivel');
            $table->boolean('fase_estado')->default(true);
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            $table->timestamp('fecha_inactivacion')->nullable();
            $table->index('fase_estado');
            $table->index('nivel');
        });

        // 5. TBL_TAREA
        Schema::create($tname('tbl_tarea'), function (Blueprint $table) {
            $table->increments('id_tarea');
            $table->string('nombre_tarea', 180);
            $table->enum('estado', ['activo','inactivo'])->default('activo'); // ✅ ESTANDARIZADO
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            $table->index('nombre_tarea');
            $table->index('estado');
        });

        // 6. TBL_PRIORIDAD
        Schema::create($tname('tbl_prioridad'), function (Blueprint $table) {
            $table->increments('id_prioridad');
            $table->string('nombre_prioridad', 50);
            $table->integer('orden');
            $table->string('color', 20)->nullable();
            $table->boolean('fase_prioridad')->default(true);
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            $table->timestamp('fecha_inactivacion')->nullable();
            $table->index('fase_prioridad');
            $table->index('orden');
        });

        // 7. TBL_PERMISOS
        Schema::create($tname('tbl_permisos'), function (Blueprint $table) {
            $table->increments('id_permiso');
            $table->string('name', 100);
            $table->string('guard_per', 50)->nullable();
            $table->timestamp('fecha_actualizacion')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            $table->timestamp('fecha_inactivacion')->nullable();
            $table->index('name');
        });

        // ═══════════════════════════════════════════════════════════
        // NIVEL 1 - DEPENDENCIAS DIRECTAS
        // ═══════════════════════════════════════════════════════════

        // 8. TBL_AREA (depende de tbl_proceso)
        Schema::create($tname('tbl_area'), function (Blueprint $table) use ($tname) {
            $table->increments('id_area');
            $table->string('nombre_area', 100);
            $table->boolean('fase_area')->default(true);
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            $table->timestamp('fecha_inactivacion')->nullable();
            $table->unsignedInteger('fk_id_proceso');
            
            $table->index('fase_area');
            $table->index('fk_id_proceso');
            
            $table->foreign('fk_id_proceso')
                ->references('id_proceso')
                ->on($tname('tbl_proceso'))
                ->onDelete('no action');
        });

        // ═══════════════════════════════════════════════════════════
        // NIVEL 2 - DEPENDENCIAS SECUNDARIAS
        // ═══════════════════════════════════════════════════════════

        // 9. TBL_DEPARTAMENTO (depende de tbl_area)
        Schema::create($tname('tbl_departamento'), function (Blueprint $table) use ($tname) {
            $table->increments('id_departamento');
            $table->string('nombre_departamento', 150);
            $table->unsignedInteger('fk_id_area');
            $table->enum('estado', ['activo','inactivo'])->default('activo'); // ✅ ESTANDARIZADO
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('fecha_inactivacion')->nullable();
            
            $table->index('estado');
            $table->index('fk_id_area');
            
            $table->foreign('fk_id_area')
                ->references('id_area')
                ->on($tname('tbl_area'))
                ->onDelete('no action');
        });

        // ═══════════════════════════════════════════════════════════
        // NIVEL 3 - DEPENDENCIAS TERCIARIAS
        // ═══════════════════════════════════════════════════════════

        // 10. TBL_USUARIO (depende de area, departamento, rol)
        Schema::create($tname('tbl_usuario'), function (Blueprint $table) use ($tname) {
            $table->increments('id_usuario');
            $table->string('nombre_usuario', 150);
            $table->string('correo', 100)->unique();
            $table->string('password', 255);
            $table->string('remember_token', 100)->nullable();
            $table->string('firebase_uid', 255)->nullable(); // ✅ SIN unique directo
            $table->string('fcm_token', 255)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->string('security_token', 64)->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            $table->timestamp('fecha_inactivacion')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->unsignedInteger('fk_id_area');
            $table->unsignedInteger('fk_id_departamento');
            $table->unsignedInteger('fk_id_rol');
            $table->enum('estado', ['activo','inactivo'])->default('activo');
            
            $table->index('fk_id_rol');
            $table->index('fk_id_area');
            $table->index('fk_id_departamento');
            $table->index('estado');
            
            $table->foreign('fk_id_area')
                ->references('id_area')
                ->on($tname('tbl_area'))
                ->onDelete('no action');
            
            $table->foreign('fk_id_departamento')
                ->references('id_departamento')
                ->on($tname('tbl_departamento'))
                ->onDelete('no action');
            
            $table->foreign('fk_id_rol')
                ->references('id_rol')
                ->on($tname('tbl_rol'))
                ->onDelete('no action');
        });

        $realTableName = $tname('tbl_usuario');
        $driver = Schema::getConnection()->getDriverName();

        try {
            if ($driver === 'sqlite') {
                DB::statement("
                    CREATE UNIQUE INDEX idx_firebase_uid 
                    ON {$realTableName} (firebase_uid) 
                    WHERE firebase_uid IS NOT NULL
                ");
            } elseif ($driver === 'sqlsrv') {
                DB::statement("
                    CREATE UNIQUE NONCLUSTERED INDEX idx_firebase_uid 
                    ON [{$realTableName}] (firebase_uid) 
                    WHERE firebase_uid IS NOT NULL
                ");
            }
        } catch (\Exception $e) {
            Log::error("Error creando índice único para firebase_uid: " . $e->getMessage());
        }

        // 11. TBL_CATEGORIA (depende de ans, area, departamento, usuario, tarea)
        Schema::create($tname('tbl_categoria'), function (Blueprint $table) use ($tname) {
            $table->increments('id_categoria');
            $table->string('nombre_categoria', 100);
            $table->string('siglas', 10)->nullable();
            $table->boolean('fase_categoria')->default(true);
            $table->unsignedInteger('fk_id_ans');
            $table->unsignedInteger('fk_id_area');
            $table->unsignedInteger('fk_id_departamento');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            $table->timestamp('fecha_inactivacion')->nullable();
            $table->unsignedInteger('fk_id_usuarios');
            $table->unsignedInteger('fk_id_tarea');
            
            $table->index('fase_categoria');
            $table->index('fk_id_ans');
            $table->index('fk_id_area');
            $table->index('fk_id_departamento');
            $table->index('fk_id_usuarios');
            $table->index('fk_id_tarea');
            
            $table->foreign('fk_id_ans')
                ->references('id_ans')
                ->on($tname('tbl_ans'))
                ->onDelete('no action');
            
            $table->foreign('fk_id_area')
                ->references('id_area')
                ->on($tname('tbl_area'))
                ->onDelete('no action');
            
            $table->foreign('fk_id_departamento')
                ->references('id_departamento')
                ->on($tname('tbl_departamento'))
                ->onDelete('no action');
            
            $table->foreign('fk_id_tarea')
                ->references('id_tarea')
                ->on($tname('tbl_tarea'))
                ->onDelete('no action');
            
            $table->foreign('fk_id_usuarios')
                ->references('id_usuario')
                ->on($tname('tbl_usuario'))
                ->onDelete('no action');
        });

        // 12. TBL_PERMISO_ROL (depende de permisos, rol)
        Schema::create($tname('tbl_permiso_rol'), function (Blueprint $table) use ($tname) {
            $table->increments('id_permiso_rol');
            $table->unsignedInteger('fk_id_permisos');
            $table->unsignedInteger('fk_id_rol');
            
            $table->index('fk_id_permisos');
            $table->index('fk_id_rol');
            
            $table->foreign('fk_id_permisos')
                ->references('id_permiso')
                ->on($tname('tbl_permisos'))
                ->onDelete('no action');
            
            $table->foreign('fk_id_rol')
                ->references('id_rol')
                ->on($tname('tbl_rol'))
                ->onDelete('no action');
        });

        // 13. TBL_VACACIONES (depende de usuario)
        Schema::create($tname('tbl_vacaciones'), function (Blueprint $table) use ($tname) {
            $table->increments('id_vacaciones');
            $table->unsignedInteger('fk_id_usuario');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedInteger('fk_id_reemplazo');
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('fk_id_usuario');
            $table->index('fk_id_reemplazo');
            $table->index('fecha_inicio');
            $table->index('fecha_fin');
            
            $table->foreign('fk_id_reemplazo')
                ->references('id_usuario')
                ->on($tname('tbl_usuario'))
                ->onDelete('no action');
            
            $table->foreign('fk_id_usuario')
                ->references('id_usuario')
                ->on($tname('tbl_usuario'))
                ->onDelete('no action');
        });

        // ═══════════════════════════════════════════════════════════
        // NIVEL 4 - DEPENDENCIAS CUATERNARIAS
        // ═══════════════════════════════════════════════════════════

        // 14. TBL_TICKETS (depende de prioridad, estado, categoria, usuario, area)
        Schema::create($tname('tbl_tickets'), function (Blueprint $table) use ($tname) {
            $table->increments('id_tickets');
            $table->text('descripcion');
            $table->unsignedInteger('fk_id_prioridad');
            $table->unsignedInteger('fk_id_estado');
            $table->unsignedInteger('fk_id_categoria');
            $table->unsignedInteger('fk_id_usuario');
            $table->unsignedInteger('fk_id_area');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            $table->timestamp('fecha_cierre')->nullable();
            
            $table->index('fk_id_estado');
            $table->index('fk_id_usuario');
            $table->index('fk_id_categoria');
            $table->index('fk_id_area');
            $table->index('fecha_creacion');
            $table->index('fk_id_prioridad');
            $table->index('fecha_cierre');
            
            $table->foreign('fk_id_area')
                ->references('id_area')
                ->on($tname('tbl_area'))
                ->onDelete('no action');
            
            $table->foreign('fk_id_categoria')
                ->references('id_categoria')
                ->on($tname('tbl_categoria'))
                ->onDelete('no action');
            
            $table->foreign('fk_id_estado')
                ->references('id_estado')
                ->on($tname('tbl_estado'))
                ->onDelete('no action');
            
            $table->foreign('fk_id_prioridad')
                ->references('id_prioridad')
                ->on($tname('tbl_prioridad'))
                ->onDelete('no action');
            
            $table->foreign('fk_id_usuario')
                ->references('id_usuario')
                ->on($tname('tbl_usuario'))
                ->onDelete('no action');
        });

        // ═══════════════════════════════════════════════════════════
        // NIVEL 5 - DEPENDENCIAS FINALES
        // ═══════════════════════════════════════════════════════════

        // 15. TBL_ADJUNTO (depende de tickets)
        Schema::create($tname('tbl_adjunto'), function (Blueprint $table) use ($tname) {
            $table->increments('id_adjunto');
            $table->string('adjunto', 255);
            $table->string('tipo_adjunto', 100)->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            $table->timestamp('fecha_inactivacion')->nullable();
            $table->unsignedInteger('fk_id_ticket');
            
            $table->index('fk_id_ticket');
            $table->index('fecha_creacion');
            
            $table->foreign('fk_id_ticket')
                ->references('id_tickets')
                ->on($tname('tbl_tickets'))
                ->onDelete('no action');
        });

        // 16. TBL_EVALUACION (depende de usuario, tickets)
        Schema::create($tname('tbl_evaluacion'), function (Blueprint $table) use ($tname) {
            $table->increments('id_evaluacion');
            $table->unsignedTinyInteger('calificacion');
            $table->unsignedInteger('fk_id_usuario');
            $table->unsignedInteger('fk_id_ticket');
            $table->unsignedTinyInteger('tiempo_respuesta')->nullable();
            $table->unsignedTinyInteger('eficiencia')->nullable();
            $table->unsignedTinyInteger('actitud')->nullable();
            $table->unsignedTinyInteger('conocimiento')->nullable();
            
            $table->index('fk_id_ticket');
            $table->index('fk_id_usuario');
            $table->index('calificacion');
            
            $table->foreign('fk_id_ticket')
                ->references('id_tickets')
                ->on($tname('tbl_tickets'))
                ->onDelete('no action');
            
            $table->foreign('fk_id_usuario')
                ->references('id_usuario')
                ->on($tname('tbl_usuario'))
                ->onDelete('no action');
        });

        // ═══════════════════════════════════════════════════════════
        // TABLAS DE CONFIGURACIÓN (SIN DEPENDENCIAS)
        // ═══════════════════════════════════════════════════════════

        // 17. TBL_CONFIGURACION
        Schema::create($tname('tbl_configuracion'), function (Blueprint $table) {
            $table->increments('id_configuracion');
            $table->string('smtp_host', 100);
            $table->integer('smtp_port')->default(587);
            $table->string('smtp_user', 100);
            $table->string('smtp_password', 255);
            $table->string('smtp_encryption', 10)->default('tls');
            $table->string('smtp_from_address', 100);
            $table->string('smtp_from_name', 100);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            $table->integer('max_file_size')->default(5242880);
            $table->string('allowed_file_types', 255)->default('jpg,jpeg,png,pdf,doc,docx');
            $table->string('nombre_sitio', 150)->nullable();
            $table->string('base_url', 255)->nullable();
            $table->string('nombre_administrador', 150)->nullable();
            $table->string('correo_administrador', 150)->nullable();
            $table->string('correo_base', 150)->nullable();
            $table->string('tipo_correo', 50)->default('cdosys');
            $table->string('servidor_smtp', 100)->nullable();
            $table->string('nivel_prioridad_localizador', 50)->nullable();
            $table->string('notificar_actualizaciones_usuario', 2)->default('si');
            $table->string('acceso_base_conocimientos', 20)->default('any');
            $table->string('busquedas_sql_base_conocimientos', 20)->default('disable');
            $table->string('prioridad_por_omision', 20)->nullable();
            $table->string('estado_por_omision', 50)->nullable();
            $table->string('estado_cerrado', 50)->nullable();
            $table->string('tipo_autenticacion', 20)->default('database');
            $table->string('seleccion_usuario', 2)->default('no');
            $table->string('tablero_in_out', 2)->default('no');
            $table->string('permitir_imagen', 2)->default('no');
            $table->integer('tamano_maximo_imagen')->default(100000);
            
            $table->index('tipo_correo');
        });

        // 18. TBL_CORREO
        Schema::create($tname('tbl_correo'), function (Blueprint $table) {
            $table->increments('id_correo');
            $table->string('asunto', 255);
            $table->text('cuerpo');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent(); // ✅ SIN useCurrentOnUpdate
            
            $table->index('asunto');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        $prefix = env('DB_PREFIX', '');
        $tname = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };
        
        // ✅ ORDEN INVERSO RESPETANDO DEPENDENCIAS
        Schema::dropIfExists($tname('tbl_correo'));
        Schema::dropIfExists($tname('tbl_configuracion'));
        Schema::dropIfExists($tname('tbl_evaluacion'));
        Schema::dropIfExists($tname('tbl_adjunto'));
        Schema::dropIfExists($tname('tbl_tickets'));
        Schema::dropIfExists($tname('tbl_vacaciones'));
        Schema::dropIfExists($tname('tbl_permiso_rol'));
        Schema::dropIfExists($tname('tbl_categoria'));
        Schema::dropIfExists($tname('tbl_usuario'));
        Schema::dropIfExists($tname('tbl_departamento'));
        Schema::dropIfExists($tname('tbl_area'));
        Schema::dropIfExists($tname('tbl_permisos'));
        Schema::dropIfExists($tname('tbl_prioridad'));
        Schema::dropIfExists($tname('tbl_tarea'));
        Schema::dropIfExists($tname('tbl_estado'));
        Schema::dropIfExists($tname('tbl_rol'));
        Schema::dropIfExists($tname('tbl_ans'));
        Schema::dropIfExists($tname('tbl_proceso'));
        
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('password_reset_tokens');
    }
};
