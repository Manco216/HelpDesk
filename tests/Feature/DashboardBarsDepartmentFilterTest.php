<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardBarsDepartmentFilterTest extends TestCase
{
    use RefreshDatabase;

    private function tname(string $nm): string
    {
        $prefix = env('DB_PREFIX', '');
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    }

    public function test_bars_month_filters_by_user_department(): void
    {
        $tickets = $this->tname('tbl_tickets');
        $categoria = $this->tname('tbl_categoria');
        $departamento = $this->tname('tbl_departamento');
        $proceso = $this->tname('tbl_proceso');
        $estado = $this->tname('tbl_estado');
        $prioridad = $this->tname('tbl_prioridad');
        $usuario = $this->tname('tbl_usuario');

        foreach ([$tickets,$categoria,$departamento,$proceso,$estado,$prioridad,$usuario] as $tb) {
            if (!Schema::hasTable($tb)) {
                $this->markTestSkipped('Service desk tables are not present.');
                return;
            }
        }

        $now = now();
        $depA = DB::table($departamento)->insertGetId([
            'nombre' => 'Dept A', 'codigo' => 'DEP_A', 'estado' => 'activo',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $depB = DB::table($departamento)->insertGetId([
            'nombre' => 'Dept B', 'codigo' => 'DEP_B', 'estado' => 'activo',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $procAId = DB::table($proceso)->insertGetId(array_filter([
            Schema::hasColumn($proceso,'nombre_proceso') ? 'nombre_proceso' : 'nombre' => 'Proceso A',
            'estado' => Schema::hasColumn($proceso,'estado') ? 'activo' : null,
            'fk_id_departamento' => Schema::hasColumn($proceso,'fk_id_departamento') ? $depA : null,
            'created_at' => Schema::hasColumn($proceso,'created_at') ? $now : null,
            'updated_at' => Schema::hasColumn($proceso,'updated_at') ? $now : null,
        ], function ($v) { return $v !== null; }));
        $procBId = DB::table($proceso)->insertGetId(array_filter([
            Schema::hasColumn($proceso,'nombre_proceso') ? 'nombre_proceso' : 'nombre' => 'Proceso B',
            'estado' => Schema::hasColumn($proceso,'estado') ? 'activo' : null,
            'fk_id_departamento' => Schema::hasColumn($proceso,'fk_id_departamento') ? $depB : null,
            'created_at' => Schema::hasColumn($proceso,'created_at') ? $now : null,
            'updated_at' => Schema::hasColumn($proceso,'updated_at') ? $now : null,
        ], function ($v) { return $v !== null; }));

        $catA = DB::table($categoria)->insertGetId(array_filter([
            'nombre' => 'Cat A', 'codigo' => 'CAT_A', 'estado' => 'activo',
            'fk_id_departamento' => Schema::hasColumn($categoria,'fk_id_departamento') ? $depA : null,
            'fk_id_proceso' => Schema::hasColumn($categoria,'fk_id_proceso') ? $procAId : null,
            'created_at' => $now, 'updated_at' => $now,
        ], function ($v) { return $v !== null; }));
        $catB = DB::table($categoria)->insertGetId(array_filter([
            'nombre' => 'Cat B', 'codigo' => 'CAT_B', 'estado' => 'activo',
            'fk_id_departamento' => Schema::hasColumn($categoria,'fk_id_departamento') ? $depB : null,
            'fk_id_proceso' => Schema::hasColumn($categoria,'fk_id_proceso') ? $procBId : null,
            'created_at' => $now, 'updated_at' => $now,
        ], function ($v) { return $v !== null; }));

        $estadoId = DB::table($estado)->insertGetId([
            Schema::hasColumn($estado,'nombre_estado') ? 'nombre_estado' : 'nombre' => 'Abierto',
            'estado' => Schema::hasColumn($estado,'estado') ? 'activo' : null,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $priorId = DB::table($prioridad)->insertGetId([
            Schema::hasColumn($prioridad,'nombre_prioridad') ? 'nombre_prioridad' : 'nombre' => 'Media',
            'estado' => Schema::hasColumn($prioridad,'estado') ? 'activo' : null,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $uId = DB::table($usuario)->insertGetId(array_filter([
            Schema::hasColumn($usuario,'nombre') ? 'nombre' : 'nombre_usuario' => 'Tester',
            Schema::hasColumn($usuario,'email') ? 'email' : 'correo' => 'tester@socya.org.co',
            'fk_id_departamento' => Schema::hasColumn($usuario,'fk_id_departamento') ? $depA : null,
            'created_at' => $now, 'updated_at' => $now,
        ], function ($v) { return $v !== null; }));

        DB::table($tickets)->insert([
            'codigo' => 'TCK_A',
            'asunto' => 'Ticket A',
            'descripcion' => 'Prueba A',
            'fk_id_categoria' => $catA,
            'fk_id_prioridad' => $priorId,
            'fk_id_estado' => $estadoId,
            'fk_id_usuario_creador' => $uId,
            'fk_id_usuario_asignado' => $uId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table($tickets)->insert([
            'codigo' => 'TCK_B',
            'asunto' => 'Ticket B',
            'descripcion' => 'Prueba B',
            'fk_id_categoria' => $catB,
            'fk_id_prioridad' => $priorId,
            'fk_id_estado' => $estadoId,
            'fk_id_usuario_creador' => $uId,
            'fk_id_usuario_asignado' => $uId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = $this->withSession([
            'user_id' => $uId,
            'user_email' => 'tester@socya.org.co',
        ])->getJson('/api/dashboard/bars?period=year&group=month&limit=12');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('series', $json);
        $series = $json['series'];
        $this->assertIsArray($series);
        $this->assertNotEmpty($series);
        $data = $series[0]['data'] ?? [];
        $this->assertIsArray($data);
        $this->assertEquals(1, array_sum($data), 'Debe contar sólo tickets del departamento del usuario');
    }

    public function test_bars_returns_403_when_user_has_no_department(): void
    {
        $usuario = $this->tname('tbl_usuario');
        if (!Schema::hasTable($usuario)) {
            $this->markTestSkipped('Service desk tables are not present.');
            return;
        }
        $uId = DB::table($usuario)->insertGetId([
            Schema::hasColumn($usuario,'nombre') ? 'nombre' : 'nombre_usuario' => 'Tester 2',
            Schema::hasColumn($usuario,'email') ? 'email' : 'correo' => 'tester2@socya.org.co',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $response = $this->withSession([
            'user_id' => $uId,
            'user_email' => 'tester2@socya.org.co',
        ])->getJson('/api/dashboard/bars?period=year&group=month&limit=12');
        $response->assertStatus(403);
        $response->assertJson(['error' => 'department_not_assigned']);
    }
}

