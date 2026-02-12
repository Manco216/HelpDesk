<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    $allowed = env('ALLOWED_EMAIL_DOMAINS', 'socya.org.co');
    $email = (string)session('user_email', '');
    $has = session()->has('user_id');
    $ok = false;
    foreach (array_filter(array_map('trim', explode(',', $allowed))) as $d) {
        if (str_ends_with(strtolower($email), '@' . strtolower($d))) { $ok = true; break; }
    }
    if (!$has || !$ok) { return redirect()->route('login'); }
    return view('dashboard');
})->name('dashboard');

Route::get('/admin', function () {
    $allowed = env('ALLOWED_EMAIL_DOMAINS', 'socya.org.co');
    $email = (string)session('user_email', '');
    $has = session()->has('user_id');
    $ok = false;
    foreach (array_filter(array_map('trim', explode(',', $allowed))) as $d) {
        if (str_ends_with(strtolower($email), '@' . strtolower($d))) { $ok = true; break; }
    }
    if (!$has || !$ok) { return redirect()->route('login'); }
    return view('admin');
})->name('admin');

Route::get('/planificacion', function () {
    $allowed = env('ALLOWED_EMAIL_DOMAINS', 'socya.org.co');
    $email = (string)session('user_email', '');
    $has = session()->has('user_id');
    $ok = false;
    foreach (array_filter(array_map('trim', explode(',', $allowed))) as $d) {
        if (str_ends_with(strtolower($email), '@' . strtolower($d))) { $ok = true; break; }
    }
    if (!$has || !$ok) { return redirect()->route('login'); }
    return view('planificacion');
})->name('planificacion');

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/assets/img/{path}', function ($path) {
    $pub = public_path('img/' . $path);
    if (file_exists($pub)) {
        return response()->file($pub);
    }
    $res = resource_path('img/' . $path);
    if (file_exists($res)) {
        return response()->file($res);
    }
    return response()->noContent(404);
})->where('path', '.*')->name('asset.img');

Route::get('/api/processes', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $table = $t('tbl_proceso');
    if (!Schema::hasTable($table)) {
        return response()->json([], 200);
    }
    $nameCol = Schema::hasColumn($table, 'nombre_proceso') ? 'nombre_proceso' : 'nombre';
    $rows = DB::table($table)
        ->select('id_proceso', DB::raw("$nameCol as nombre_proceso"))
        ->orderBy($nameCol, 'asc')
        ->get();
    return response()->json($rows);
});

Route::get('/api/processes/{id}/categories', function ($id) {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $cat = $t('tbl_categoria');
    $dep = $t('tbl_departamento');
    $proc = $t('tbl_proceso');
    if (!Schema::hasTable($cat)) {
        return response()->json([], 200);
    }
    $nameCol = Schema::hasColumn($cat, 'nombre_categoria') ? "$cat.nombre_categoria" : "$cat.nombre";
    if (Schema::hasTable($proc) && Schema::hasColumn($cat, 'fk_id_proceso')) {
        $rows = DB::table($cat)
            ->where("$cat.fk_id_proceso", $id)
            ->select(DB::raw("$cat.id_categoria as id_categoria"), DB::raw("$nameCol as nombre_categoria"))
            ->orderBy($nameCol, 'asc')
            ->get();
    } else if (Schema::hasTable($dep) && Schema::hasColumn($cat, 'fk_id_departamento') && Schema::hasColumn($dep, 'fk_id_proceso')) {
        $rows = DB::table($cat)
            ->join($dep, "$cat.fk_id_departamento", '=', "$dep.id_departamento")
            ->where("$dep.fk_id_proceso", $id)
            ->select(DB::raw("$cat.id_categoria as id_categoria"), DB::raw("$nameCol as nombre_categoria"))
            ->orderBy($nameCol, 'asc')
            ->get();
    } else {
        $rows = [];
    }
    return response()->json($rows);
});

