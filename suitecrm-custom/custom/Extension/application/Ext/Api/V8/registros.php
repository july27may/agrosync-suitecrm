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
        'animales' => ['ags_animales', 'ags_animales r', 'r.finca_id',
            'r.codigo_animal AS codigo,r.sexo,r.categoria,r.fecha_nacimiento AS nacimiento,r.madre_id AS madreId,r.raza,r.estado'],
        'eventos' => ['ags_eventos_sanitarios', 'ags_eventos_sanitarios r JOIN ags_animales a ON a.id=r.animal_id AND a.deleted=0',
            'a.finca_id', 'r.animal_id AS animalId,r.fecha,r.tipo_evento AS tipo,r.producto,
                r.responsable_id AS responsableId,r.referencia_certificado AS certificado,r.description AS observaciones'],
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

$app->get('/agrosync/registros/{kind:fincas|lotes|labores|insumos|cosechas|animales|eventos}', function ($request, $response, $args) {
    $user = agrosyncDelegatedUser($request);
    $farm = $request->getQueryParam('fincaId');
    if (!$user || !agrosyncLivestockId($farm) || !agrosyncLivestockRole($user, $farm)) return $response->withStatus(403);
    $db = DBManagerFactory::getInstance();
    $kind = $args['kind'];
    $rows = agrosyncRecords($db, $kind, $farm);
    if (in_array($kind, ['animales','eventos'], true)) {
        [$module] = agrosyncRecordQuery($kind);
        $rows = array_values(array_filter($rows, static function ($row) use ($module) {
            $bean = BeanFactory::getBean($module, $row['id']);
            $animal = isset($row['animalId']) ? BeanFactory::getBean('ags_animales', $row['animalId']) : $bean;
            return !empty($bean->id) && $bean->ACLAccess('view') && !empty($animal->id) && $animal->ACLAccess('view');
        }));
    }
    return agrosyncLivestockReply($response, 200, ['registros'=>$rows]);
});

$app->post('/agrosync/registros/{kind:fincas|lotes|labores|insumos|cosechas|animales|eventos}', function ($request, $response, $args) {
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
        || (in_array($kind, ['cosechas','animales','eventos'], true) && $id === null)
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
        $amount = bcadd($amount, '0', 3);
        $rate = bcadd($rate, '0', 2);
        $fields += ['lote_id'=>$lot,'recolector_id'=>$collector,'fecha'=>$date,'estado_cafe'=>$state,
            'cantidad_kg'=>$amount,'tarifa_kg'=>$rate];
        $compare += ['loteId'=>$lot,'recolectorId'=>$collector,'fecha'=>$date,'estadoCafe'=>$state,
            'pesoKg'=>$amount,'tarifaKg'=>$rate];
    } elseif ($kind === 'animales') {
        $sex = $input['sexo'] ?? null;
        $category = $input['categoria'] ?? null;
        $birth = $input['nacimiento'] ?? null;
        $mother = $input['madreId'] ?? null;
        $breed = $input['raza'] ?? null;
        $state = $input['estado'] ?? null;
        if (!agrosyncLivestockText($code, 30, true) || !in_array($sex, ['hembra','macho'], true)
            || !in_array($category, [null,'ternero','ternera','novillo','novilla','vaca','toro'], true)
            || ($birth !== null && (!agrosyncLivestockDate($birth) || $birth > date('Y-m-d')))
            || ($mother !== null && !agrosyncLivestockId($mother)) || !agrosyncLivestockText($breed, 100)
            || !in_array($state, ['activo','vendido','fallecido','inactivo'], true)) {
            return agrosyncLivestockReply($response, 400, ['code'=>'GANADERIA_INVALIDA']);
        }
        $column = 'codigo_animal';
        $fields += ['codigo_animal'=>trim($code),'sexo'=>$sex,'categoria'=>$category,'fecha_nacimiento'=>$birth,
            'madre_id'=>$mother,'raza'=>$breed,'estado'=>$state];
    } elseif ($kind === 'eventos') {
        $animal = $input['animalId'] ?? null;
        $date = $input['fecha'] ?? null;
        $type = $input['tipo'] ?? null;
        $product = $input['producto'] ?? null;
        $responsible = $input['responsableId'] ?? null;
        $certificate = $input['certificado'] ?? null;
        $notes = $input['observaciones'] ?? null;
        if (!agrosyncLivestockId($animal) || !agrosyncLivestockDate($date)
            || !in_array($type, ['vacunacion','tratamiento','revision','otro'], true)
            || !agrosyncLivestockText($product, 150) || !agrosyncLivestockText($certificate, 100)
            || !agrosyncLivestockText($notes, 4000) || ($responsible !== null && !agrosyncLivestockId($responsible))) {
            return agrosyncLivestockReply($response, 400, ['code'=>'GANADERIA_INVALIDA']);
        }
        $fields += ['animal_id'=>$animal,'fecha'=>$date,'tipo_evento'=>$type,'producto'=>$product,
            'responsable_id'=>$responsible,'referencia_certificado'=>$certificate,'description'=>$notes];
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
    $lock = $db->quoted(in_array($kind, ['animales','eventos'], true)
        ? 'agrosync-ganado-' . $farm : 'agrosync-reg-' . substr(hash('sha256', $lockScope), 0, 45));
    $acquired = agrosyncInventoryRows($db, "SELECT GET_LOCK($lock, 5) AS acquired");
    if ((string) ($acquired[0]['acquired'] ?? '') !== '1') return $response->withStatus(503);
    try {
        [$module, $from, $farmColumn] = agrosyncRecordQuery($kind);
        $key = $id ?? $op;
        $operationKey = $db->quoted($op);
        $fingerprint = agrosyncRecordVersion([$user->id, $farm, $kind, $key, $version, $fields]);
        $original = agrosyncRecords($db, $kind, $farm, $key)[0] ?? null;
        if ($id !== null && !$original) return $response->withStatus(403);
        if (in_array($kind, ['animales','eventos'], true)) {
            $target = BeanFactory::getBean($module, $id);
            if (!$target || !$target->ACLAccess('edit')) return $response->withStatus(403);
        }
        if ($id !== null) {
            $previous = $db->fetchByAssoc($db->query("SELECT actor_id,finca_id,fingerprint,receipt
                FROM agrosync_acuses_edicion WHERE operation_id=$operationKey", true), false);
            if ($previous) {
                if ($previous['actor_id'] !== $user->id || $previous['finca_id'] !== $farm || $previous['fingerprint'] !== $fingerprint) {
                    return agrosyncLivestockReply($response, 409, ['code'=>'OPERACION_REUTILIZADA']);
                }
                return agrosyncLivestockReply($response, 200, json_decode($previous['receipt'], true, 512, JSON_THROW_ON_ERROR));
            }
        }
        if (isset($collector)) {
            $contact = BeanFactory::getBean('Contacts', $collector);
            if (empty($contact->id) || !$contact->ACLAccess('view')) return $response->withStatus(403);
        }
        if (isset($lot) && !agrosyncRecords($db, 'lotes', $farm, $lot)) return $response->withStatus(403);
        if (in_array($kind, ['animales','eventos'], true)) {
            $reference = $kind === 'animales' ? $mother : $animal;
            $visited = $kind === 'animales' ? [$id=>true] : [];
            while ($reference !== null && $reference !== '') {
                if (isset($visited[$reference])) return agrosyncLivestockReply($response, 409, ['code'=>'REFERENCIA_CICLICA']);
                $relative = BeanFactory::getBean('ags_animales', $reference);
                if (empty($relative->id) || $relative->finca_id !== $farm || !$relative->ACLAccess('view')
                    || ($kind === 'animales' && count($visited) === 1 && $relative->sexo !== 'hembra')) {
                    return $response->withStatus(403);
                }
                $visited[$reference] = true;
                $reference = $kind === 'animales' ? $relative->madre_id : null;
            }
            if (isset($responsible)) {
                $contact = BeanFactory::getBean('Contacts', $responsible);
                if (empty($contact->id) || !$contact->ACLAccess('view')) return $response->withStatus(403);
            }
        }
        if ($kind === 'insumos' && $original && $original['unidad'] !== $unit) {
            return agrosyncLivestockReply($response, 409, ['code'=>'UNIDAD_INMUTABLE']);
        }
        if ($original) {
            $same = !array_diff_assoc($compare, array_intersect_key($original, $compare));
            if ($same && $id === null && BeanFactory::getBean($module, $key)->created_by === $user->id) {
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
            $receipt = ['operationId'=>$op, 'registro'=>$saved];
            if ($id !== null) {
                $actorSql = $db->quoted($user->id);
                $farmSql = $db->quoted($farm);
                $hashSql = $db->quoted($fingerprint);
                $receiptSql = $db->quoted(json_encode($receipt, JSON_THROW_ON_ERROR));
                if (!$db->query("INSERT INTO agrosync_acuses_edicion (operation_id,actor_id,finca_id,fingerprint,receipt)
                    VALUES ($operationKey,$actorSql,$farmSql,$hashSql,$receiptSql)", false)) {
                    throw new RuntimeException('No se guardó el acuse.');
                }
            }
            $db->query('COMMIT', true);
        } catch (Throwable $failure) {
            $db->query('ROLLBACK');
            return $response->withStatus(503);
        }
        return agrosyncLivestockReply($response, $id === null ? 201 : 200, $receipt);
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
