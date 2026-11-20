<?php

function agrosyncRecordVersion(array $row): string
{
    return hash('sha256', json_encode($row, JSON_THROW_ON_ERROR));
}

function agrosyncRecordQuery(string $kind): array
{
    return match ($kind) {
        'fincas' => ['ags_Fincas', 'ags_fincas r', 'r.id', 'r.codigo_finca AS codigo'],
        'lotes' => ['ags_lotes', 'ags_lotes r', 'r.finca_id', 'r.codigo_lote AS codigo'],
        'labores' => ['ags_labores', 'ags_labores r JOIN ags_lotes l ON l.id=r.lote_id AND l.deleted=0',
            'l.finca_id', 'r.lote_id AS loteId,r.fecha,r.tipo_labor AS tipo'],
        'cosechas' => ['ags_cosechas', 'ags_cosechas r JOIN ags_lotes l ON l.id=r.lote_id AND l.deleted=0',
            'l.finca_id', 'r.lote_id AS loteId,r.recolector_id AS recolectorId,r.fecha,r.estado_cafe AS estadoCafe,
                         r.cantidad_kg AS pesoKg,r.tarifa_kg AS tarifaKg'],
        'insumos' => ['ags_insumos', 'ags_insumos r', 'r.finca_id',
            'r.codigo_insumo AS codigo,r.unidad,r.estado,r.categoria'],
    };
}

function agrosyncRecords($db, string $kind, string $farmId, ?string $recordId = null): array
{
    [, $from, $farmColumn, $columns] = agrosyncRecordQuery($kind);
    $farm = $db->quoted($farmId);
    $record = $recordId === null ? '' : ' AND r.id=' . $db->quoted($recordId);
    $rows = agrosyncInventoryRows($db, "SELECT r.id,r.name AS nombre,r.date_modified AS modificado,$columns
        FROM $from WHERE r.deleted=0 AND $farmColumn=$farm$record ORDER BY r.name,r.id");
    return array_map(static fn ($row) => $row + ['version'=>agrosyncRecordVersion($row)], $rows);
}

$app->get('/agrosync/registros/{kind:fincas|lotes|labores|insumos|cosechas}', function ($request, $response, $args) {
    $user = agrosyncDelegatedUser($request);
    $farm = $request->getQueryParam('fincaId');
    if (!$user || !agrosyncLivestockId($farm) || !agrosyncLivestockRole($user, $farm)) return $response->withStatus(403);
    $db = DBManagerFactory::getInstance();
    return agrosyncLivestockReply($response, 200, ['registros'=>agrosyncRecords($db, $args['kind'], $farm)]);
});

$app->post('/agrosync/registros/{kind:fincas|lotes|labores|insumos|cosechas}', function ($request, $response, $args) {
    $user = agrosyncDelegatedUser($request);
    $kind = $args['kind'];
    $input = $request->getParsedBody();
    $farm = $input['fincaId'] ?? null;
    $id = $input['id'] ?? null;
    $op = $input['operationId'] ?? null;
    $version = $input['version'] ?? null;
    $name = $input['nombre'] ?? null;
    $code = $input['codigo'] ?? null;
    $role = $user && agrosyncLivestockId($farm) ? agrosyncLivestockRole($user, $farm) : null;
    if ($role !== 'ADMINISTRADOR_FINCA' && !($role === 'REGISTRADOR' && $kind === 'labores' && $id === null)) {
        return $response->withStatus(403);
    }
    if (!is_string($op) || !preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/D', $op)
        || !agrosyncLivestockText($name, 255, true)
        || ($id !== null && (!agrosyncLivestockId($id) || !is_string($version) || !preg_match('/^[a-f0-9]{64}$/D', $version)))
        || ($kind === 'fincas' && $id !== $farm)
        || ($kind === 'cosechas' && $id === null)
        || (in_array($kind, ['fincas','lotes','insumos'], true) && !agrosyncLivestockText($code, 30, true))) {
        return agrosyncLivestockReply($response, 400, ['code'=>'REGISTRO_INVALIDO']);
    }

    $fields = ['name'=>trim($name)];
    $compare = ['nombre'=>trim($name)];
    if ($kind === 'labores') {
        $lot = $input['loteId'] ?? null;
        $date = $input['fecha'] ?? null;
        $type = $input['tipo'] ?? null;
        if (!agrosyncLivestockId($lot) || !agrosyncLivestockDate($date)
            || !in_array($type, ['siembra','fertilizacion','control_malezas','poda','otra'], true)) {
            return agrosyncLivestockReply($response, 400, ['code'=>'LABOR_INVALIDA']);
        }
        $fields += ['lote_id'=>$lot, 'fecha'=>$date, 'tipo_labor'=>$type];
        $compare += ['loteId'=>$lot, 'fecha'=>$date, 'tipo'=>$type];
    } elseif ($kind === 'cosechas') {
        $lot = $input['loteId'] ?? null;
        $collector = $input['recolectorId'] ?? null;
        $date = $input['fecha'] ?? null;
        $state = $input['estadoCafe'] ?? null;
        $amount = $input['pesoKg'] ?? null;
        $rate = $input['tarifaKg'] ?? null;
        if (!agrosyncLivestockId($lot) || !agrosyncLivestockId($collector) || !agrosyncLivestockDate($date)
            || !in_array($state, ['cereza','pergamino'], true)
            || !is_string($amount) || !preg_match('/^[0-9]{1,11}(?:\.[0-9]{1,3})?$/D', $amount)
            || !is_string($rate) || !preg_match('/^[0-9]{1,12}(?:\.[0-9]{1,2})?$/D', $rate)
            || bccomp($amount, '0', 3) <= 0 || bccomp($rate, '0', 2) <= 0) {
            return agrosyncLivestockReply($response, 400, ['code'=>'COSECHA_INVALIDA']);
        }
        $contact = BeanFactory::getBean('Contacts', $collector);
        if (empty($contact->id) || !$contact->ACLAccess('view')) return $response->withStatus(403);
        $amount = bcadd($amount, '0', 3);
        $rate = bcadd($rate, '0', 2);
        $fields += ['lote_id'=>$lot,'recolector_id'=>$collector,'fecha'=>$date,'estado_cafe'=>$state,
            'cantidad_kg'=>$amount,'tarifa_kg'=>$rate];
        $compare += ['loteId'=>$lot,'recolectorId'=>$collector,'fecha'=>$date,'estadoCafe'=>$state,
            'pesoKg'=>$amount,'tarifaKg'=>$rate];
    } else {
        $column = ['fincas'=>'codigo_finca', 'lotes'=>'codigo_lote', 'insumos'=>'codigo_insumo'][$kind];
        $fields[$column] = trim($code);
        $compare['codigo'] = trim($code);
    }
    if ($kind === 'insumos') {
        $unit = $input['unidad'] ?? null;
        $category = $input['categoria'] ?? 'otro';
        if (!in_array($unit, ['kg','l','unidad'], true)
            || !in_array($category, ['abono','alimento','medicamento','otro'], true)) {
            return agrosyncLivestockReply($response, 400, ['code'=>'INSUMO_INVALIDO']);
        }
        $fields += ['unidad'=>$unit, 'categoria'=>$category];
        $compare += ['unidad'=>$unit, 'categoria'=>$category];
    }
    if ($id === null && in_array($kind, ['lotes', 'insumos'], true)) {
        $fields += ['finca_id'=>$farm, 'estado'=>'activo'];
    }

    $db = DBManagerFactory::getInstance();
    $lockScope = $kind === 'fincas' ? 'fincas' : $farm;
    $lock = $db->quoted('agrosync-reg-' . substr(hash('sha256', $lockScope), 0, 45));
    $acquired = agrosyncInventoryRows($db, "SELECT GET_LOCK($lock, 5) AS acquired");
    if ((string) ($acquired[0]['acquired'] ?? '') !== '1') return $response->withStatus(503);
    try {
        [$module, $from, $farmColumn] = agrosyncRecordQuery($kind);
        $key = $id ?? $op;
        $original = agrosyncRecords($db, $kind, $farm, $key)[0] ?? null;
        if ($id !== null && !$original) return $response->withStatus(403);
        if (isset($lot) && !agrosyncRecords($db, 'lotes', $farm, $lot)) return $response->withStatus(403);
        if ($kind === 'insumos' && $original && $original['unidad'] !== $unit) {
            return agrosyncLivestockReply($response, 409, ['code'=>'UNIDAD_INMUTABLE']);
        }
        if ($original) {
            $same = !array_diff_assoc($compare, array_intersect_key($original, $compare));
            if ($same && ($id !== null || BeanFactory::getBean($module, $key)->created_by === $user->id)) {
                return agrosyncLivestockReply($response, 200, ['operationId'=>$op, 'registro'=>$original]);
            }
            if ($id === null || $original['version'] !== $version) {
                return agrosyncLivestockReply($response, 409, ['code'=>'VERSION_INCOMPATIBLE']);
            }
        }

        $keySql = $db->quoted($key);
        if ($id === null) {
            $table = explode(' ', $from, 2)[0];
            if (agrosyncInventoryRows($db, "SELECT id FROM $table WHERE id=$keySql LIMIT 1")) {
                return agrosyncLivestockReply($response, 409, ['code'=>'OPERACION_REUTILIZADA']);
            }
        }
        if (isset($column)) {
            $codeSql = $db->quoted($fields[$column]);
            $scope = $kind === 'fincas' ? '' : " AND $farmColumn=" . $db->quoted($farm);
            if (agrosyncInventoryRows($db, "SELECT r.id FROM $from WHERE r.deleted=0$scope
                AND r.id<>$keySql AND LOWER(TRIM(r.$column))=LOWER($codeSql) LIMIT 1")) {
                return agrosyncLivestockReply($response, 409, ['code'=>'CODIGO_DUPLICADO']);
            }
        }
        $bean = $id === null ? BeanFactory::newBean($module) : BeanFactory::getBean($module, $id);
        if ($id === null) {
            $bean->id = $key;
            $bean->new_with_id = true;
        }
        $groups = $id === null ? agrosyncRecordGroups($db, 'ags_Fincas', $farm) : agrosyncRecordGroups($db, $module, $id);
        $db->query('START TRANSACTION', true);
        try {
            foreach ($fields as $name => $value) {
                $bean->$name = $value;
            }
            if ($bean->save() !== $key) throw new RuntimeException('El registro quedó sin confirmar.');
            agrosyncSetRecordGroups($bean, $groups);
            $saved = agrosyncRecords($db, $kind, $farm, $key)[0] ?? null;
            if (!$saved) throw new RuntimeException('El registro quedó fuera del alcance de la finca.');
            $db->query('COMMIT', true);
        } catch (Throwable $failure) {
            $db->query('ROLLBACK');
            return $response->withStatus(503);
        }
        return agrosyncLivestockReply($response, $id === null ? 201 : 200, ['operationId'=>$op, 'registro'=>$saved]);
    } finally {
        $db->query("SELECT RELEASE_LOCK($lock)");
    }
});

$app->get('/agrosync/registros/fincas/alta', function ($request, $response) {
    $user = agrosyncDelegatedUser($request);
    if (!$user) return $response->withStatus(403);
    return agrosyncLivestockReply($response, 200, ['permitida'=>$user->isAdmin()]);
});

$app->post('/agrosync/registros/fincas/alta', function ($request, $response) {
    $user = agrosyncDelegatedUser($request);
    if (!$user || !$user->isAdmin()) return $response->withStatus(403);
    $GLOBALS['current_user'] = $user;
    $input = $request->getParsedBody();
    $operation = $input['operationId'] ?? null;
    $name = $input['nombre'] ?? null;
    $code = $input['codigo'] ?? null;
    if (!is_string($operation) || !preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/D', $operation)
        || !agrosyncLivestockText($name, 255, true) || !agrosyncLivestockText($code, 30, true)) {
        return agrosyncLivestockReply($response, 400, ['code'=>'FINCA_INVALIDA']);
    }
    $name = trim($name);
    $code = trim($code);
    $db = DBManagerFactory::getInstance();
    $key = $db->quoted($operation);
    $lock = $db->quoted('agrosync-reg-' . substr(hash('sha256', 'fincas'), 0, 45));
    $result = agrosyncInventoryRows($db, "SELECT GET_LOCK($lock, 5) AS acquired");
    if ((string) ($result[0]['acquired'] ?? '') !== '1') return $response->withStatus(503);
    try {
        $previous = agrosyncInventoryRows($db, "SELECT name,codigo_finca,created_by,deleted FROM ags_fincas WHERE id=$key")[0] ?? null;
        if ($previous) {
            $same = !$previous['deleted'] && $previous['created_by'] === $user->id && $previous['name'] === $name
                && $previous['codigo_finca'] === $code;
            return agrosyncLivestockReply($response, $same ? 200 : 409,
                $same ? ['operationId'=>$operation,'fincaId'=>$operation] : ['code'=>'OPERACION_REUTILIZADA']);
        }
        $codeSql = $db->quoted($code);
        if (agrosyncInventoryRows($db, "SELECT id FROM ags_fincas WHERE deleted=0
            AND LOWER(TRIM(codigo_finca))=LOWER($codeSql) LIMIT 1")) {
            return agrosyncLivestockReply($response, 409, ['code'=>'CODIGO_DUPLICADO']);
        }
        $role = agrosyncInventoryRows($db, "SELECT id FROM acl_roles
            WHERE deleted=0 AND name='AgroSync Administrador de finca'");
        if (count($role) !== 1) return agrosyncLivestockReply($response, 409, ['code'=>'CONFIGURACION_ROL']);
        $db->query('START TRANSACTION', true);
        try {
            $farm = BeanFactory::newBean('ags_Fincas');
            $farm->id = $operation;
            $farm->new_with_id = true;
            $farm->name = $name;
            $farm->codigo_finca = $code;
            $farm->estado = 'Activa';
            $farm->save();
            $group = BeanFactory::newBean('SecurityGroups');
            $group->name = mb_substr('AgroSync ' . $code . ' · ' . $name, 0, 255);
            $group->save();
            if (!$group->load_relationship('aclroles') || !$group->load_relationship('users')) {
                throw new RuntimeException('AgroSync: no se pudieron configurar los vínculos.');
            }
            $group->aclroles->add($role[0]['id']);
            $group->users->add($user);
            agrosyncSetRecordGroups($farm, [$group->id]);
            $group->aclroles->load();
            require_once 'modules/SecurityGroups/SecurityGroup.php';
            $memberships = SecurityGroup::getUserSecurityGroups($user->id);
            if (!in_array($role[0]['id'], $group->aclroles->get(), true)
                || !isset($memberships[$group->id])
                || !agrosyncRecords($db, 'fincas', $operation, $operation)) {
                throw new RuntimeException('AgroSync: el alta no se pudo confirmar.');
            }
            $db->query('COMMIT', true);
        } catch (Throwable $failure) {
            $db->query('ROLLBACK');
            return $response->withStatus(503);
        }
        return agrosyncLivestockReply($response, 201, ['operationId'=>$operation,'fincaId'=>$operation]);
    } finally {
        $db->query("SELECT RELEASE_LOCK($lock)");
    }
});