Route::post('/api/processes/{id}/categories/seed-default', function ($id) {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $proceso = $t('tbl_proceso');
    $departamento = $t('tbl_departamento');
    $usuario = $t('tbl_usuario');
    $rol = $t('tbl_rol');
    $categoria = $t('tbl_categoria');
    foreach ([$proceso,$usuario,$rol,$categoria] as $tb) {
        if (!Schema::hasTable($tb)) {
            return response()->json([], 200);
        }
    }
    $now = now();
    $proc = DB::table($proceso)->where('id_proceso', $id)->first();
    if (!$proc) {
        $id = DB::table($proceso)->insertGetId([
            'nombre' => 'Proceso ' . $id,
            'descripcion' => null,
            'estado' => 'activo',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $proc = DB::table($proceso)->where('id_proceso', $id)->first();
    }
    $deptId = null;
    if (Schema::hasTable($departamento)) {
        if (Schema::hasColumn($proceso, 'fk_id_departamento')) {
            $deptId = isset($proc->fk_id_departamento) ? (int)$proc->fk_id_departamento : null;
        }
        if ($deptId === null && Schema::hasColumn($departamento, 'fk_id_proceso')) {
            $deptRow = DB::table($departamento)->where('fk_id_proceso', $id)->orderBy('id_departamento', 'asc')->first();
            $deptId = $deptRow ? (int)$deptRow->id_departamento : null;
        }
        if ($deptId === null) {
            $deptId = DB::table($departamento)->insertGetId([
                'nombre' => 'General ' . (isset($proc->nombre) ? $proc->nombre : 'Proceso'),
                'codigo' => 'DEP_' . (string)$id,
                'descripcion' => null,
                'fk_id_padre' => null,
                'metadata' => json_encode([]),
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            if (Schema::hasColumn($proceso, 'fk_id_departamento')) {
                DB::table($proceso)->where('id_proceso', $id)->update(['fk_id_departamento' => $deptId]);
            }
        }
    }
    $adminRole = DB::table($rol)->where('nombre', 'admin')->first();
    $user = $adminRole ? DB::table($usuario)->where('fk_id_rol', $adminRole->id_rol)->first() : DB::table($usuario)->first();
    if (!$user) {
        $uId = DB::table($usuario)->insertGetId([
            'nombre' => 'Auto Admin',
            'email' => 'auto_admin_' . $id . '@servicedesk.test',
            'password' => Hash::make('password'),
            'fk_id_departamento' => $deptId,
            'fk_id_rol' => $adminRole ? $adminRole->id_rol : DB::table($rol)->insertGetId([
                'nombre' => 'admin',
                'descripcion' => 'Administrador',
                'permisos' => json_encode([]),
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ]),
            'preferencias' => json_encode([]),
            'estado' => 'activo',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $user = DB::table($usuario)->where('id_usuario', $uId)->first();
    }
    $created = [];
    $defs = [
        ['Incidencias','CAT_INC',8,24,$user->id_usuario],
        ['Accesos','CAT_ACC',8,24,$user->id_usuario],
        ['Mantenimientos','CAT_MNT',24,72,$user->id_usuario],
    ];
    foreach ($defs as $d) {
        [$name,$code,$slaR,$slaS,$uid] = $d;
        $nameCol = Schema::hasColumn($categoria,'nombre_categoria') ? 'nombre_categoria' : 'nombre';
        $exists = DB::table($categoria)->where($nameCol, $name)->when(Schema::hasColumn($categoria, 'fk_id_proceso'), function ($qq) use ($id) {
            return $qq->where('fk_id_proceso', (int)$id);
        }, function ($qq) use ($deptId) {
            return $qq->where('fk_id_departamento', (int)$deptId);
        })->exists();
        if (!$exists) {
            $payload = [
                'nombre' => $name,
                'codigo' => $code,
                'descripcion' => null,
                'sla_respuesta_horas' => $slaR,
                'sla_resolucion_horas' => $slaS,
                'fk_id_responsable' => $uid,
                'configuracion' => json_encode([]),
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn($categoria, 'fk_id_proceso')) {
                $payload['fk_id_proceso'] = (int)$id;
            } else {
                $payload['fk_id_departamento'] = (int)$deptId;
            }
            DB::table($categoria)->insert($payload);
            $created[] = $name;
        }
    }
    return response()->json(['process_id' => (int)$id, 'created' => $created], 201);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/api/tickets', function () {
    try {
        $prefix = env('DB_PREFIX', '');
        $t = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };
        $tickets = $t('tbl_tickets');
        $categoria = $t('tbl_categoria');
        $estado = $t('tbl_estado');
        $prioridad = $t('tbl_prioridad');
        $usuario = $t('tbl_usuario');
        $adjunto = $t('tbl_adjunto');
        foreach ([$tickets,$categoria,$estado,$prioridad,$usuario,$adjunto] as $tb) {
            if (!Schema::hasTable($tb)) {
                return response()->json(['error' => 'missing_table', 'table' => $tb], 422);
            }
        }
        $req = request();
        $catId = (int)($req->input('fk_id_categoria') ?? $req->input('category_id') ?? 0);
        $desc = (string)($req->input('descripcion') ?? $req->input('description') ?? '');
        if (!$catId || $desc === '') {
            return response()->json(['error' => 'invalid_payload'], 422);
        }
        $catRow = DB::table($categoria)->where('id_categoria', $catId)->first();
        if (!$catRow) {
            return response()->json(['error' => 'category_not_found'], 404);
        }
        $repCol = Schema::hasColumn($categoria, 'fk_id_responsable') ? 'fk_id_responsable' : (Schema::hasColumn($categoria, 'fk_id_usuarios') ? 'fk_id_usuarios' : null);
        $assignedId = (int)($repCol ? ($catRow->$repCol ?? 0) : 0);
        $estadoNameCol = Schema::hasColumn($estado, 'nombre_estado') ? 'nombre_estado' : 'nombre';
        $estadoOrderCol = Schema::hasColumn($estado, 'orden') ? 'orden' : null;
        $estadoRow = DB::table($estado)->where($estadoNameCol, 'Abierto')->first();
        if (!$estadoRow) {
            $estadoRow = DB::table($estado)->when($estadoOrderCol, function ($q) use ($estadoOrderCol) {
                return $q->orderBy($estadoOrderCol, 'asc');
            })->first();
        }
        if (!$estadoRow) {
            $payload = [
                $estadoNameCol => 'Abierto',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn($estado, 'color')) $payload['color'] = '#22c55e';
            if ($estadoOrderCol) $payload[$estadoOrderCol] = 1;
            if (Schema::hasColumn($estado, 'es_inicial')) $payload['es_inicial'] = true;
            if (Schema::hasColumn($estado, 'es_final')) $payload['es_final'] = false;
            if (Schema::hasColumn($estado, 'estado')) $payload['estado'] = 'activo';
            $eid = DB::table($estado)->insertGetId($payload);
            $estadoRow = DB::table($estado)->where('id_estado', $eid)->first();
        }
        $priorNameCol = Schema::hasColumn($prioridad, 'nombre_prioridad') ? 'nombre_prioridad' : 'nombre';
        $priorOrderCol = Schema::hasColumn($prioridad, 'orden') ? 'orden' : null;
        $priorRow = DB::table($prioridad)->where($priorNameCol, 'Media')->first();
        if (!$priorRow) {
            $priorRow = DB::table($prioridad)->when($priorOrderCol, function ($q) use ($priorOrderCol) {
                return $q->orderBy($priorOrderCol, 'asc');
            })->first();
        }
        if (!$priorRow) {
            $payload = [
                $priorNameCol => 'Media',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($priorOrderCol) $payload[$priorOrderCol] = 2;
            if (Schema::hasColumn($prioridad, 'color')) $payload['color'] = '#f59e0b';
            if (Schema::hasColumn($prioridad, 'sla_horas')) $payload['sla_horas'] = 8;
            if (Schema::hasColumn($prioridad, 'sla_resolucion_horas')) $payload['sla_resolucion_horas'] = 24;
            if (Schema::hasColumn($prioridad, 'estado')) $payload['estado'] = 'activo';
            $pid = DB::table($prioridad)->insertGetId($payload);
            $priorRow = DB::table($prioridad)->where('id_prioridad', $pid)->first();
        }
        $uid = (int)(session('user_id') ?? 0);
        $userRow = null;
        if ($uid > 0) {
            $userRow = DB::table($usuario)->where('id_usuario', $uid)->first();
        }
        if (!$userRow) {
            $rol = $t('tbl_rol');
            $rolNameCol = Schema::hasColumn($rol, 'nombre') ? 'nombre' : (Schema::hasColumn($rol, 'nombre_rol') ? 'nombre_rol' : null);
            $adminRole = $rolNameCol ? DB::table($rol)->where($rolNameCol, 'admin')->first() : null;
            $userRow = $adminRole ? DB::table($usuario)->where('fk_id_rol', $adminRole->id_rol)->first() : DB::table($usuario)->first();
            if (!$userRow) {
                return response()->json(['error' => 'user_not_found'], 500);
            }
        }
        $hasCode = Schema::hasColumn($tickets, 'codigo');
        $code = $hasCode ? ('T' . strtoupper(Str::random(10))) : null;
        $tries = 0;
        if ($hasCode) {
            while (DB::table($tickets)->where('codigo', $code)->exists() && $tries < 5) {
                $code = 'T' . strtoupper(Str::random(10));
                $tries++;
            }
        }
        $asunto = mb_substr($desc, 0, 120);
        if ($asunto === '') { $asunto = 'Solicitud'; }
        $now = now();
        $creatorCol = Schema::hasColumn($tickets, 'fk_id_usuario_creador') ? 'fk_id_usuario_creador'
            : (Schema::hasColumn($tickets, 'fk_id_usuario') ? 'fk_id_usuario'
            : (Schema::hasColumn($tickets, 'id_usuario_creador') ? 'id_usuario_creador' : null));
        $assignedCol = Schema::hasColumn($tickets, 'fk_id_usuario_asignado') ? 'fk_id_usuario_asignado'
            : (Schema::hasColumn($tickets, 'fk_id_usuario') ? 'fk_id_usuario'
            : (Schema::hasColumn($tickets, 'id_usuario_asignado') ? 'id_usuario_asignado' : null));
        $createdCol = Schema::hasColumn($tickets, 'created_at') ? 'created_at' : (Schema::hasColumn($tickets, 'fecha_creacion') ? 'fecha_creacion' : null);
        $payload = [
            'descripcion' => $desc,
            'fk_id_prioridad' => (int)$priorRow->id_prioridad,
            'fk_id_estado' => (int)$estadoRow->id_estado,
            'fk_id_categoria' => (int)$catId,
        ];
        if ($hasCode && $code !== null) $payload['codigo'] = $code;
        if (Schema::hasColumn($tickets, 'asunto')) $payload['asunto'] = $asunto;
        if ($creatorCol) $payload[$creatorCol] = (int)$userRow->id_usuario;
        if ($assignedCol) $payload[$assignedCol] = (int)($assignedId > 0 ? $assignedId : $userRow->id_usuario);
        if ($createdCol) $payload[$createdCol] = $now;
        if (Schema::hasColumn($tickets, 'updated_at')) $payload['updated_at'] = $now;
        $ticketId = DB::table($tickets)->insertGetId($payload);
        $files = $req->file('files');
        $saved = [];
        if ($files) {
            $fs = is_array($files) ? $files : [$files];
            foreach ($fs as $file) {
                if (!$file || !$file->isValid()) continue;
                $path = $file->store('adjuntos');
                $name = $file->getClientOriginalName();
                DB::table($adjunto)->insert([
                    'nombre_original' => $name,
                    'ruta' => $path,
                    'tipo_mime' => $file->getMimeType(),
                    'tamano_bytes' => (int)$file->getSize(),
                    'fk_id_ticket' => (int)$ticketId,
                    'fk_id_usuario' => (int)$userRow->id_usuario,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $saved[] = $name;
            }
        }
        return response()->json([
            'id_tickets' => (int)$ticketId,
            'adjuntos' => $saved,
        ], 201);
    } catch (\Throwable $e) {
        $dbg = (string)(request()->query('debug') ?? '');
        $payload = [
            'error' => 'exception',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
        return response()->json($payload, $dbg === '1' ? 200 : 500);
    }
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/api/tickets/search', function () {
    try {
        $prefix = env('DB_PREFIX', '');
        $t = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };
        $tickets = $t('tbl_tickets');
        $usuario = $t('tbl_usuario');
        $categoria = $t('tbl_categoria');
        $departamento = $t('tbl_departamento');
        $estado = $t('tbl_estado');
        $prioridad = $t('tbl_prioridad');
        $proceso = $t('tbl_proceso');
        foreach ([$tickets,$usuario,$categoria,$departamento,$estado,$prioridad,$proceso] as $tb) {
            if (!Schema::hasTable($tb)) {
                return response()->json([], 200);
            }
        }
        $req = request();
        $problemId = (int)($req->query('problem_id') ?? 0);
        $keywords = trim((string)($req->query('keywords') ?? ''));
        $reportedBy = trim((string)($req->query('reported_by') ?? ''));
        $reportedById = (int)($req->query('reported_by_id') ?? 0);
        $assignedTo = trim((string)($req->query('assigned_to') ?? ''));
        $categoryParam = trim((string)($req->query('category') ?? ''));
        $departmentParam = trim((string)($req->query('department') ?? ''));
        $statusParam = trim((string)($req->query('status') ?? ''));
        $priorityParam = trim((string)($req->query('priority') ?? ''));
        $categoryId = (int)($req->query('category_id') ?? 0);
        $departmentId = (int)($req->query('department_id') ?? 0);
        $statusId = (int)($req->query('status_id') ?? 0);
        $priorityId = (int)($req->query('priority_id') ?? 0);
        $dateFrom = (string)($req->query('date_from') ?? '');
        $dateTo = (string)($req->query('date_to') ?? '');
        $orderBy = (string)($req->query('order_by') ?? 'id');
        $statusMap = ['open' => 'Abierto','in_progress' => 'En progreso','closed' => 'Cerrado'];
        $priorityMap = ['low' => 'Baja','medium' => 'Media','high' => 'Alta'];
        $catNameCol = Schema::hasColumn($categoria, 'nombre_categoria') ? "$categoria.nombre_categoria" : "$categoria.nombre";
        $deptNameCol = Schema::hasColumn($departamento, 'nombre_departamento') ? "$departamento.nombre_departamento" : "$departamento.nombre";
        $estadoNameCol = Schema::hasColumn($estado, 'nombre_estado') ? "$estado.nombre_estado" : "$estado.nombre";
        $priorNameCol = Schema::hasColumn($prioridad, 'nombre_prioridad') ? "$prioridad.nombre_prioridad" : "$prioridad.nombre";
        $deptProcFk = Schema::hasColumn($departamento, 'fk_id_proceso') ? "$departamento.fk_id_proceso" : null;
        $procNameCol = Schema::hasTable($proceso) && $deptProcFk
            ? (Schema::hasColumn($proceso, 'nombre') ? "$proceso.nombre" : (Schema::hasColumn($proceso, 'nombre_proceso') ? "$proceso.nombre_proceso" : "$proceso.nombre"))
            : "NULL";
        $reporterNameCol = Schema::hasColumn($usuario, 'nombre') ? 'reporter.nombre' : 'reporter.nombre_usuario';
        $reporterEmailCol = Schema::hasColumn($usuario, 'email') ? 'reporter.email' : 'reporter.correo';
        $assignedNameCol = Schema::hasColumn($usuario, 'nombre') ? 'assigned.nombre' : 'assigned.nombre_usuario';
        $creatorCol = Schema::hasColumn($tickets, 'fk_id_usuario_creador') ? 'fk_id_usuario_creador'
            : (Schema::hasColumn($tickets, 'fk_id_usuario') ? 'fk_id_usuario'
            : (Schema::hasColumn($tickets, 'id_usuario_creador') ? 'id_usuario_creador' : null));
        $assignedCol = Schema::hasColumn($tickets, 'fk_id_usuario_asignado') ? 'fk_id_usuario_asignado'
            : (Schema::hasColumn($tickets, 'fk_id_usuario') ? 'fk_id_usuario'
            : (Schema::hasColumn($tickets, 'id_usuario_asignado') ? 'id_usuario_asignado' : null));
        $createdCol = Schema::hasColumn($tickets, 'created_at')
            ? "$tickets.created_at"
            : (Schema::hasColumn($tickets, 'fecha_creacion') ? "$tickets.fecha_creacion" : null);
        $q = DB::table($tickets)
            ->leftJoin($usuario . ' as reporter', $creatorCol ? "$tickets.$creatorCol" : DB::raw('NULL'), '=', "reporter.id_usuario")
            ->leftJoin($usuario . ' as assigned', $assignedCol ? "$tickets.$assignedCol" : DB::raw('NULL'), '=', "assigned.id_usuario")
            ->leftJoin($categoria, "$tickets.fk_id_categoria", '=', "$categoria.id_categoria")
            ->when(Schema::hasTable($proceso) && Schema::hasColumn($categoria, 'fk_id_proceso'), function ($qq) use ($categoria, $proceso, $departamento) {
                $procDeptFk = Schema::hasColumn($proceso, 'fk_id_departamento') ? "$proceso.fk_id_departamento" : null;
                $qq = $qq->leftJoin($proceso, "$categoria.fk_id_proceso", '=', "$proceso.id_proceso");
                if ($procDeptFk) {
                    $qq = $qq->leftJoin($departamento, $procDeptFk, '=', "$departamento.id_departamento");
                }
                return $qq;
            }, function ($qq) use ($categoria, $departamento, $proceso, $deptProcFk) {
                $qq = $qq->leftJoin($departamento, "$categoria.fk_id_departamento", '=', "$departamento.id_departamento");
                if ($deptProcFk) {
                    $qq = $qq->leftJoin($proceso, $deptProcFk, '=', "$proceso.id_proceso");
                }
                return $qq;
            })
            ->leftJoin($estado, "$tickets.fk_id_estado", '=', "$estado.id_estado")
            ->leftJoin($prioridad, "$tickets.fk_id_prioridad", '=', "$prioridad.id_prioridad");
        if ($problemId > 0) {
            $q->where("$tickets.id_tickets", $problemId);
        }
        if ($keywords !== '') {
            $q->where(function ($qq) use ($tickets, $keywords) {
                $qq->where("$tickets.descripcion", 'like', '%' . $keywords . '%')
                   ->orWhere("$tickets.asunto", 'like', '%' . $keywords . '%');
            });
        }
        if ($reportedById > 0 && $creatorCol) {
            $q->where("$tickets.$creatorCol", $reportedById);
        } else if ($reportedBy !== '') {
            if (strtolower($reportedBy) === 'me') {
                $uid = (int)(session('user_id') ?? 0);
                if ($uid > 0 && $creatorCol) {
                    $q->where("$tickets.$creatorCol", $uid);
                }
            } else {
                $q->where(function ($qq) use ($reportedBy, $reporterNameCol, $reporterEmailCol) {
                    $qq->where(DB::raw($reporterNameCol), 'like', '%' . $reportedBy . '%')
                       ->orWhere(DB::raw($reporterEmailCol), 'like', '%' . $reportedBy . '%');
                });
            }
        }
        if ($assignedTo !== '') {
            if ($assignedTo === 'me') {
                $uid = (int)(session('user_id') ?? 0);
                if ($uid > 0 && $assignedCol) {
                    $q->where("$tickets.$assignedCol", $uid);
                }
            } else if ($assignedTo === 'team') {
                $uid = (int)(session('user_id') ?? 0);
                if ($uid > 0) {
                    $u = DB::table($usuario)->where('id_usuario', $uid)->first();
                    if ($u && isset($u->fk_id_departamento) && Schema::hasColumn($usuario, 'fk_id_departamento')) {
                        $q->where("assigned.fk_id_departamento", (int)$u->fk_id_departamento);
                    }
                }
            } else if (ctype_digit($assignedTo)) {
                $uid = (int)$assignedTo;
                if ($uid > 0 && $assignedCol) {
                    $q->where("$tickets.$assignedCol", $uid);
                }
            }
        }
        if ($categoryId > 0) {
            $q->where("$categoria.id_categoria", $categoryId);
        } else if ($categoryParam !== '') {
            $q->where(DB::raw($catNameCol), 'like', '%' . $categoryParam . '%');
        }
        if ($departmentId > 0) {
            $q->where("$departamento.id_departamento", $departmentId);
        } else if ($departmentParam !== '') {
            $q->where(DB::raw($deptNameCol), 'like', '%' . $departmentParam . '%');
        }
        if ($statusId > 0) {
            $q->where("$estado.id_estado", $statusId);
        } else if ($statusParam !== '') {
            $st = $statusMap[strtolower($statusParam)] ?? $statusParam;
            $q->where(DB::raw($estadoNameCol), $st);
        }
        if ($priorityId > 0) {
            $q->where("$prioridad.id_prioridad", $priorityId);
        } else if ($priorityParam !== '') {
            $pr = $priorityMap[strtolower($priorityParam)] ?? $priorityParam;
            $q->where(DB::raw($priorNameCol), $pr);
        }
        if ($dateFrom !== '' && $createdCol !== null) {
            $q->whereDate(DB::raw($createdCol), '>=', $dateFrom);
        }
        if ($dateTo !== '' && $createdCol !== null) {
            $q->whereDate(DB::raw($createdCol), '<=', $dateTo);
        }
        if ($orderBy === 'id') {
            $q->orderBy("$tickets.id_tickets", 'desc');
        } else if ($orderBy === 'created_at') {
            if ($createdCol !== null) {
                $q->orderBy(DB::raw($createdCol), 'desc');
            } else {
                $q->orderBy("$tickets.id_tickets", 'desc');
            }
        } else if ($orderBy === 'priority') {
            if (Schema::hasColumn($prioridad, 'orden')) {
                $q->orderBy("$prioridad.orden", 'desc');
            } else {
                $q->orderBy("$tickets.id_tickets", 'desc');
            }
        } else if ($orderBy === 'status') {
            if (Schema::hasColumn($estado, 'orden')) {
                $q->orderBy("$estado.orden", 'asc');
            } else {
                $q->orderBy("$tickets.id_tickets", 'desc');
            }
        } else {
            $q->orderBy("$tickets.id_tickets", 'desc');
        }
        $fechaSel = $createdCol !== null ? DB::raw("$createdCol as fecha_creacion") : DB::raw("NULL as fecha_creacion");
        $rows = $q->select(
            "$tickets.id_tickets as id",
            "$tickets.descripcion as descripcion",
            $fechaSel,
            DB::raw("$estadoNameCol as estado"),
            DB::raw("$priorNameCol as prioridad"),
            DB::raw("$catNameCol as categoria"),
            DB::raw("$deptNameCol as departamento"),
            DB::raw("$assignedNameCol as assigned_to"),
            DB::raw("$procNameCol as proceso"),
            DB::raw("$reporterNameCol as usuario"),
            DB::raw("$reporterEmailCol as correo")
        )->limit(200)->get();
        return response()->json($rows, 200);
    } catch (\Throwable $e) {
        $dbg = (string)(request()->query('debug') ?? '');
        $payload = [
            'error' => 'exception',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
        return response()->json($payload, $dbg === '1' ? 200 : 500);
    }
});
Route::get('/api/tickets/{id}', function ($id) {
    try {
        $prefix = env('DB_PREFIX', '');
        $t = function(string $nm) use ($prefix) {
            return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
        };
        $tickets = $t('tbl_tickets');
        $usuario = $t('tbl_usuario');
        $categoria = $t('tbl_categoria');
        $departamento = $t('tbl_departamento');
        $estado = $t('tbl_estado');
        $prioridad = $t('tbl_prioridad');
        $adjunto = $t('tbl_adjunto');
        $proceso = $t('tbl_proceso');
        foreach ([$tickets,$usuario,$categoria,$departamento,$estado,$prioridad,$adjunto,$proceso] as $tb) {
            if (!Schema::hasTable($tb)) {
                return response()->json(['error' => 'missing_table'], 422);
            }
        }
        $createdCol = Schema::hasColumn($tickets, 'created_at') ? "$tickets.created_at" : (Schema::hasColumn($tickets, 'fecha_creacion') ? "$tickets.fecha_creacion" : null);
        $catNameCol = Schema::hasColumn($categoria, 'nombre_categoria') ? "$categoria.nombre_categoria" : "$categoria.nombre";
        $deptNameCol = Schema::hasColumn($departamento, 'nombre_departamento') ? "$departamento.nombre_departamento" : "$departamento.nombre";
        $estadoNameCol = Schema::hasColumn($estado, 'nombre_estado') ? "$estado.nombre_estado" : "$estado.nombre";
        $priorNameCol = Schema::hasColumn($prioridad, 'nombre_prioridad') ? "$prioridad.nombre_prioridad" : "$prioridad.nombre";
        $procNameCol = Schema::hasColumn($proceso, 'nombre') ? "$proceso.nombre" : (Schema::hasColumn($proceso, 'nombre_proceso') ? "$proceso.nombre_proceso" : "$proceso.nombre");
        $reporterNameCol = Schema::hasColumn($usuario, 'nombre') ? 'reporter.nombre' : 'reporter.nombre_usuario';
        $reporterEmailCol = Schema::hasColumn($usuario, 'email') ? 'reporter.email' : 'reporter.correo';
        $assignedNameCol = Schema::hasColumn($usuario, 'nombre') ? 'assigned.nombre' : 'assigned.nombre_usuario';
        $creatorCol = Schema::hasColumn($tickets, 'fk_id_usuario_creador') ? 'fk_id_usuario_creador' : (Schema::hasColumn($tickets, 'fk_id_usuario') ? 'fk_id_usuario' : (Schema::hasColumn($tickets, 'id_usuario_creador') ? 'id_usuario_creador' : null));
        $assignedCol = Schema::hasColumn($tickets, 'fk_id_usuario_asignado') ? 'fk_id_usuario_asignado' : (Schema::hasColumn($tickets, 'fk_id_usuario') ? 'fk_id_usuario' : (Schema::hasColumn($tickets, 'id_usuario_asignado') ? 'id_usuario_asignado' : null));
        $deptProcFk = Schema::hasColumn($departamento, 'fk_id_proceso') ? "$departamento.fk_id_proceso" : null;
        $query = DB::table($tickets)
            ->when($creatorCol, function ($qq) use ($tickets, $creatorCol, $usuario) {
                return $qq->leftJoin($usuario . ' as reporter', "$tickets.$creatorCol", '=', "reporter.id_usuario");
            }, function ($qq) {
                return $qq;
            })
            ->when($assignedCol, function ($qq) use ($tickets, $assignedCol, $usuario) {
                return $qq->leftJoin($usuario . ' as assigned', "$tickets.$assignedCol", '=', "assigned.id_usuario");
            }, function ($qq) {
                return $qq;
            })
            ->leftJoin($categoria, "$tickets.fk_id_categoria", '=', "$categoria.id_categoria")
            ->when(Schema::hasTable($proceso) && Schema::hasColumn($categoria, 'fk_id_proceso'), function ($qq) use ($categoria, $proceso, $departamento) {
                $procDeptFk = Schema::hasColumn($proceso, 'fk_id_departamento') ? "$proceso.fk_id_departamento" : null;
                $qq = $qq->leftJoin($proceso, "$categoria.fk_id_proceso", '=', "$proceso.id_proceso");
                if ($procDeptFk) {
                    $qq = $qq->leftJoin($departamento, $procDeptFk, '=', "$departamento.id_departamento");
                }
                return $qq;
            }, function ($qq) use ($categoria, $departamento, $proceso, $deptProcFk) {
                $qq = $qq->leftJoin($departamento, "$categoria.fk_id_departamento", '=', "$departamento.id_departamento");
                if ($deptProcFk) {
                    $qq = $qq->leftJoin($proceso, $deptProcFk, '=', "$proceso.id_proceso");
                }
                return $qq;
            })
            ->leftJoin($estado, "$tickets.fk_id_estado", '=', "$estado.id_estado")
            ->leftJoin($prioridad, "$tickets.fk_id_prioridad", '=', "$prioridad.id_prioridad")
            ->where("$tickets.id_tickets", (int)$id);
        $fechaSel = $createdCol ? DB::raw("$createdCol as fecha_creacion") : DB::raw("NULL as fecha_creacion");
        $procIdSel = Schema::hasColumn($categoria, 'fk_id_proceso')
            ? DB::raw("$categoria.fk_id_proceso as process_id")
            : ($deptProcFk ? DB::raw("$deptProcFk as process_id") : DB::raw("NULL as process_id"));
        $row = $query->select(
                "$tickets.id_tickets as id",
                "$tickets.descripcion as descripcion",
                "$tickets.fk_id_categoria as category_id",
                $fechaSel,
                $procIdSel,
                DB::raw("$estadoNameCol as estado"),
                DB::raw("$priorNameCol as prioridad"),
                DB::raw("$catNameCol as categoria"),
                DB::raw("$deptNameCol as departamento"),
                DB::raw("$assignedNameCol as assigned_to"),
                DB::raw("$procNameCol as proceso"),
                DB::raw("$reporterNameCol as usuario"),
                DB::raw("$reporterEmailCol as correo")
            )->first();
        if (!$row) {
            return response()->json(['error' => 'not_found'], 404);
        }
        $files = DB::table($adjunto)
            ->where('fk_id_ticket', (int)$id)
            ->orderBy('created_at', 'desc')
            ->select('nombre_original', 'ruta', 'tipo_mime', 'created_at')
            ->get()
            ->map(function ($f) {
                return [
                    'name' => $f->nombre_original,
                    'type' => $f->tipo_mime,
                    'date' => $f->created_at,
                    'url' => url('/assets/adjuntos/' . basename($f->ruta)),
                ];
            });
        $out = (array)$row;
        $out['attachments'] = $files;
        return response()->json($out, 200);
    } catch (\Throwable $e) {
        $dbg = (string)(request()->query('debug') ?? '');
        $payload = [
            'error' => 'exception',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
        return response()->json($payload, $dbg === '1' ? 200 : 500);
    }
});

Route::put('/api/tickets/{id}', function ($id) {
    return response()->json(['error' => 'editing_disabled'], 405);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/assets/adjuntos/{file}', function ($file) {
    $path = storage_path('app/adjuntos/' . $file);
    if (!file_exists($path)) {
        return response()->noContent(404);
    }
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $imageExts = ['png','jpg','jpeg','gif','webp','bmp','svg'];
    $mime = \Illuminate\Support\Facades\File::mimeType($path);
    if (in_array($ext, $imageExts)) {
        return response()->file($path, $mime ? ['Content-Type' => $mime] : []);
    }
    return response()->download($path, $file, $mime ? ['Content-Type' => $mime] : []);
})->where('file', '.*');
Route::get('/api/catalog/categories', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $cat = $t('tbl_categoria');
    if (!Schema::hasTable($cat)) {
        return response()->json([], 200);
    }
    $req = request();
    $q = trim((string)($req->query('q') ?? ''));
    try {
        $rows = Cache::remember('catalog:categories:' . $prefix . ':' . md5($q), 300, function () use ($cat, $q) {
            $nameCol = Schema::hasColumn($cat, 'nombre_categoria') ? 'nombre_categoria' : 'nombre';
            $builder = DB::table($cat)
                ->select('id_categoria', DB::raw("$nameCol as nombre_categoria"));
            if ($q !== '') {
                $builder->where($nameCol, 'like', '%' . $q . '%');
            }
            return $builder->orderBy($nameCol, 'asc')->limit(500)->get();
        });
        return response()->json($rows, 200);
    } catch (\Throwable $e) {
        $nameCol = Schema::hasColumn($cat, 'nombre_categoria') ? 'nombre_categoria' : 'nombre';
        $builder = DB::table($cat)
            ->select('id_categoria', DB::raw("$nameCol as nombre_categoria"));
        if ($q !== '') {
            $builder->where($nameCol, 'like', '%' . $q . '%');
        }
        $rows = $builder->orderBy($nameCol, 'asc')->limit(500)->get();
        return response()->json($rows, 200);
    }
});
Route::get('/api/catalog/departments', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $dept = $t('tbl_departamento');
    if (!Schema::hasTable($dept)) {
        return response()->json([], 200);
    }
    $req = request();
    $q = trim((string)($req->query('q') ?? ''));
    try {
        $rows = Cache::remember('catalog:departments:' . $prefix . ':' . md5($q), 300, function () use ($dept, $q) {
            $nameCol = Schema::hasColumn($dept, 'nombre_departamento') ? 'nombre_departamento' : 'nombre';
            $builder = DB::table($dept)
                ->select('id_departamento', DB::raw("$nameCol as nombre_departamento"));
            if ($q !== '') {
                $builder->where($nameCol, 'like', '%' . $q . '%');
            }
            return $builder->orderBy($nameCol, 'asc')->limit(500)->get();
        });
        return response()->json($rows, 200);
    } catch (\Throwable $e) {
        $nameCol = Schema::hasColumn($dept, 'nombre_departamento') ? 'nombre_departamento' : 'nombre';
        $builder = DB::table($dept)
            ->select('id_departamento', DB::raw("$nameCol as nombre_departamento"));
        if ($q !== '') {
            $builder->where($nameCol, 'like', '%' . $q . '%');
        }
        $rows = $builder->orderBy($nameCol, 'asc')->limit(500)->get();
        return response()->json($rows, 200);
    }
});
Route::get('/api/catalog/statuses', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $estado = $t('tbl_estado');
    if (!Schema::hasTable($estado)) {
        return response()->json([], 200);
    }
    try {
        $rows = Cache::remember('catalog:statuses:' . $prefix, 300, function () use ($estado) {
            $nameCol = Schema::hasColumn($estado, 'nombre_estado') ? 'nombre_estado' : 'nombre';
            return DB::table($estado)
                ->select('id_estado', DB::raw("$nameCol as nombre_estado"))
                ->orderBy($nameCol, 'asc')
                ->get();
        });
        return response()->json($rows, 200);
    } catch (\Throwable $e) {
        $nameCol = Schema::hasColumn($estado, 'nombre_estado') ? 'nombre_estado' : 'nombre';
        $rows = DB::table($estado)
            ->select('id_estado', DB::raw("$nameCol as nombre_estado"))
            ->orderBy($nameCol, 'asc')
            ->get();
        return response()->json($rows, 200);
    }
});
Route::get('/api/catalog/priorities', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $prioridad = $t('tbl_prioridad');
    if (!Schema::hasTable($prioridad)) {
        return response()->json([], 200);
    }
    try {
        $rows = Cache::remember('catalog:priorities:' . $prefix, 300, function () use ($prioridad) {
            $nameCol = Schema::hasColumn($prioridad, 'nombre_prioridad') ? 'nombre_prioridad' : 'nombre';
            return DB::table($prioridad)
                ->select('id_prioridad', DB::raw("$nameCol as nombre_prioridad"))
                ->orderBy($nameCol, 'asc')
                ->get();
        });
        return response()->json($rows, 200);
    } catch (\Throwable $e) {
        $nameCol = Schema::hasColumn($prioridad, 'nombre_prioridad') ? 'nombre_prioridad' : 'nombre';
        $rows = DB::table($prioridad)
            ->select('id_prioridad', DB::raw("$nameCol as nombre_prioridad"))
            ->orderBy($nameCol, 'asc')
            ->get();
        return response()->json($rows, 200);
    }
});
Route::get('/api/catalog/roles', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $rol = $t('tbl_rol');
    if (!Schema::hasTable($rol)) {
        return response()->json([], 200);
    }
    try {
        $rows = Cache::remember('catalog:roles:' . $prefix, 300, function () use ($rol) {
            $nameCol = Schema::hasColumn($rol, 'nombre') ? 'nombre' : 'nombre';
            return DB::table($rol)
                ->select('id_rol', DB::raw("$nameCol as nombre_rol"))
                ->orderBy($nameCol, 'asc')
                ->get();
        });
        return response()->json($rows, 200);
    } catch (\Throwable $e) {
        $nameCol = Schema::hasColumn($rol, 'nombre') ? 'nombre' : 'nombre';
        $rows = DB::table($rol)
            ->select('id_rol', DB::raw("$nameCol as nombre_rol"))
            ->orderBy($nameCol, 'asc')
            ->get();
        return response()->json($rows, 200);
    }
});

Route::get('/api/admin/categories', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json([], 403);
    }
    $cat = $t('tbl_categoria');
    $usuario = $t('tbl_usuario');
    if (!Schema::hasTable($cat) || !Schema::hasTable($usuario)) {
        return response()->json([], 200);
    }
    $nameCol = Schema::hasColumn($cat, 'nombre_categoria') ? "$cat.nombre_categoria" : "$cat.nombre";
    $userNameCol = Schema::hasColumn($usuario, 'nombre') ? 'rep.nombre' : 'rep.nombre_usuario';
    $fkCol = Schema::hasColumn($cat, 'fk_id_responsable') ? "$cat.fk_id_responsable"
        : (Schema::hasColumn($cat, 'fk_id_usuarios') ? "$cat.fk_id_usuarios"
        : (Schema::hasColumn($cat, 'fk_id_usuario') ? "$cat.fk_id_usuario" : null));
    $q = DB::table($cat);
    if ($fkCol !== null) {
        $q = $q->leftJoin($usuario . ' as rep', $fkCol, '=', 'rep.id_usuario');
    }
    $rows = $q->select(
        "$cat.id_categoria as id",
        DB::raw("$nameCol as nombre_categoria"),
        DB::raw(($fkCol !== null) ? "$fkCol as rep_id" : "NULL as rep_id"),
        DB::raw(($fkCol !== null) ? "$userNameCol as rep_nombre" : "NULL as rep_nombre")
    )->orderBy(DB::raw($nameCol), 'asc')->limit(1000)->get();
    return response()->json($rows, 200);
});
Route::get('/api/admin/categories/{id}', function ($id) {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $cat = $t('tbl_categoria');
    $dept = $t('tbl_departamento');
    $ans = $t('tbl_ans');
    $tarea = $t('tbl_tarea');
    if (!Schema::hasTable($cat)) {
        return response()->json(['error' => 'missing_table'], 422);
    }
    $row = DB::table($cat)->where('id_categoria', (int)$id)->first();
    if (!$row) {
        return response()->json(['error' => 'not_found'], 404);
    }
    $nameCol = Schema::hasColumn($cat, 'nombre_categoria') ? 'nombre_categoria' : 'nombre';
    $siglasCol = Schema::hasColumn($cat, 'siglas') ? 'siglas' : null;
    $deptCol = Schema::hasColumn($cat, 'fk_id_departamento') ? 'fk_id_departamento' : null;
    $ansCol = Schema::hasColumn($cat, 'fk_id_ans') ? 'fk_id_ans' : null;
    $tareaCol = Schema::hasColumn($cat, 'fk_id_tarea') ? 'fk_id_tarea' : null;
    $repCol = Schema::hasColumn($cat, 'fk_id_responsable') ? 'fk_id_responsable'
        : (Schema::hasColumn($cat, 'fk_id_usuarios') ? 'fk_id_usuarios'
        : (Schema::hasColumn($cat, 'fk_id_usuario') ? 'fk_id_usuario' : null));
    $payload = [
        'id' => (int)$row->id_categoria,
        'nombre' => (string)($row->$nameCol ?? ''),
        'siglas' => $siglasCol ? (string)($row->$siglasCol ?? '') : '',
        'departamento_id' => $deptCol ? (int)($row->$deptCol ?? 0) : 0,
        'ans_id' => $ansCol ? (int)($row->$ansCol ?? 0) : 0,
        'tarea_id' => $tareaCol ? (int)($row->$tareaCol ?? 0) : 0,
        'representante_id' => $repCol ? (int)($row->$repCol ?? 0) : 0,
    ];
    return response()->json($payload, 200);
});
Route::post('/api/admin/categories', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $cat = $t('tbl_categoria');
    $dept = $t('tbl_departamento');
    $area = $t('tbl_area');
    $ans = $t('tbl_ans');
    $tarea = $t('tbl_tarea');
    $usuario = $t('tbl_usuario');
    foreach ([$cat,$dept,$usuario] as $tb) {
        if (!Schema::hasTable($tb)) {
            return response()->json(['error' => 'missing_table', 'table' => $tb], 422);
        }
    }
    $req = request();
    $nombre = trim((string)($req->input('nombre') ?? ''));
    $siglas = trim((string)($req->input('siglas') ?? ''));
    $departamentoId = (int)($req->input('departamento_id') ?? 0);
    $ansId = (int)($req->input('ans_id') ?? 0);
    $tareaId = (int)($req->input('tarea_id') ?? 0);
    $repId = (int)($req->input('representante_id') ?? 0);
    if ($nombre === '' || $departamentoId <= 0) {
        return response()->json(['error' => 'invalid_payload'], 422);
    }
    $areaId = 0;
    if (Schema::hasTable($dept)) {
        $areaId = (int)(DB::table($dept)->where('id_departamento', $departamentoId)->value('fk_id_area') ?? 0);
    }
    $nameCol = Schema::hasColumn($cat, 'nombre_categoria') ? 'nombre_categoria' : 'nombre';
    $repCol = Schema::hasColumn($cat, 'fk_id_responsable') ? 'fk_id_responsable'
        : (Schema::hasColumn($cat, 'fk_id_usuarios') ? 'fk_id_usuarios'
        : (Schema::hasColumn($cat, 'fk_id_usuario') ? 'fk_id_usuario' : null));
    $data = [
        $nameCol => $nombre,
    ];
    if ($siglas !== '' && Schema::hasColumn($cat, 'siglas')) { $data['siglas'] = $siglas; }
    if ($repCol !== null && $repId > 0) { $data[$repCol] = $repId; }
    if ($departamentoId > 0 && Schema::hasColumn($cat, 'fk_id_departamento')) { $data['fk_id_departamento'] = $departamentoId; }
    if ($areaId > 0 && Schema::hasColumn($cat, 'fk_id_area')) { $data['fk_id_area'] = $areaId; }
    if ($ansId > 0 && Schema::hasTable($ans) && Schema::hasColumn($cat, 'fk_id_ans')) { $data['fk_id_ans'] = $ansId; }
    if ($tareaId > 0 && Schema::hasTable($tarea) && Schema::hasColumn($cat, 'fk_id_tarea')) { $data['fk_id_tarea'] = $tareaId; }
    $now = now();
    if (Schema::hasColumn($cat, 'fecha_creacion')) { $data['fecha_creacion'] = $now; }
    if (Schema::hasColumn($cat, 'fecha_actualizacion')) { $data['fecha_actualizacion'] = $now; }
    if (Schema::hasColumn($cat, 'created_at')) { $data['created_at'] = $now; }
    if (Schema::hasColumn($cat, 'updated_at')) { $data['updated_at'] = $now; }
    if (Schema::hasColumn($cat, 'estado')) { $data['estado'] = 'activo'; }
    if (Schema::hasColumn($cat, 'fase_categoria')) { $data['fase_categoria'] = true; }
    $id = DB::table($cat)->insertGetId($data);
    return response()->json(['id' => $id], 201);
})->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
Route::put('/api/admin/categories/{id}', function ($id) {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $cat = $t('tbl_categoria');
    $dept = $t('tbl_departamento');
    $ans = $t('tbl_ans');
    $tarea = $t('tbl_tarea');
    if (!Schema::hasTable($cat)) {
        return response()->json(['error' => 'missing_table'], 422);
    }
    $exists = DB::table($cat)->where('id_categoria', (int)$id)->exists();
    if (!$exists) {
        return response()->json(['error' => 'not_found'], 404);
    }
    $req = request();
    $nombre = trim((string)($req->input('nombre') ?? ''));
    $siglas = trim((string)($req->input('siglas') ?? ''));
    $departamentoId = (int)($req->input('departamento_id') ?? 0);
    $ansId = (int)($req->input('ans_id') ?? 0);
    $tareaId = (int)($req->input('tarea_id') ?? 0);
    $repId = (int)($req->input('representante_id') ?? 0);
    if ($nombre === '' || $departamentoId <= 0) {
        return response()->json(['error' => 'invalid_payload'], 422);
    }
    $areaId = 0;
    if (Schema::hasTable($dept)) {
        $areaId = (int)(DB::table($dept)->where('id_departamento', $departamentoId)->value('fk_id_area') ?? 0);
    }
    $nameCol = Schema::hasColumn($cat, 'nombre_categoria') ? 'nombre_categoria' : 'nombre';
    $repCol = Schema::hasColumn($cat, 'fk_id_responsable') ? 'fk_id_responsable'
        : (Schema::hasColumn($cat, 'fk_id_usuarios') ? 'fk_id_usuarios'
        : (Schema::hasColumn($cat, 'fk_id_usuario') ? 'fk_id_usuario' : null));
    $upd = [
        $nameCol => $nombre,
    ];
    if ($siglas !== '' && Schema::hasColumn($cat, 'siglas')) { $upd['siglas'] = $siglas; }
    if ($repCol !== null) { $upd[$repCol] = $repId > 0 ? $repId : null; }
    if (Schema::hasColumn($cat, 'fk_id_departamento')) { $upd['fk_id_departamento'] = $departamentoId; }
    if (Schema::hasColumn($cat, 'fk_id_area')) { $upd['fk_id_area'] = $areaId; }
    if (Schema::hasColumn($cat, 'fk_id_ans')) { $upd['fk_id_ans'] = $ansId; }
    if (Schema::hasColumn($cat, 'fk_id_tarea')) { $upd['fk_id_tarea'] = $tareaId; }
    $now = now();
    if (Schema::hasColumn($cat, 'fecha_actualizacion')) { $upd['fecha_actualizacion'] = $now; }
    if (Schema::hasColumn($cat, 'updated_at')) { $upd['updated_at'] = $now; }
    DB::table($cat)->where('id_categoria', (int)$id)->update($upd);
    return response()->json(['id' => (int)$id, 'updated' => true], 200);
})->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

Route::get('/api/admin/users', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $usuario = $t('tbl_usuario');
    $rol = $t('tbl_rol');
    if (!Schema::hasTable($usuario)) {
        return response()->json([], 200);
    }
    $nameCol = Schema::hasColumn($usuario, 'nombre') ? "$usuario.nombre" : "$usuario.nombre_usuario";
    $emailCol = Schema::hasColumn($usuario, 'email') ? "$usuario.email" : "$usuario.correo";
    $hasRolFk = Schema::hasColumn($usuario, 'fk_id_rol');
    $query = DB::table($usuario);
    if ($hasRolFk && Schema::hasTable($rol)) {
        $query = $query->leftJoin($rol, "$usuario.fk_id_rol", '=', "$rol.id_rol");
    }
    $rows = $query->select(
            "$usuario.id_usuario as id",
            DB::raw("$nameCol as nombre_usuario"),
            DB::raw("$emailCol as email"),
            $hasRolFk && Schema::hasTable($rol) ? DB::raw("$rol.nombre as rol_nombre") : DB::raw("NULL as rol_nombre")
        )
        ->orderBy(DB::raw($nameCol), 'asc')
        ->limit(1000)
        ->get();
    return response()->json($rows, 200);
});
Route::get('/api/admin/users/{id}', function ($id) {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $usuario = $t('tbl_usuario');
    $rol = $t('tbl_rol');
    if (!Schema::hasTable($usuario)) {
        return response()->json(['error' => 'missing_table'], 422);
    }
    $query = DB::table($usuario);
    if (Schema::hasTable($rol) && Schema::hasColumn($usuario, 'fk_id_rol')) {
        $query = $query->leftJoin($rol, "$usuario.fk_id_rol", '=', "$rol.id_rol");
    }
    $row = $query->where("$usuario.id_usuario", (int)$id)->select("$usuario.*", Schema::hasTable($rol) ? DB::raw("$rol.nombre as rol_nombre") : DB::raw("NULL as rol_nombre"))->first();
    if (!$row) {
        return response()->json(['error' => 'not_found'], 404);
    }
    $nameCol = Schema::hasColumn($usuario, 'nombre') ? 'nombre' : 'nombre_usuario';
    $emailCol = Schema::hasColumn($usuario, 'email') ? 'email' : 'correo';
    $estadoCol = Schema::hasColumn($usuario, 'estado') ? 'estado' : null;
    $rolCol = Schema::hasColumn($usuario, 'fk_id_rol') ? 'fk_id_rol' : null;
    $payload = [
        'id' => (int)$row->id_usuario,
        'nombre' => (string)($row->$nameCol ?? ''),
        'email' => (string)($row->$emailCol ?? ''),
        'estado' => $estadoCol ? (string)($row->$estadoCol ?? '') : '',
        'rol_id' => $rolCol ? (int)($row->$rolCol ?? 0) : 0,
        'rol_nombre' => (string)($row->rol_nombre ?? '')
    ];
    return response()->json($payload, 200);
});
Route::post('/api/admin/users', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $usuario = $t('tbl_usuario');
    if (!Schema::hasTable($usuario)) {
        return response()->json(['error' => 'missing_table'], 422);
    }
    $req = request();
    $nombre = trim((string)($req->input('nombre') ?? ''));
    $email = trim((string)($req->input('email') ?? ''));
    $estado = trim((string)($req->input('estado') ?? ''));
    $rolId = (int)($req->input('rol_id') ?? 0);
    if ($nombre === '' || $email === '') {
        return response()->json(['error' => 'invalid_payload'], 422);
    }
    $nameCol = Schema::hasColumn($usuario, 'nombre') ? 'nombre' : 'nombre_usuario';
    $emailCol = Schema::hasColumn($usuario, 'email') ? 'email' : 'correo';
    $estadoCol = Schema::hasColumn($usuario, 'estado') ? 'estado' : null;
    $rolCol = Schema::hasColumn($usuario, 'fk_id_rol') ? 'fk_id_rol' : null;
    $now = now();
    $data = [
        $nameCol => $nombre,
        $emailCol => $email,
    ];
    if ($estadoCol) { $data[$estadoCol] = ($estado !== '' ? $estado : 'activo'); }
    if ($rolCol && $rolId > 0) { $data[$rolCol] = $rolId; }
    if (Schema::hasColumn($usuario, 'created_at')) { $data['created_at'] = $now; }
    if (Schema::hasColumn($usuario, 'updated_at')) { $data['updated_at'] = $now; }
    $id = DB::table($usuario)->insertGetId($data);
    return response()->json(['id' => (int)$id], 201);
})->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
Route::put('/api/admin/users/{id}', function ($id) {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $usuario = $t('tbl_usuario');
    if (!Schema::hasTable($usuario)) {
        return response()->json(['error' => 'missing_table'], 422);
    }
    $exists = DB::table($usuario)->where('id_usuario', (int)$id)->exists();
    if (!$exists) {
        return response()->json(['error' => 'not_found'], 404);
    }
    $req = request();
    $nombre = trim((string)($req->input('nombre') ?? ''));
    $email = trim((string)($req->input('email') ?? ''));
    $estado = trim((string)($req->input('estado') ?? ''));
    $rolId = (int)($req->input('rol_id') ?? 0);
    if ($nombre === '' || $email === '') {
        return response()->json(['error' => 'invalid_payload'], 422);
    }
    $nameCol = Schema::hasColumn($usuario, 'nombre') ? 'nombre' : 'nombre_usuario';
    $emailCol = Schema::hasColumn($usuario, 'email') ? 'email' : 'correo';
    $estadoCol = Schema::hasColumn($usuario, 'estado') ? 'estado' : null;
    $rolCol = Schema::hasColumn($usuario, 'fk_id_rol') ? 'fk_id_rol' : null;
    $upd = [
        $nameCol => $nombre,
        $emailCol => $email,
    ];
    if ($estadoCol) { $upd[$estadoCol] = ($estado !== '' ? $estado : null); }
    if ($rolCol) { $upd[$rolCol] = ($rolId > 0 ? $rolId : null); }
    $now = now();
    if (Schema::hasColumn($usuario, 'updated_at')) { $upd['updated_at'] = $now; }
    DB::table($usuario)->where('id_usuario', (int)$id)->update($upd);
    return response()->json(['id' => (int)$id, 'updated' => true], 200);
})->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

