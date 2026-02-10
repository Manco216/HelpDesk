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
    $rows = DB::table($table)
        ->select('id_proceso', 'nombre_proceso')
        ->orderBy('nombre_proceso', 'asc')
        ->get();
    return response()->json($rows);
});

Route::get('/api/processes/{id}/categories', function ($id) {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $cat = $t('tbl_categoria');
    $area = $t('tbl_area');
    if (!Schema::hasTable($cat) || !Schema::hasTable($area)) {
        return response()->json([], 200);
    }
    $rows = DB::table($cat)
        ->join($area, "$cat.fk_id_area", '=', "$area.id_area")
        ->where("$area.fk_id_proceso", $id)
        ->select("$cat.id_categoria as id_categoria", "$cat.nombre_categoria as nombre_categoria", "$cat.siglas as siglas")
        ->orderBy("$cat.nombre_categoria", 'asc')
        ->get();
    return response()->json($rows);
});

Route::post('/api/processes/{id}/categories/seed-default', function ($id) {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $proceso = $t('tbl_proceso');
    $area = $t('tbl_area');
    $departamento = $t('tbl_departamento');
    $ans = $t('tbl_ans');
    $tarea = $t('tbl_tarea');
    $usuario = $t('tbl_usuario');
    $rol = $t('tbl_rol');
    $categoria = $t('tbl_categoria');
    foreach ([$proceso,$area,$departamento,$ans,$tarea,$usuario,$rol,$categoria] as $tb) {
        if (!Schema::hasTable($tb)) {
            return response()->json([], 200);
        }
    }
    $now = now();
    $proc = DB::table($proceso)->where('id_proceso', $id)->first();
    if (!$proc) {
        $id = DB::table($proceso)->insertGetId([
            'nombre_proceso' => 'Proceso ' . $id,
            'fase_proceso' => true,
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
        ]);
        $proc = DB::table($proceso)->where('id_proceso', $id)->first();
    }
    $areaRow = DB::table($area)->where('fk_id_proceso', $id)->first();
    $areaId = $areaRow ? $areaRow->id_area : DB::table($area)->insertGetId([
        'nombre_area' => 'General ' . ($proc->nombre_proceso ?? 'Proceso'),
        'fase_area' => true,
        'fecha_creacion' => $now,
        'fecha_actualizacion' => $now,
        'fk_id_proceso' => $id,
    ]);
    $deptRow = DB::table($departamento)->where('fk_id_area', $areaId)->first();
    $deptId = $deptRow ? $deptRow->id_departamento : DB::table($departamento)->insertGetId([
        'nombre_departamento' => 'General',
        'fk_id_area' => $areaId,
        'estado' => 'activo',
        'created_at' => $now,
    ]);
    $ansCrit = DB::table($ans)->orderBy('tiempo', 'asc')->first();
    $ansCritId = $ansCrit ? $ansCrit->id_ans : DB::table($ans)->insertGetId([
        'medida' => 'horas',
        'tiempo' => 2,
        'fase_ans' => true,
        'fecha_creacion' => $now,
        'fecha_actualizacion' => $now,
    ]);
    $ansNorm = DB::table($ans)->where('tiempo', '>=', 8)->orderBy('tiempo', 'asc')->first();
    $ansNormId = $ansNorm ? $ansNorm->id_ans : DB::table($ans)->insertGetId([
        'medida' => 'horas',
        'tiempo' => 8,
        'fase_ans' => true,
        'fecha_creacion' => $now,
        'fecha_actualizacion' => $now,
    ]);
    $tSoporte = DB::table($tarea)->where('nombre_tarea', 'Soporte técnico')->first();
    $tSoporteId = $tSoporte ? $tSoporte->id_tarea : DB::table($tarea)->insertGetId([
        'nombre_tarea' => 'Soporte técnico',
        'estado' => 'activo',
        'fecha_creacion' => $now,
        'fecha_modificacion' => $now,
    ]);
    $tMant = DB::table($tarea)->where('nombre_tarea', 'Mantenimiento preventivo')->first();
    $tMantId = $tMant ? $tMant->id_tarea : DB::table($tarea)->insertGetId([
        'nombre_tarea' => 'Mantenimiento preventivo',
        'estado' => 'activo',
        'fecha_creacion' => $now,
        'fecha_modificacion' => $now,
    ]);
    $adminRole = DB::table($rol)->where('nombre_rol', 'Administrador')->first();
    $user = $adminRole ? DB::table($usuario)->where('fk_id_rol', $adminRole->id_rol)->first() : DB::table($usuario)->first();
    if (!$user) {
        $uId = DB::table($usuario)->insertGetId([
            'nombre_usuario' => 'Auto Admin',
            'correo' => 'auto_admin_' . $id . '@servicedesk.test',
            'password' => Hash::make('password'),
            'fecha_creacion' => $now,
            'fecha_actualizacion' => $now,
            'fk_id_area' => $areaId,
            'fk_id_departamento' => $deptId,
            'fk_id_rol' => $adminRole ? $adminRole->id_rol : DB::table($rol)->insertGetId([
                'nombre_rol' => 'Administrador',
                'fecha_creacion' => $now,
                'fecha_actualizacion' => $now,
            ]),
            'estado' => 'activo',
        ]);
        $user = DB::table($usuario)->where('id_usuario', $uId)->first();
    }
    $created = [];
    $defs = [
        ['Incidencias','INC',$ansCritId,$tSoporteId,$user->id_usuario],
        ['Accesos','ACC',$ansNormId,$tMantId,$user->id_usuario],
        ['Mantenimientos','MNT',$ansNormId,$tMantId,$user->id_usuario],
    ];
    foreach ($defs as $d) {
        [$name,$sig,$ansId,$taskId,$uid] = $d;
        $exists = DB::table($categoria)->where('nombre_categoria',$name)->where('fk_id_area',$areaId)->exists();
        if (!$exists) {
            DB::table($categoria)->insert([
                'nombre_categoria' => $name,
                'siglas' => $sig,
                'fase_categoria' => true,
                'fk_id_ans' => $ansId,
                'fk_id_area' => $areaId,
                'fk_id_departamento' => $deptId,
                'fecha_creacion' => $now,
                'fecha_actualizacion' => $now,
                'fk_id_usuarios' => $uid,
                'fk_id_tarea' => $taskId,
            ]);
            $created[] = $name;
        }
    }
    return response()->json(['process_id' => (int)$id, 'created' => $created], 201);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/api/tickets', function () {
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
    $area = $t('tbl_area');
    foreach ([$tickets,$categoria,$estado,$prioridad,$usuario,$adjunto,$area] as $tb) {
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
    $areaId = (int)($catRow->fk_id_area ?? 0);
    if (!$areaId) {
        $areaRow = DB::table($area)->first();
        $areaId = $areaRow ? (int)$areaRow->id_area : 0;
    }
    $estadoRow = DB::table($estado)->where('nombre_estado', 'Abierto')->first();
    if (!$estadoRow) $estadoRow = DB::table($estado)->orderBy('nivel','asc')->first();
    $priorRow = DB::table($prioridad)->where('nombre_prioridad', 'Media')->first();
    if (!$priorRow) $priorRow = DB::table($prioridad)->orderBy('orden','asc')->first();
    $adminRole = DB::table($t('tbl_rol'))->where('nombre_rol', 'Administrador')->first();
    $userRow = $adminRole ? DB::table($usuario)->where('fk_id_rol', $adminRole->id_rol)->first() : DB::table($usuario)->first();
    if (!$userRow) {
        return response()->json(['error' => 'user_not_found'], 500);
    }
    $now = now();
    $ticketId = DB::table($tickets)->insertGetId([
        'descripcion' => $desc,
        'fk_id_prioridad' => (int)$priorRow->id_prioridad,
        'fk_id_estado' => (int)$estadoRow->id_estado,
        'fk_id_categoria' => (int)$catId,
        'fk_id_usuario' => (int)$userRow->id_usuario,
        'fk_id_area' => (int)$areaId,
        'fecha_creacion' => $now,
        'fecha_actualizacion' => $now,
    ]);
    $files = $req->file('files');
    $saved = [];
    if ($files) {
        $fs = is_array($files) ? $files : [$files];
        foreach ($fs as $file) {
            if (!$file || !$file->isValid()) continue;
            $path = $file->store('adjuntos');
            $name = basename($path);
            DB::table($adjunto)->insert([
                'adjunto' => $name,
                'tipo_adjunto' => $file->getMimeType(),
                'fecha_creacion' => $now,
                'fecha_actualizacion' => $now,
                'fk_id_ticket' => (int)$ticketId,
            ]);
            $saved[] = $name;
        }
    }
    return response()->json([
        'id_tickets' => (int)$ticketId,
        'adjuntos' => $saved,
    ], 201);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/api/tickets/search', function () {
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
    $area = $t('tbl_area');
    foreach ([$tickets,$usuario,$categoria,$departamento,$estado,$prioridad,$area] as $tb) {
        if (!Schema::hasTable($tb)) {
            return response()->json([], 200);
        }
    }
    $req = request();
    $problemId = (int)($req->query('problem_id') ?? 0);
    $keywords = trim((string)($req->query('keywords') ?? ''));
    $reportedBy = trim((string)($req->query('reported_by') ?? ''));
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
    $statusMap = [
        'open' => 'Abierto',
        'in_progress' => 'En progreso',
        'closed' => 'Cerrado',
    ];
    $priorityMap = [
        'low' => 'Baja',
        'medium' => 'Media',
        'high' => 'Alta',
    ];
    $categoryMap = [
        'soporte' => 'Soporte técnico',
        'acceso' => 'Accesos',
        'incidencia' => 'Incidencias',
    ];
    $q = DB::table($tickets)
        ->leftJoin($usuario, "$tickets.fk_id_usuario", '=', "$usuario.id_usuario")
        ->leftJoin($categoria, "$tickets.fk_id_categoria", '=', "$categoria.id_categoria")
        ->leftJoin($departamento, "$categoria.fk_id_departamento", '=', "$departamento.id_departamento")
        ->leftJoin($estado, "$tickets.fk_id_estado", '=', "$estado.id_estado")
        ->leftJoin($prioridad, "$tickets.fk_id_prioridad", '=', "$prioridad.id_prioridad")
        ->leftJoin($area, "$tickets.fk_id_area", '=', "$area.id_area");
    if ($problemId > 0) {
        $q->where("$tickets.id_tickets", $problemId);
    }
    if ($keywords !== '') {
        $q->where(function ($qq) use ($tickets, $keywords) {
            $qq->where("$tickets.descripcion", 'like', '%' . $keywords . '%');
        });
    }
    if ($reportedBy !== '') {
        $q->where(function ($qq) use ($usuario, $reportedBy) {
            $qq->where("$usuario.nombre_usuario", 'like', '%' . $reportedBy . '%')
               ->orWhere("$usuario.correo", 'like', '%' . $reportedBy . '%');
        });
    }
    if ($assignedTo !== '') {
        if ($assignedTo === 'me') {
            $uid = (int)(session('user_id') ?? 0);
            if ($uid > 0) {
                $q->where("$tickets.fk_id_usuario", $uid);
            }
        } else if ($assignedTo === 'team') {
            $uid = (int)(session('user_id') ?? 0);
            if ($uid > 0) {
                $u = DB::table($usuario)->where('id_usuario', $uid)->first();
                if ($u && isset($u->fk_id_area)) {
                    $q->where("$tickets.fk_id_area", (int)$u->fk_id_area);
                }
            }
        } else if (ctype_digit($assignedTo)) {
            $uid = (int)$assignedTo;
            if ($uid > 0) {
                $q->where("$tickets.fk_id_usuario", $uid);
            }
        }
    }
    if ($categoryId > 0) {
        $q->where("$categoria.id_categoria", $categoryId);
    } else if ($categoryParam !== '') {
        $name = $categoryMap[strtolower($categoryParam)] ?? $categoryParam;
        $q->where("$categoria.nombre_categoria", 'like', '%' . $name . '%');
    }
    if ($departmentId > 0) {
        $q->where("$departamento.id_departamento", $departmentId);
    } else if ($departmentParam !== '') {
        $q->where("$departamento.nombre_departamento", 'like', '%' . $departmentParam . '%');
    }
    if ($statusId > 0) {
        $q->where("$estado.id_estado", $statusId);
    } else if ($statusParam !== '') {
        $st = $statusMap[strtolower($statusParam)] ?? $statusParam;
        $q->where("$estado.nombre_estado", $st);
    }
    if ($priorityId > 0) {
        $q->where("$prioridad.id_prioridad", $priorityId);
    } else if ($priorityParam !== '') {
        $pr = $priorityMap[strtolower($priorityParam)] ?? $priorityParam;
        $q->where("$prioridad.nombre_prioridad", $pr);
    }
    if ($dateFrom !== '') {
        $q->whereDate("$tickets.fecha_creacion", '>=', $dateFrom);
    }
    if ($dateTo !== '') {
        $q->whereDate("$tickets.fecha_creacion", '<=', $dateTo);
    }
    if ($orderBy === 'id') {
        $q->orderBy("$tickets.id_tickets", 'desc');
    } else if ($orderBy === 'created_at') {
        $q->orderBy("$tickets.fecha_creacion", 'desc');
    } else if ($orderBy === 'priority') {
        $q->orderBy("$prioridad.orden", 'desc');
    } else if ($orderBy === 'status') {
        $q->orderBy("$estado.nivel", 'asc');
    } else {
        $q->orderBy("$tickets.id_tickets", 'desc');
    }
    $rows = $q->select(
        "$tickets.id_tickets as id",
        "$tickets.descripcion as descripcion",
        "$tickets.fecha_creacion as fecha_creacion",
        "$estado.nombre_estado as estado",
        "$prioridad.nombre_prioridad as prioridad",
        "$categoria.nombre_categoria as categoria",
        "$departamento.nombre_departamento as departamento",
        "$area.nombre_area as area",
        "$usuario.nombre_usuario as usuario",
        "$usuario.correo as correo"
    )->limit(200)->get();
    return response()->json($rows, 200);
});
Route::get('/api/tickets/{id}', function ($id) {
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
    $area = $t('tbl_area');
    $adjunto = $t('tbl_adjunto');
    foreach ([$tickets,$usuario,$categoria,$departamento,$estado,$prioridad,$area,$adjunto] as $tb) {
        if (!Schema::hasTable($tb)) {
            return response()->json(['error' => 'missing_table'], 422);
        }
    }
    $row = DB::table($tickets)
        ->leftJoin($usuario, "$tickets.fk_id_usuario", '=', "$usuario.id_usuario")
        ->leftJoin($categoria, "$tickets.fk_id_categoria", '=', "$categoria.id_categoria")
        ->leftJoin($departamento, "$categoria.fk_id_departamento", '=', "$departamento.id_departamento")
        ->leftJoin($estado, "$tickets.fk_id_estado", '=', "$estado.id_estado")
        ->leftJoin($prioridad, "$tickets.fk_id_prioridad", '=', "$prioridad.id_prioridad")
        ->leftJoin($area, "$tickets.fk_id_area", '=', "$area.id_area")
        ->where("$tickets.id_tickets", (int)$id)
        ->select(
            "$tickets.id_tickets as id",
            "$tickets.descripcion as descripcion",
            "$tickets.fecha_creacion as fecha_creacion",
            "$estado.nombre_estado as estado",
            "$prioridad.nombre_prioridad as prioridad",
            "$categoria.nombre_categoria as categoria",
            "$departamento.nombre_departamento as departamento",
            "$area.nombre_area as area",
            "$usuario.nombre_usuario as usuario",
            "$usuario.correo as correo"
        )->first();
    if (!$row) {
        return response()->json(['error' => 'not_found'], 404);
    }
    $files = DB::table($adjunto)
        ->where('fk_id_ticket', (int)$id)
        ->orderBy('fecha_creacion', 'desc')
        ->select('adjunto', 'tipo_adjunto', 'fecha_creacion')
        ->get()
        ->map(function ($f) {
            return [
                'name' => $f->adjunto,
                'type' => $f->tipo_adjunto,
                'date' => $f->fecha_creacion,
                'url' => url('/assets/adjuntos/' . $f->adjunto),
            ];
        });
    $out = (array)$row;
    $out['attachments'] = $files;
    return response()->json($out, 200);
});
Route::get('/assets/adjuntos/{file}', function ($file) {
    $path = storage_path('app/adjuntos/' . $file);
    if (file_exists($path)) {
        return response()->download($path, $file);
    }
    return response()->noContent(404);
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
    $rows = Cache::remember('catalog:categories:' . $prefix . ':' . md5($q), 300, function () use ($cat, $q) {
        $builder = DB::table($cat)
            ->select('id_categoria', 'nombre_categoria');
        if ($q !== '') {
            $builder->where('nombre_categoria', 'like', '%' . $q . '%');
        }
        return $builder->orderBy('nombre_categoria', 'asc')->limit(500)->get();
    });
    return response()->json($rows, 200);
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
    $rows = Cache::remember('catalog:departments:' . $prefix . ':' . md5($q), 300, function () use ($dept, $q) {
        $builder = DB::table($dept)
            ->select('id_departamento', 'nombre_departamento');
        if ($q !== '') {
            $builder->where('nombre_departamento', 'like', '%' . $q . '%');
        }
        return $builder->orderBy('nombre_departamento', 'asc')->limit(500)->get();
    });
    return response()->json($rows, 200);
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
    $rows = Cache::remember('catalog:statuses:' . $prefix, 300, function () use ($estado) {
        return DB::table($estado)
            ->select('id_estado', 'nombre_estado')
            ->orderBy('nombre_estado', 'asc')
            ->get();
    });
    return response()->json($rows, 200);
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
    $rows = Cache::remember('catalog:priorities:' . $prefix, 300, function () use ($prioridad) {
        return DB::table($prioridad)
            ->select('id_prioridad', 'nombre_prioridad')
            ->orderBy('nombre_prioridad', 'asc')
            ->get();
    });
    return response()->json($rows, 200);
});
Route::get('/api/catalog/users', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $usuario = $t('tbl_usuario');
    if (!Schema::hasTable($usuario)) {
        return response()->json([], 200);
    }
    $req = request();
    $q = trim((string)($req->query('q') ?? ''));
    $rows = Cache::remember('catalog:users:' . $prefix . ':' . md5($q), 300, function () use ($usuario, $q) {
        $builder = DB::table($usuario)
            ->select('id_usuario', 'nombre_usuario');
        if ($q !== '') {
            $builder->where('nombre_usuario', 'like', '%' . $q . '%');
        }
        return $builder->orderBy('nombre_usuario', 'asc')->limit(500)->get();
    });
    return response()->json($rows, 200);
});
Route::post('/login/google', function () {
    $prefix = env('DB_PREFIX', '');
    $t = function(string $nm) use ($prefix) {
        return ($prefix !== '' && $prefix !== 'tbl_') ? preg_replace('/^tbl_/i', $prefix, $nm) : $nm;
    };
    $usuario = $t('tbl_usuario');
    $rol = $t('tbl_rol');
    $area = $t('tbl_area');
    $departamento = $t('tbl_departamento');
    foreach ([$usuario,$rol,$area,$departamento] as $tb) {
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
    $user = DB::table($usuario)->where('correo', $email)->first();
    if (!$user) {
        $areaRow = DB::table($area)->first();
        $areaId = $areaRow ? (int)$areaRow->id_area : DB::table($area)->insertGetId([
            'nombre_area' => 'General',
            'fase_area' => true,
            'fecha_creacion' => now(),
            'fecha_actualizacion' => now(),
            'fk_id_proceso' => DB::table($t('tbl_proceso'))->first()->id_proceso ?? 1,
        ]);
        $deptRow = DB::table($departamento)->where('fk_id_area', $areaId)->first();
        $deptId = $deptRow ? (int)$deptRow->id_departamento : DB::table($departamento)->insertGetId([
            'nombre_departamento' => 'General',
            'fk_id_area' => $areaId,
            'estado' => 'activo',
            'created_at' => now(),
        ]);
        $rolRow = DB::table($rol)->where('nombre_rol', 'Usuario final')->first();
        $rolId = $rolRow ? (int)$rolRow->id_rol : DB::table($rol)->insertGetId([
            'nombre_rol' => 'Usuario final',
            'fecha_creacion' => now(),
            'fecha_actualizacion' => now(),
        ]);
        $uidStr = $uid !== '' ? $uid : Str::uuid()->toString();
        $id = DB::table($usuario)->insertGetId([
            'nombre_usuario' => $name,
            'correo' => $email,
            'password' => Hash::make(Str::random(12)),
            'fecha_creacion' => now(),
            'fecha_actualizacion' => now(),
            'fk_id_area' => $areaId,
            'fk_id_departamento' => $deptId,
            'fk_id_rol' => $rolId,
            'estado' => 'activo',
            'firebase_uid' => $uidStr,
        ]);
        $user = DB::table($usuario)->where('id_usuario', $id)->first();
    } else {
        DB::table($usuario)->where('id_usuario', $user->id_usuario)->update([
            'nombre_usuario' => $name,
            'fecha_actualizacion' => now(),
        ]);
    }
    session([
        'user_id' => (int)$user->id_usuario,
        'user_email' => $user->correo,
        'user_name' => $user->nombre_usuario,
    ]);
    return response()->json([
        'ok' => true,
        'name' => $user->nombre_usuario,
        'email' => $user->correo,
    ], 200);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

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