Route::get('/api/admin/departments', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $dept = $t('tbl_departamento');
    if (!Schema::hasTable($dept)) {
        return response()->json([], 200);
    }
    $nameCol = Schema::hasColumn($dept, 'nombre_departamento') ? "$dept.nombre_departamento" : "$dept.nombre";
    $rows = DB::table($dept)
        ->select(
            "$dept.id_departamento as id",
            DB::raw("$nameCol as nombre_departamento")
        )
        ->orderBy(DB::raw($nameCol), 'asc')
        ->limit(1000)
        ->get();
    return response()->json($rows, 200);
});

Route::get('/api/admin/statuses', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $estado = $t('tbl_estado');
    if (!Schema::hasTable($estado)) {
        return response()->json([], 200);
    }
    $nameCol = Schema::hasColumn($estado, 'nombre_estado') ? "$estado.nombre_estado" : "$estado.nombre";
    $rows = DB::table($estado)
        ->select(
            "$estado.id_estado as id",
            DB::raw("$nameCol as nombre_estado")
        )
        ->orderBy(DB::raw($nameCol), 'asc')
        ->limit(1000)
        ->get();
    return response()->json($rows, 200);
});
Route::post('/api/admin/statuses', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $estado = $t('tbl_estado');
    if (!Schema::hasTable($estado)) {
        return response()->json(['error' => 'table_missing'], 400);
    }
    $nombre = (string)request('nombre', '');
    $nmCol = Schema::hasColumn($estado, 'nombre_estado') ? 'nombre_estado' : (Schema::hasColumn($estado, 'nombre') ? 'nombre' : null);
    if (!$nmCol || $nombre === '') {
        return response()->json(['error' => 'invalid_payload'], 422);
    }
    $ins = [ $nmCol => $nombre ];
    if (Schema::hasColumn($estado, 'created_at')) $ins['created_at'] = now();
    if (Schema::hasColumn($estado, 'updated_at')) $ins['updated_at'] = now();
    $id = DB::table($estado)->insertGetId($ins);
    return response()->json(['id' => (int)$id], 201);
});
Route::put('/api/admin/statuses/{id}', function ($id) {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $estado = $t('tbl_estado');
    if (!Schema::hasTable($estado)) {
        return response()->json(['error' => 'table_missing'], 400);
    }
    $row = DB::table($estado)->where('id_estado', (int)$id)->first();
    if (!$row) {
        return response()->json(['error' => 'not_found'], 404);
    }
    $nombre = (string)request('nombre', '');
    $nmCol = Schema::hasColumn($estado, 'nombre_estado') ? 'nombre_estado' : (Schema::hasColumn($estado, 'nombre') ? 'nombre' : null);
    if (!$nmCol || $nombre === '') {
        return response()->json(['error' => 'invalid_payload'], 422);
    }
    $upd = [ $nmCol => $nombre ];
    if (Schema::hasColumn($estado, 'updated_at')) $upd['updated_at'] = now();
    DB::table($estado)->where('id_estado', (int)$id)->update($upd);
    return response()->json(['updated' => true], 200);
});

Route::get('/api/admin/priorities', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $prioridad = $t('tbl_prioridad');
    if (!Schema::hasTable($prioridad)) {
        return response()->json([], 200);
    }
    $nameCol = Schema::hasColumn($prioridad, 'nombre_prioridad') ? "$prioridad.nombre_prioridad" : "$prioridad.nombre";
    $rows = DB::table($prioridad)
        ->select(
            "$prioridad.id_prioridad as id",
            DB::raw("$nameCol as nombre_prioridad")
        )
        ->orderBy(DB::raw($nameCol), 'asc')
        ->limit(1000)
        ->get();
    return response()->json($rows, 200);
});
Route::post('/api/admin/priorities', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $prioridad = $t('tbl_prioridad');
    if (!Schema::hasTable($prioridad)) {
        return response()->json(['error' => 'table_missing'], 400);
    }
    $nombre = (string)request('nombre', '');
    $nmCol = Schema::hasColumn($prioridad, 'nombre_prioridad') ? 'nombre_prioridad' : (Schema::hasColumn($prioridad, 'nombre') ? 'nombre' : null);
    if (!$nmCol || $nombre === '') {
        return response()->json(['error' => 'invalid_payload'], 422);
    }
    $ins = [ $nmCol => $nombre ];
    if (Schema::hasColumn($prioridad, 'created_at')) $ins['created_at'] = now();
    if (Schema::hasColumn($prioridad, 'updated_at')) $ins['updated_at'] = now();
    $id = DB::table($prioridad)->insertGetId($ins);
    return response()->json(['id' => (int)$id], 201);
});
Route::put('/api/admin/priorities/{id}', function ($id) {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $prioridad = $t('tbl_prioridad');
    if (!Schema::hasTable($prioridad)) {
        return response()->json(['error' => 'table_missing'], 400);
    }
    $row = DB::table($prioridad)->where('id_prioridad', (int)$id)->first();
    if (!$row) {
        return response()->json(['error' => 'not_found'], 404);
    }
    $nombre = (string)request('nombre', '');
    $nmCol = Schema::hasColumn($prioridad, 'nombre_prioridad') ? 'nombre_prioridad' : (Schema::hasColumn($prioridad, 'nombre') ? 'nombre' : null);
    if (!$nmCol || $nombre === '') {
        return response()->json(['error' => 'invalid_payload'], 422);
    }
    $upd = [ $nmCol => $nombre ];
    if (Schema::hasColumn($prioridad, 'updated_at')) $upd['updated_at'] = now();
    DB::table($prioridad)->where('id_prioridad', (int)$id)->update($upd);
    return response()->json(['updated' => true], 200);
});

Route::get('/api/admin/tasks', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $tarea = $t('tbl_tarea');
    if (!Schema::hasTable($tarea)) {
        return response()->json([], 200);
    }
    $nameCol = Schema::hasColumn($tarea, 'nombre_tarea') ? "$tarea.nombre_tarea" : "$tarea.nombre";
    $rows = DB::table($tarea)
        ->select(
            "$tarea.id_tarea as id",
            DB::raw("$nameCol as nombre_tarea")
        )
        ->orderBy(DB::raw($nameCol), 'asc')
        ->limit(1000)
        ->get();
    return response()->json($rows, 200);
});
Route::get('/api/admin/ans', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $ans = $t('tbl_ans');
    if (!Schema::hasTable($ans)) {
        return response()->json([], 200);
    }
    $timeCol = Schema::hasColumn($ans, 'tiempo') ? 'tiempo' : 'tiempo';
    $unitCol = Schema::hasColumn($ans, 'unidad_tiempo') ? 'unidad_tiempo' : 'medida';
    $raw = DB::table($ans)
        ->select('id_ans', $timeCol, $unitCol)
        ->orderBy($timeCol, 'asc')
        ->limit(1000)
        ->get();
    $rows = [];
    foreach ($raw as $r) {
        $rows[] = [
            'id' => (int)$r->id_ans,
            'nombre_ans' => (string)((int)$r->$timeCol) . ' ' . (string)$r->$unitCol,
        ];
    }
    return response()->json($rows, 200);
});
Route::post('/api/admin/ans', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $ans = $t('tbl_ans');
    if (!Schema::hasTable($ans)) {
        return response()->json(['error' => 'table_missing'], 400);
    }
    $tiempo = (int)request('tiempo', 0);
    $unidad = (string)request('unidad_tiempo', '');
    if ($tiempo <= 0 || $unidad === '') {
        return response()->json(['error' => 'invalid_payload'], 422);
    }
    $ins = [ 'tiempo' => $tiempo ];
    if (Schema::hasColumn($ans, 'unidad_tiempo')) { $ins['unidad_tiempo'] = $unidad; }
    else if (Schema::hasColumn($ans, 'medida')) { $ins['medida'] = $unidad; }
    if (Schema::hasColumn($ans, 'created_at')) $ins['created_at'] = now();
    if (Schema::hasColumn($ans, 'updated_at')) $ins['updated_at'] = now();
    $id = DB::table($ans)->insertGetId($ins);
    return response()->json(['id' => (int)$id], 201);
});
Route::put('/api/admin/ans/{id}', function ($id) {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $ans = $t('tbl_ans');
    if (!Schema::hasTable($ans)) {
        return response()->json(['error' => 'table_missing'], 400);
    }
    $row = DB::table($ans)->where('id_ans', (int)$id)->first();
    if (!$row) {
        return response()->json(['error' => 'not_found'], 404);
    }
    $tiempo = (int)request('tiempo', 0);
    $unidad = (string)request('unidad_tiempo', '');
    if ($tiempo <= 0 || $unidad === '') {
        return response()->json(['error' => 'invalid_payload'], 422);
    }
    $upd = [ 'tiempo' => $tiempo ];
    if (Schema::hasColumn($ans, 'unidad_tiempo')) { $upd['unidad_tiempo'] = $unidad; }
    else if (Schema::hasColumn($ans, 'medida')) { $upd['medida'] = $unidad; }
    if (Schema::hasColumn($ans, 'updated_at')) $upd['updated_at'] = now();
    DB::table($ans)->where('id_ans', (int)$id)->update($upd);
    return response()->json(['updated' => true], 200);
});
Route::get('/api/admin/category-form-data', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $ansT = $t('tbl_ans');
    $taskT = $t('tbl_tarea');
    $deptT = $t('tbl_departamento');
    $userT = $t('tbl_usuario');
    $ansRows = [];
    $taskRows = [];
    $deptRows = [];
    $userRows = [];
    if (Schema::hasTable($ansT)) {
        $timeCol = Schema::hasColumn($ansT, 'tiempo') ? 'tiempo' : 'tiempo';
        $unitCol = Schema::hasColumn($ansT, 'unidad_tiempo') ? 'unidad_tiempo' : 'medida';
        $raw = DB::table($ansT)
            ->select('id_ans', $timeCol, $unitCol)
            ->orderBy($timeCol, 'asc')
            ->limit(1000)
            ->get();
        $ansRows = [];
        foreach ($raw as $r) {
            $ansRows[] = [
                'id' => (int)$r->id_ans,
                'nombre_ans' => (string)((int)$r->$timeCol) . ' ' . (string)$r->$unitCol,
            ];
        }
    }
    if (Schema::hasTable($taskT)) {
        $nameCol = Schema::hasColumn($taskT, 'nombre_tarea') ? "$taskT.nombre_tarea" : "$taskT.nombre";
        $taskRows = DB::table($taskT)
            ->select("$taskT.id_tarea as id", DB::raw("$nameCol as nombre_tarea"))
            ->orderBy(DB::raw($nameCol), 'asc')
            ->limit(1000)
            ->get();
    }
    if (Schema::hasTable($deptT)) {
        $nameCol = Schema::hasColumn($deptT, 'nombre_departamento') ? "$deptT.nombre_departamento" : "$deptT.nombre";
        $deptRows = DB::table($deptT)
            ->select("$deptT.id_departamento as id", DB::raw("$nameCol as nombre_departamento"))
            ->orderBy(DB::raw($nameCol), 'asc')
            ->limit(1000)
            ->get();
    }
    if (Schema::hasTable($userT)) {
        $nameCol = Schema::hasColumn($userT, 'nombre') ? "$userT.nombre" : "$userT.nombre_usuario";
        $userRows = DB::table($userT)
            ->select("$userT.id_usuario as id", DB::raw("$nameCol as nombre_usuario"))
            ->orderBy(DB::raw($nameCol), 'asc')
            ->limit(1000)
            ->get();
    }
    return response()->json([
        'ans' => $ansRows,
        'tasks' => $taskRows,
        'departments' => $deptRows,
        'users' => $userRows,
    ], 200);
});
Route::get('/api/catalog/users', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $usuario = $t('tbl_usuario');
    $categoria = $t('tbl_categoria');
    if (!Schema::hasTable($usuario)) {
        return response()->json([], 200);
    }
    $req = request();
    $q = trim((string)($req->query('q') ?? ''));
    $categoryId = (int)($req->query('category_id') ?? 0);
    try {
        $cacheKey = 'catalog:users:' . $prefix . ':' . md5($q) . ':' . $categoryId;
        $rows = Cache::remember($cacheKey, 300, function () use ($usuario, $q, $categoria, $categoryId) {
            $nameCol = Schema::hasColumn($usuario, 'nombre') ? 'nombre' : 'nombre_usuario';
            $emailCol = Schema::hasColumn($usuario, 'email') ? 'email' : 'correo';
            $builder = DB::table($usuario)
                ->select('id_usuario', DB::raw("$nameCol as nombre_usuario"));
            if ($q !== '') {
                $builder->where(function ($qq) use ($nameCol, $emailCol, $q) {
                    $qq->where($nameCol, 'like', '%' . $q . '%')
                       ->orWhere($emailCol, 'like', '%' . $q . '%');
                });
            }
            if ($categoryId > 0 && Schema::hasTable($categoria)) {
                $repCol = Schema::hasColumn($categoria, 'fk_id_responsable') ? 'fk_id_responsable' : 'fk_id_usuarios';
                $representativeId = DB::table($categoria)
                    ->where('id_categoria', $categoryId)
                    ->value($repCol);
                if ($representativeId) {
                    $builder->where('id_usuario', $representativeId);
                }
            }
            return $builder->orderBy($nameCol, 'asc')->limit(500)->get();
        });
        return response()->json($rows, 200);
    } catch (\Throwable $e) {
        $nameCol = Schema::hasColumn($usuario, 'nombre') ? 'nombre' : 'nombre_usuario';
        $emailCol = Schema::hasColumn($usuario, 'email') ? 'email' : 'correo';
        $builder = DB::table($usuario)
            ->select('id_usuario', DB::raw("$nameCol as nombre_usuario"));
        if ($q !== '') {
            $builder->where(function ($qq) use ($nameCol, $emailCol, $q) {
                $qq->where($nameCol, 'like', '%' . $q . '%')
                   ->orWhere($emailCol, 'like', '%' . $q . '%');
            });
        }
        if ($categoryId > 0 && Schema::hasTable($categoria)) {
            $repCol = Schema::hasColumn($categoria, 'fk_id_responsable') ? 'fk_id_responsable' : 'fk_id_usuarios';
            $representativeId = DB::table($categoria)
                ->where('id_categoria', $categoryId)
                ->value($repCol);
            if ($representativeId) {
                $builder->where('id_usuario', $representativeId);
            }
        }
        $rows = $builder->orderBy($nameCol, 'asc')->limit(500)->get();
        return response()->json($rows, 200);
    }
});

Route::get('/api/catalog/representatives', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $categoria = $t('tbl_categoria');
    $usuario = $t('tbl_usuario');
    if (!Schema::hasTable($categoria) || !Schema::hasTable($usuario)) {
        return response()->json([], 200);
    }
    $req = request();
    $q = trim((string)($req->query('q') ?? ''));
    $repCol = Schema::hasColumn($categoria, 'fk_id_responsable') ? 'fk_id_responsable' : 'fk_id_usuarios';
    $userNameCol = Schema::hasColumn($usuario, 'nombre') ? "$usuario.nombre" : "$usuario.nombre_usuario";
    $catNameCol = Schema::hasColumn($categoria, 'nombre_categoria') ? "$categoria.nombre_categoria" : "$categoria.nombre";
    $builder = DB::table($categoria)
        ->leftJoin($usuario, "$categoria.$repCol", '=', "$usuario.id_usuario")
        ->select("$usuario.id_usuario as id_usuario", DB::raw("$userNameCol as nombre_usuario"))
        ->whereNotNull("$categoria.$repCol");
    if ($q !== '') {
        $builder->where(DB::raw($catNameCol), 'like', '%' . $q . '%');
    }
    $rows = $builder->distinct()->orderBy(DB::raw($userNameCol), 'asc')->limit(500)->get();
    return response()->json($rows, 200);
});

Route::get('/api/dashboard/bars', function () {
    try {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $allowed = env('ALLOWED_EMAIL_DOMAINS', 'socya.org.co');
    $email = (string)session('user_email', '');
    $ok = false;
    foreach (array_filter(array_map('trim', explode(',', $allowed))) as $d) {
        if (str_ends_with(strtolower($email), '@' . strtolower($d))) { $ok = true; break; }
    }
    if ($uid <= 0 || !$ok) {
        \Log::warning('dashboard_bars_unauthorized', ['user_id' => $uid, 'email' => $email]);
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $tickets = $t('tbl_tickets');
    $categoria = $t('tbl_categoria');
    $departamento = $t('tbl_departamento');
    $proceso = $t('tbl_proceso');
    $estado = $t('tbl_estado');
    $tarea = $t('tbl_tarea');
    $usuario = $t('tbl_usuario');
    foreach ([$tickets,$categoria,$departamento,$proceso,$estado,$usuario] as $tb) {
        if (!Schema::hasTable($tb)) {
            return response()->json(['labels' => [], 'series' => []], 200);
        }
    }
    $req = request();
    $period = strtolower((string)($req->query('period') ?? 'year'));
    $year = (int)($req->query('year') ?? date('Y'));
    $month = (int)($req->query('month') ?? date('n'));
    $processId = (int)($req->query('process_id') ?? 0);
    $limit = (int)($req->query('limit') ?? 12);
    $groupParam = strtolower((string)($req->query('group') ?? ''));
    \Log::info('dashboard_bars_request', [
        'user_id' => $uid, 'period' => $period, 'year' => $year, 'month' => $month,
        'process_id' => $processId, 'group' => $groupParam,
    ]);
    $createdColName = Schema::hasColumn($tickets, 'created_at') ? 'created_at' : (Schema::hasColumn($tickets, 'fecha_creacion') ? 'fecha_creacion' : null);
    $createdCol = $createdColName ? "$tickets.$createdColName" : null;
    $estadoNameCol = Schema::hasColumn($estado, 'nombre_estado') ? "$estado.nombre_estado" : "$estado.nombre";
    $hasTask = Schema::hasTable($tarea) && Schema::hasColumn($categoria, 'fk_id_tarea');
    $groupLabelCol = $hasTask
        ? (Schema::hasColumn($tarea, 'nombre_tarea') ? "$tarea.nombre_tarea" : "$tarea.nombre")
        : (Schema::hasColumn($departamento, 'nombre_departamento') ? "$departamento.nombre_departamento" : "$departamento.nombre");
    $groupIdCol = $hasTask ? "$tarea.id_tarea" : "$departamento.id_departamento";
    $orderCol = $hasTask && Schema::hasColumn($tarea, 'orden') ? "$tarea.orden" : $groupLabelCol;
    $catProcessFk = Schema::hasColumn($categoria, 'fk_id_proceso') ? "$categoria.fk_id_proceso" : null;
    $procDeptFk = Schema::hasColumn($proceso, 'fk_id_departamento') ? "$proceso.fk_id_departamento" : null;
    $q = DB::table($tickets)
        ->leftJoin($categoria, "$tickets.fk_id_categoria", '=', "$categoria.id_categoria")
        ->when($catProcessFk, function ($qq) use ($proceso, $departamento, $catProcessFk, $procDeptFk) {
            $qq = $qq->leftJoin($proceso, $catProcessFk, '=', "$proceso.id_proceso");
            if ($procDeptFk) {
                $qq = $qq->leftJoin($departamento, $procDeptFk, '=', "$departamento.id_departamento");
            }
            return $qq;
        }, function ($qq) use ($departamento, $proceso, $categoria) {
            $qq = $qq->leftJoin($departamento, "$categoria.fk_id_departamento", '=', "$departamento.id_departamento");
            if (Schema::hasColumn($departamento, 'fk_id_proceso')) {
                $qq = $qq->leftJoin($proceso, "$departamento.fk_id_proceso", '=', "$proceso.id_proceso");
            }
            return $qq;
        })
        ->leftJoin($estado, "$tickets.fk_id_estado", '=', "$estado.id_estado");
    if ($hasTask) {
        $q = $q->leftJoin($tarea, "$categoria.fk_id_tarea", '=', "$tarea.id_tarea");
    }
    if ($createdCol) {
        if ($period === 'year' && $year > 0) {
            $q->whereYear($createdCol, $year);
        } else if ($period === 'month' && $year > 0 && $month > 0) {
            $q->whereYear($createdCol, $year)->whereMonth($createdCol, $month);
        }
    }
    if ($processId > 0) {
        $q->where("$proceso.id_proceso", $processId);
    }
    $userDeptId = null;
    $urow = DB::table($usuario)->where('id_usuario', $uid)->select('fk_id_departamento')->first();
    if ($urow && isset($urow->fk_id_departamento)) {
        $userDeptId = (int)$urow->fk_id_departamento;
    }
    if ($userDeptId === null) {
        \Log::warning('dashboard_bars_no_department', ['user_id' => $uid]);
        return response()->json(['error' => 'department_not_assigned'], 403);
    }
    if ($userDeptId !== null) {
        $q->where("$departamento.id_departamento", $userDeptId);
    }
    if ($groupParam === 'month') {
        if (!$createdCol) {
            $labelsM = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
            return response()->json([
                'labels' => $labelsM,
                'series' => [
                    ['name' => 'SOLICITUDES', 'data' => array_fill(0, 12, 0)],
                ],
                'group' => 'month',
                'period' => $period,
                'year' => $year,
                'month' => $month,
            ], 200);
        }
        $mq = DB::table($tickets)
            ->leftJoin($categoria, "$tickets.fk_id_categoria", '=', "$categoria.id_categoria")
            ->when($catProcessFk, function ($qq) use ($proceso, $departamento, $catProcessFk, $procDeptFk) {
                $qq = $qq->leftJoin($proceso, $catProcessFk, '=', "$proceso.id_proceso");
                if ($procDeptFk) {
                    $qq = $qq->leftJoin($departamento, $procDeptFk, '=', "$departamento.id_departamento");
                }
                return $qq;
            }, function ($qq) use ($departamento, $proceso, $categoria) {
                $qq = $qq->leftJoin($departamento, "$categoria.fk_id_departamento", '=', "$departamento.id_departamento");
                if (Schema::hasColumn($departamento, 'fk_id_proceso')) {
                    $qq = $qq->leftJoin($proceso, "$departamento.fk_id_proceso", '=', "$proceso.id_proceso");
                }
                return $qq;
            });
        if ($period === 'year' && $year > 0) {
            $mq->whereYear($createdCol, $year);
        } else if ($period === 'month' && $year > 0 && $month > 0) {
            $mq->whereYear($createdCol, $year)->whereMonth($createdCol, $month);
        }
        if ($processId > 0) {
            $mq->where("$proceso.id_proceso", $processId);
        }
        if ($userDeptId !== null) {
            $mq->where("$departamento.id_departamento", $userDeptId);
        }
        \Log::info('dashboard_bars_month_group_filters', [
            'user_id' => $uid,
            'dept_id' => $userDeptId,
            'created_col' => $createdColName,
            'process_id' => $processId,
        ]);
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $monthExpr = "CAST(strftime('%m', $createdCol) AS INTEGER)";
        } else if ($driver === 'pgsql') {
            $monthExpr = "EXTRACT(MONTH FROM $createdCol)";
        } else {
            $monthExpr = "MONTH($createdCol)";
        }
        $rowsM = $mq->select(
            DB::raw("$monthExpr as m"),
            DB::raw("COUNT(*) as total")
        )->groupBy(DB::raw($monthExpr))->get();
        $labelsM = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $counts = array_fill(0, 12, 0);
        foreach ($rowsM as $r) {
            $mi = (int)$r->m;
            if ($mi >= 1 && $mi <= 12) {
                $counts[$mi - 1] = (int)$r->total;
            }
        }
        return response()->json([
            'labels' => $labelsM,
            'series' => [
                ['name' => 'SOLICITUDES', 'data' => $counts],
            ],
            'group' => 'month',
            'period' => $period,
            'year' => $year,
            'month' => $month,
        ], 200);
    }
    $closedExpr = "LOWER($estadoNameCol) IN ('cerrado','cancelado')";
    $rows = $q->select(
        DB::raw("$groupIdCol as gid"),
        DB::raw("$groupLabelCol as glabel"),
        DB::raw("SUM(CASE WHEN $closedExpr THEN 1 ELSE 0 END) as cerradas"),
        DB::raw("SUM(CASE WHEN $closedExpr THEN 0 ELSE 1 END) as tramite")
    )->groupBy(DB::raw("$groupIdCol, $groupLabelCol"))
     ->orderBy(DB::raw($orderCol), 'asc')
     ->limit($limit)
     ->get();
    \Log::info('dashboard_bars_filters', [
        'user_id' => $uid,
        'dept_id' => $userDeptId,
        'created_col' => $createdColName,
        'process_id' => $processId,
        'group' => $groupParam,
    ]);
    $labels = [];
    $cerradas = [];
    $tramite = [];
    foreach ($rows as $r) {
        $labels[] = $r->glabel;
        $cerradas[] = (int)$r->cerradas;
        $tramite[] = (int)$r->tramite;
    }
    return response()->json([
        'labels' => $labels,
        'series' => [
            ['name' => 'CERRADAS', 'data' => $cerradas],
            ['name' => 'TRÁMITE', 'data' => $tramite],
        ],
        'group' => $hasTask ? 'tarea' : 'departamento',
        'period' => $period,
        'year' => $year,
        'month' => $month,
    ], 200);
    } catch (\Throwable $e) {
        $dbg = (string)(request()->query('debug') ?? '');
        $payload = [
            'error' => 'exception',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
        try {
            \Log::error('dashboard_bars_exception', $payload);
        } catch (\Throwable $e2) {}
        return response()->json($payload, $dbg === '1' ? 200 : 500);
    }
});
Route::post('/api/tasks/seed-default', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $tarea = $t('tbl_tarea');
    $categoria = $t('tbl_categoria');
    $departamento = $t('tbl_departamento');
    if (!Schema::hasTable($tarea)) {
        return response()->json(['created' => 0, 'updated' => 0], 200);
    }
    $defs = [
        ['nombre_tarea' => 'Bases de Datos', 'codigo' => 'BD', 'color' => '#06b6d4', 'orden' => 1],
        ['nombre_tarea' => 'Desarrollo', 'codigo' => 'DEV', 'color' => '#3b82f6', 'orden' => 2],
        ['nombre_tarea' => 'Infraestructura', 'codigo' => 'INF', 'color' => '#f59e0b', 'orden' => 3],
        ['nombre_tarea' => 'Líder de Proceso', 'codigo' => 'LP', 'color' => '#6366f1', 'orden' => 4],
        ['nombre_tarea' => 'Seguridad', 'codigo' => 'SEC', 'color' => '#ef4444', 'orden' => 5],
        ['nombre_tarea' => 'Sistemas de información', 'codigo' => 'SI', 'color' => '#22c55e', 'orden' => 6],
        ['nombre_tarea' => 'Soporte Técnico', 'codigo' => 'SUP', 'color' => '#0ea5e9', 'orden' => 7],
    ];
    $existing = DB::table($tarea)->pluck('id_tarea', 'nombre_tarea');
    $created = 0;
    foreach ($defs as $d) {
        if (!$existing->has($d['nombre_tarea'])) {
            DB::table($tarea)->insert([
                'nombre_tarea' => $d['nombre_tarea'],
                'codigo' => $d['codigo'],
                'descripcion' => null,
                'metricas' => json_encode(['incluir_en_dashboard' => true]),
                'orden' => $d['orden'],
                'color' => $d['color'],
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }
    }
    $ids = DB::table($tarea)->select('id_tarea','nombre_tarea')->get()->reduce(function ($acc, $r) {
        $acc[strtolower($r->nombre_tarea)] = (int)$r->id_tarea;
        return $acc;
    }, []);
    $updated = 0;
    if (Schema::hasTable($categoria) && Schema::hasTable($departamento) && Schema::hasColumn($categoria, 'fk_id_tarea')) {
        $nameDeptCol = Schema::hasColumn($departamento, 'nombre_departamento') ? "$departamento.nombre_departamento" : "$departamento.nombre";
        $rows = DB::table($categoria)
            ->leftJoin($departamento, "$categoria.fk_id_departamento", '=', "$departamento.id_departamento")
            ->select("$categoria.id_categoria as cid", DB::raw("$nameDeptCol as dept"))
            ->whereNull("$categoria.fk_id_tarea")
            ->limit(1000)
            ->get();
        foreach ($rows as $r) {
            $dept = strtolower((string)$r->dept);
            $key = '';
            if (str_contains($dept, 'aplicac') || str_contains($dept, 'desarroll')) { $key = 'desarrollo'; }
            else if (str_contains($dept, 'infra')) { $key = 'infraestructura'; }
            else if (str_contains($dept, 'segur')) { $key = 'seguridad'; }
            else if (str_contains($dept, 'base') || str_contains($dept, 'datos')) { $key = 'bases de datos'; }
            else if (str_contains($dept, 'soporte')) { $key = 'soporte técnico'; }
            else if (str_contains($dept, 'informac') || str_contains($dept, 'sistema')) { $key = 'sistemas de información'; }
            else if (str_contains($dept, 'proceso')) { $key = 'líder de proceso'; }
            if ($key !== '' && isset($ids[$key])) {
                DB::table($categoria)->where('id_categoria', $r->cid)->update(['fk_id_tarea' => $ids[$key]]);
                $updated++;
            }
        }
    }
    return response()->json(['created' => $created, 'updated' => $updated], 200);
})->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
Route::post('/api/ans/seed-default', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $ans = $t('tbl_ans');
    if (!Schema::hasTable($ans)) {
        return response()->json(['created' => 0], 200);
    }
    $defs = [
        ['tiempo' => 2, 'unidad_tiempo' => 'horas', 'descripcion' => 'Crítico'],
        ['tiempo' => 8, 'unidad_tiempo' => 'horas', 'descripcion' => 'Normal'],
        ['tiempo' => 24, 'unidad_tiempo' => 'horas', 'descripcion' => 'Baja'],
        ['tiempo' => 3, 'unidad_tiempo' => 'dias', 'descripcion' => 'Resolución estándar'],
        ['tiempo' => 7, 'unidad_tiempo' => 'dias', 'descripcion' => 'Mantenimiento'],
    ];
    $existing = DB::table($ans)->select('tiempo','unidad_tiempo')->get()->map(function ($r) {
        return strtolower((string)$r->unidad_tiempo) . ':' . (int)$r->tiempo;
    })->toArray();
    $created = 0;
    foreach ($defs as $d) {
        $key = strtolower($d['unidad_tiempo']) . ':' . (int)$d['tiempo'];
        if (!in_array($key, $existing, true)) {
            DB::table($ans)->insert([
                'tiempo' => (int)$d['tiempo'],
                'unidad_tiempo' => (string)$d['unidad_tiempo'],
                'descripcion' => (string)($d['descripcion'] ?? null),
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }
    }
    return response()->json(['created' => $created], 200);
})->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
Route::get('/api/ans/seed-default', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $ans = $t('tbl_ans');
    if (!Schema::hasTable($ans)) {
        return response()->json(['created' => 0], 200);
    }
    $defs = [
        ['tiempo' => 2, 'unidad_tiempo' => 'horas', 'descripcion' => 'Crítico'],
        ['tiempo' => 8, 'unidad_tiempo' => 'horas', 'descripcion' => 'Normal'],
        ['tiempo' => 24, 'unidad_tiempo' => 'horas', 'descripcion' => 'Baja'],
        ['tiempo' => 3, 'unidad_tiempo' => 'dias', 'descripcion' => 'Resolución estándar'],
        ['tiempo' => 7, 'unidad_tiempo' => 'dias', 'descripcion' => 'Mantenimiento'],
    ];
    $existing = DB::table($ans)->select('tiempo','unidad_tiempo')->get()->map(function ($r) {
        return strtolower((string)$r->unidad_tiempo) . ':' . (int)$r->tiempo;
    })->toArray();
    $created = 0;
    foreach ($defs as $d) {
        $key = strtolower($d['unidad_tiempo']) . ':' . (int)$d['tiempo'];
        if (!in_array($key, $existing, true)) {
            DB::table($ans)->insert([
                'tiempo' => (int)$d['tiempo'],
                'unidad_tiempo' => (string)$d['unidad_tiempo'],
                'descripcion' => (string)($d['descripcion'] ?? null),
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }
    }
    return response()->json(['created' => $created], 200);
});
Route::get('/api/tasks/seed-default', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $tarea = $t('tbl_tarea');
    $categoria = $t('tbl_categoria');
    $departamento = $t('tbl_departamento');
    if (!Schema::hasTable($tarea)) {
        return response()->json(['created' => 0, 'updated' => 0], 200);
    }
    $defs = [
        ['nombre_tarea' => 'Bases de Datos', 'codigo' => 'BD', 'color' => '#06b6d4', 'orden' => 1],
        ['nombre_tarea' => 'Desarrollo', 'codigo' => 'DEV', 'color' => '#3b82f6', 'orden' => 2],
        ['nombre_tarea' => 'Infraestructura', 'codigo' => 'INF', 'color' => '#f59e0b', 'orden' => 3],
        ['nombre_tarea' => 'Líder de Proceso', 'codigo' => 'LP', 'color' => '#6366f1', 'orden' => 4],
        ['nombre_tarea' => 'Seguridad', 'codigo' => 'SEC', 'color' => '#ef4444', 'orden' => 5],
        ['nombre_tarea' => 'Sistemas de información', 'codigo' => 'SI', 'color' => '#22c55e', 'orden' => 6],
        ['nombre_tarea' => 'Soporte Técnico', 'codigo' => 'SUP', 'color' => '#0ea5e9', 'orden' => 7],
    ];
    $existing = DB::table($tarea)->pluck('id_tarea', 'nombre_tarea');
    $created = 0;
    foreach ($defs as $d) {
        if (!$existing->has($d['nombre_tarea'])) {
            DB::table($tarea)->insert([
                'nombre_tarea' => $d['nombre_tarea'],
                'codigo' => $d['codigo'],
                'descripcion' => null,
                'metricas' => json_encode(['incluir_en_dashboard' => true]),
                'orden' => $d['orden'],
                'color' => $d['color'],
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }
    }
    $ids = DB::table($tarea)->select('id_tarea','nombre_tarea')->get()->reduce(function ($acc, $r) {
        $acc[strtolower($r->nombre_tarea)] = (int)$r->id_tarea;
        return $acc;
    }, []);
    $updated = 0;
    if (Schema::hasTable($categoria) && Schema::hasTable($departamento) && Schema::hasColumn($categoria, 'fk_id_tarea')) {
        $nameDeptCol = Schema::hasColumn($departamento, 'nombre_departamento') ? "$departamento.nombre_departamento" : "$departamento.nombre";
        $rows = DB::table($categoria)
            ->leftJoin($departamento, "$categoria.fk_id_departamento", '=', "$departamento.id_departamento")
            ->select("$categoria.id_categoria as cid", DB::raw("$nameDeptCol as dept"))
            ->whereNull("$categoria.fk_id_tarea")
            ->limit(1000)
            ->get();
        foreach ($rows as $r) {
            $dept = strtolower((string)$r->dept);
            $key = '';
            if (str_contains($dept, 'aplicac') || str_contains($dept, 'desarroll')) { $key = 'desarrollo'; }
            else if (str_contains($dept, 'infra')) { $key = 'infraestructura'; }
            else if (str_contains($dept, 'segur')) { $key = 'seguridad'; }
            else if (str_contains($dept, 'base') || str_contains($dept, 'datos')) { $key = 'bases de datos'; }
            else if (str_contains($dept, 'soporte')) { $key = 'soporte técnico'; }
            else if (str_contains($dept, 'informac') || str_contains($dept, 'sistema')) { $key = 'sistemas de información'; }
            else if (str_contains($dept, 'proceso')) { $key = 'líder de proceso'; }
            if ($key !== '' && isset($ids[$key])) {
                DB::table($categoria)->where('id_categoria', $r->cid)->update(['fk_id_tarea' => $ids[$key]]);
                $updated++;
            }
        }
    }
    return response()->json(['created' => $created, 'updated' => $updated], 200);
});
Route::post('/login/google', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $usuario = $t('tbl_usuario');
    $rol = $t('tbl_rol');
    $departamento = $t('tbl_departamento');
    $area = $t('tbl_area');
    try {
    foreach ([$usuario,$rol,$departamento] as $tb) {
        if (!Schema::hasTable($tb)) {
            return response()->json(['error' => 'missing_table', 'table' => $tb], 422);
        }
    }
    $req = request();
    $email = (string)($req->input('email') ?? '');
    $name = (string)($req->input('name') ?? '');
    $uid = (string)($req->input('uid') ?? '');
    if ($email === '' || $name === '') {
        return response()->json(['error' => 'invalid_payload'], 422);
    }
    $allowed = env('ALLOWED_EMAIL_DOMAINS', 'socya.org.co');
    if ($allowed !== '') {
        $domains = array_filter(array_map('trim', explode(',', $allowed)));
        $domainOk = false;
        foreach ($domains as $d) {
            if (str_ends_with(strtolower($email), '@' . strtolower($d))) {
                $domainOk = true;
                break;
            }
        }
        if (!$domainOk) {
            return response()->json(['error' => 'domain_not_allowed'], 403);
        }
    }
    $userEmailCol = Schema::hasColumn($usuario, 'email') ? 'email' : 'correo';
    $userNameCol = Schema::hasColumn($usuario, 'nombre') ? 'nombre' : 'nombre_usuario';
    $user = DB::table($usuario)->where($userEmailCol, $email)->first();
    if (!$user) {
        $deptNameCol = Schema::hasColumn($departamento, 'nombre_departamento') ? 'nombre_departamento' : 'nombre';
        $deptId = DB::table($departamento)->value('id_departamento');
        if (!$deptId) {
            $deptId = DB::table($departamento)->insertGetId([$deptNameCol => 'General']);
        }
        $areaId = null;
        if (Schema::hasColumn($usuario, 'fk_id_area')) {
            if (Schema::hasTable($area)) {
                $areaNameCol = Schema::hasColumn($area, 'nombre_area') ? 'nombre_area' : 'nombre';
                $areaId = DB::table($area)->value('id_area');
                if (!$areaId) {
                    $areaId = DB::table($area)->insertGetId([$areaNameCol => 'General']);
                }
            } else {
                $areaId = 1;
            }
        }
        $rolNameCol = Schema::hasColumn($rol, 'nombre_rol') ? 'nombre_rol' : 'nombre';
        $rolId = DB::table($rol)->where($rolNameCol, 'Usuario final')->value('id_rol');
        if (!$rolId) {
            $rolId = DB::table($rol)->insertGetId([$rolNameCol => 'Usuario final']);
        }
        $insert = [
            $userNameCol => $name,
            $userEmailCol => $email,
            'estado' => 'activo',
        ];
        if (Schema::hasColumn($usuario, 'fk_id_departamento')) {
            $insert['fk_id_departamento'] = $deptId;
        }
        if (Schema::hasColumn($usuario, 'fk_id_area') && $areaId !== null) {
            $insert['fk_id_area'] = $areaId;
        }
        if (Schema::hasColumn($usuario, 'fk_id_rol')) {
            $insert['fk_id_rol'] = $rolId;
        }
        if (Schema::hasColumn($usuario, 'password')) {
            $insert['password'] = Hash::make(Str::random(12));
        }
        if (Schema::hasColumn($usuario, 'firebase_uid')) {
            $insert['firebase_uid'] = ($uid !== '' ? $uid : Str::uuid()->toString());
        }
        if (Schema::hasColumn($usuario, 'created_at')) {
            $insert['created_at'] = now();
        }
        if (Schema::hasColumn($usuario, 'updated_at')) {
            $insert['updated_at'] = now();
        }
        $id = DB::table($usuario)->insertGetId($insert);
        $user = DB::table($usuario)->where('id_usuario', $id)->first();
    } else {
        $upd = [$userNameCol => $name];
        if (Schema::hasColumn($usuario, 'updated_at')) {
            $upd['updated_at'] = now();
        }
        DB::table($usuario)->where('id_usuario', $user->id_usuario)->update($upd);
    }
    session([
        'user_id' => (int)$user->id_usuario,
        'user_email' => $user->{$userEmailCol},
        'user_name' => $user->{$userNameCol},
    ]);
    return response()->json([
        'ok' => true,
        'name' => $user->{$userNameCol},
        'email' => $user->{$userEmailCol},
    ], 200);
    } catch (\Throwable $e) {
        $dbg = (string)(request()->query('debug') ?? '');
        $payload = [
            'error' => 'exception',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
        return response()->json($payload, $dbg === '1' ? 200 : 500);
    }
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/api/admin/processes', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json([], 403);
    }
    $proc = $t('tbl_proceso');
    if (!Schema::hasTable($proc)) {
        return response()->json([], 200);
    }
    $nameCol = Schema::hasColumn($proc, 'nombre_proceso') ? "$proc.nombre_proceso" : "$proc.nombre";
    $rows = DB::table($proc)
        ->select("$proc.id_proceso as id", DB::raw("$nameCol as nombre_proceso"))
        ->orderBy(DB::raw($nameCol), 'asc')
        ->limit(1000)
        ->get();
    return response()->json($rows, 200);
});
Route::get('/api/admin/processes/{id}', function ($id) {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $proc = $t('tbl_proceso');
    if (!Schema::hasTable($proc)) {
        return response()->json(null, 404);
    }
    $row = DB::table($proc)->where('id_proceso', $id)->first();
    if (!$row) {
        return response()->json(null, 404);
    }
    $nmCol = Schema::hasColumn($proc, 'nombre_proceso') ? 'nombre_proceso' : (Schema::hasColumn($proc, 'nombre') ? 'nombre' : null);
    $descCol = Schema::hasColumn($proc, 'descripcion') ? 'descripcion' : null;
    $stateCol = Schema::hasColumn($proc, 'estado') ? 'estado' : null;
    return response()->json([
        'id' => (int)$row->id_proceso,
        'nombre' => $nmCol ? (string)$row->{$nmCol} : '',
        'descripcion' => $descCol ? (string)$row->{$descCol} : '',
        'estado' => $stateCol ? (string)$row->{$stateCol} : '',
    ], 200);
});
Route::post('/api/admin/processes', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $proc = $t('tbl_proceso');
    if (!Schema::hasTable($proc)) {
        return response()->json(['error' => 'table_missing'], 400);
    }
    $nombre = (string)request('nombre', '');
    $descripcion = (string)request('descripcion', '');
    $estado = (string)request('estado', 'activo');
    if ($nombre === '') {
        return response()->json(['error' => 'nombre_required'], 422);
    }
    $nmCol = Schema::hasColumn($proc, 'nombre_proceso') ? 'nombre_proceso' : (Schema::hasColumn($proc, 'nombre') ? 'nombre' : null);
    if (!$nmCol) {
        return response()->json(['error' => 'name_column_missing'], 400);
    }
    $insert = [ $nmCol => $nombre ];
    if (Schema::hasColumn($proc, 'descripcion')) $insert['descripcion'] = $descripcion;
    if (Schema::hasColumn($proc, 'estado')) $insert['estado'] = $estado;
    if (Schema::hasColumn($proc, 'created_at')) $insert['created_at'] = now();
    if (Schema::hasColumn($proc, 'updated_at')) $insert['updated_at'] = now();
    $id = DB::table($proc)->insertGetId($insert);
    return response()->json(['id' => (int)$id], 201);
});
Route::put('/api/admin/processes/{id}', function ($id) {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $uid = (int)(session('user_id') ?? 0);
    $adma = (bool)session('adma_authed', false);
    if ($uid <= 0 && !$adma) {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    $proc = $t('tbl_proceso');
    if (!Schema::hasTable($proc)) {
        return response()->json(['error' => 'table_missing'], 400);
    }
    $row = DB::table($proc)->where('id_proceso', $id)->first();
    if (!$row) {
        return response()->json(['error' => 'not_found'], 404);
    }
    $nombre = (string)request('nombre', '');
    $descripcion = (string)request('descripcion', '');
    $estado = (string)request('estado', '');
    $nmCol = Schema::hasColumn($proc, 'nombre_proceso') ? 'nombre_proceso' : (Schema::hasColumn($proc, 'nombre') ? 'nombre' : null);
    if (!$nmCol) {
        return response()->json(['error' => 'name_column_missing'], 400);
    }
    $upd = [ $nmCol => $nombre ];
    if (Schema::hasColumn($proc, 'descripcion')) $upd['descripcion'] = $descripcion;
    if (Schema::hasColumn($proc, 'estado') && $estado !== '') $upd['estado'] = $estado;
    if (Schema::hasColumn($proc, 'updated_at')) $upd['updated_at'] = now();
    DB::table($proc)->where('id_proceso', $id)->update($upd);
    return response()->json(['ok' => true], 200);
});

Route::post('/logout', function () {
    try {
        session()->flush();
    } catch (\Throwable $e) {
        try {
            foreach (['user_id','user_email','user_name','adma_authed'] as $k) {
                session()->forget($k);
            }
        } catch (\Throwable $e2) {}
    }
    return response()->json([
        'ok' => true,
        'redirect' => route('login'),
    ], 200);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
