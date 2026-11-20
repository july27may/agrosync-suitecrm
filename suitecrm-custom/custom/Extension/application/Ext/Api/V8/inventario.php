<?php

function agrosyncInventoryRows($db, string $query): array
{
    $result = $db->query($query, true);
    $rows = [];
    while ($row = $db->fetchByAssoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function agrosyncRecordGroups($db, string $module, string $id): array
{
    $record = $db->quoted($id);
    $kind = $db->quoted($module);
    return array_column(agrosyncInventoryRows($db, "SELECT DISTINCT g.id
        FROM securitygroups_records r JOIN securitygroups g ON r.securitygroup_id=g.id AND g.deleted=0
        WHERE r.record_id=$record AND r.module=$kind AND r.deleted=0"), 'id');
}

function agrosyncSetRecordGroups($bean, array $groups): void
{
    if (!$bean->load_relationship('SecurityGroups')) {
        throw new RuntimeException('No se cargaron los grupos del registro.');
    }
    $bean->SecurityGroups->load();
    foreach (array_diff($bean->SecurityGroups->get(), $groups) as $unrelated) {
        $bean->SecurityGroups->delete($bean->id, $unrelated);
    }
    $bean->SecurityGroups->load();
    $missing = array_values(array_diff($groups, $bean->SecurityGroups->get()));
    if ($missing) $bean->SecurityGroups->add($missing);
    $bean->SecurityGroups->load();
    $linked = $bean->SecurityGroups->get();
    if (array_diff($linked, $groups) || array_diff($groups, $linked)) {
        throw new RuntimeException('No se confirmaron los grupos del registro.');
    }
}

function agrosyncInventoryBalance(string $alias): string
{
    return "COALESCE(SUM(CASE $alias.tipo_movimiento WHEN 'entrada' THEN $alias.cantidad
        WHEN 'salida' THEN -$alias.cantidad ELSE 0 END), 0)";
}

$app->get('/agrosync/inventario', function ($request, $response) {
    $user = agrosyncDelegatedUser($request);
    $farmId = $request->getQueryParam('fincaId');
    if (!$user || !agrosyncLivestockId($farmId) || !agrosyncLivestockRole($user, $farmId)) {
        return $response->withStatus(403);
    }
    $db = DBManagerFactory::getInstance();
    $farm = $db->quoted($farmId);
    $items = agrosyncInventoryRows($db, "SELECT i.id, i.name AS nombre, i.codigo_insumo AS codigo,
        i.unidad, i.estado FROM ags_insumos i WHERE i.deleted=0 AND i.finca_id=$farm ORDER BY i.name,i.id");
    $history = agrosyncInventoryRows($db, "SELECT m.id, m.insumo_id AS insumoId,
        m.tipo_movimiento AS tipo, m.cantidad, m.fecha_hora AS fecha,
        m.movimiento_origen_id AS origenId, m.referencia_documento AS referencia
        FROM ags_movimientos_inventario m JOIN ags_insumos i ON i.id=m.insumo_id
        WHERE m.deleted=0 AND i.deleted=0 AND i.finca_id=$farm ORDER BY m.fecha_hora DESC,m.id");
    $balances = [];
    foreach ($history as $movement) {
        $itemId = $movement['insumoId'];
        $current = $balances[$itemId] ?? '0.000';
        $balances[$itemId] = match ($movement['tipo']) {
            'entrada' => bcadd($current, $movement['cantidad'], 3),
            'salida' => bcsub($current, $movement['cantidad'], 3),
            default => $current,
        };
    }
    foreach ($items as &$item) {
        $item['saldo'] = $balances[$item['id']] ?? '0.000';
    }
    unset($item);
    return agrosyncLivestockReply($response, 200, ['insumos'=>$items,'movimientos'=>$history]);
});

$app->post('/agrosync/inventario/movimientos', function ($request, $response) {
    $user = agrosyncDelegatedUser($request);
    $input = $request->getParsedBody();
    $id = $input['operationId'] ?? null;
    $farm = $input['fincaId'] ?? null;
    $item = $input['insumoId'] ?? null;
    $type = $input['tipo'] ?? null;
    $units = $input['unidad'] ?? null;
    $amount = $input['cantidad'] ?? null;
    $reference = $input['referencia'] ?? '';
    $when = $input['fecha'] ?? null;
    $origin = $input['origenId'] ?? null;
    if (!$user || !agrosyncLivestockId($farm)) return $response->withStatus(403);
    $role = agrosyncLivestockRole($user, $farm);
    if (!in_array($role, ['ADMINISTRADOR_FINCA','REGISTRADOR'], true)
        || ($origin !== null && $role !== 'ADMINISTRADOR_FINCA')) return $response->withStatus(403);
    if (!is_string($id) || !preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/D', $id)
        || !agrosyncLivestockId($item) || ($origin !== null && !agrosyncLivestockId($origin))
        || !in_array($type, ['entrada','salida'], true)
        || !in_array($units, ['kg','l','unidad'], true)
        || !is_string($amount) || !preg_match('/^[0-9]{1,11}(?:\.[0-9]{1,3})?$/D', $amount)
        || bccomp($amount, '0', 3) <= 0 || !agrosyncLivestockText($reference, 100)) {
        return agrosyncLivestockReply($response, 400, ['code'=>'MOVIMIENTO_INVALIDO']);
    }
    $recordedAt = null;
    if ($when !== null) {
        if (!is_string($when) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?Z$/D', $when)
            || !agrosyncLivestockDate(substr($when, 0, 10))) {
            return agrosyncLivestockReply($response, 400, ['code'=>'FECHA_INVALIDA']);
        }
        try {
            $recordedAt = (new DateTimeImmutable($when))->format('Y-m-d H:i:s');
        } catch (Exception $invalidDate) {
            return agrosyncLivestockReply($response, 400, ['code'=>'FECHA_INVALIDA']);
        }
    }
    $amount = bcadd($amount, '0', 3);
    $db = DBManagerFactory::getInstance();
    $key = $db->quoted($id);
    $itemKey = $db->quoted($item);
    $lock = $db->quoted('agrosync-inv-' . substr(hash('sha256', $item), 0, 45));
    $locked = agrosyncInventoryRows($db, "SELECT GET_LOCK($lock, 5) AS acquired");
    if ((string) ($locked[0]['acquired'] ?? '') !== '1') return $response->withStatus(503);
    try {
        $supply = BeanFactory::getBean('ags_insumos', $item);
        if (empty($supply->id) || $supply->finca_id !== $farm) return $response->withStatus(403);
        $prior = agrosyncInventoryRows($db, "SELECT * FROM ags_movimientos_inventario WHERE id=$key")[0] ?? null;
        $receipt = ['operationId'=>$id, 'crmId'=>$id];
        if ($prior) {
            $same = !$prior['deleted'] && $prior['created_by'] === $user->id && $prior['insumo_id'] === $item
                && $prior['tipo_movimiento'] === $type && bccomp($prior['cantidad'], $amount, 3) === 0
                && (string) $prior['movimiento_origen_id'] === (string) $origin
                && (string) $prior['referencia_documento'] === $reference && $supply->unidad === $units
                && ($recordedAt === null || $prior['fecha_hora'] === $recordedAt);
            return agrosyncLivestockReply($response, $same ? 200 : 409, $same ? $receipt : ['code'=>'OPERACION_REUTILIZADA']);
        }
        if ($supply->unidad !== $units || $supply->estado !== 'activo') {
            return agrosyncLivestockReply($response, 409, ['code'=>'INSUMO_CAMBIADO']);
        }
        if ($origin !== null) {
            $originKey = $db->quoted($origin);
            $original = agrosyncInventoryRows($db, "SELECT tipo_movimiento,cantidad,movimiento_origen_id
                FROM ags_movimientos_inventario WHERE id=$originKey AND insumo_id=$itemKey AND deleted=0")[0] ?? null;
            $compensated = agrosyncInventoryRows($db, "SELECT id FROM ags_movimientos_inventario
                WHERE movimiento_origen_id=$originKey AND deleted=0 LIMIT 1");
            if (!$original || $original['movimiento_origen_id'] || $compensated
                || $original['tipo_movimiento'] === $type || bccomp($original['cantidad'], $amount, 3) !== 0) {
                return agrosyncLivestockReply($response, 409, ['code'=>'COMPENSACION_INVALIDA']);
            }
        }
        $balance = agrosyncInventoryBalance('m');
        $current = agrosyncInventoryRows($db, "SELECT $balance AS saldo FROM ags_movimientos_inventario m
            WHERE m.insumo_id=$itemKey AND m.deleted=0")[0]['saldo'];
        if ($type === 'salida' && bccomp($current, $amount, 3) < 0) {
            return agrosyncLivestockReply($response, 409, ['code'=>'SALDO_INSUFICIENTE']);
        }
        $movement = BeanFactory::newBean('ags_movimientos_inventario');
        $movement->id = $id;
        $movement->new_with_id = true;
        $movement->name = ucfirst($type) . ' ' . $supply->name;
        $movement->insumo_id = $item;
        $movement->tipo_movimiento = $type;
        $movement->cantidad = $amount;
        $movement->fecha_hora = $recordedAt ?? TimeDate::getInstance()->nowDb();
        $movement->referencia_documento = $reference;
        $movement->movimiento_origen_id = $origin;
        $groups = agrosyncRecordGroups($db, 'ags_Fincas', $farm);
        if (!$groups) return $response->withStatus(403);
        $db->query('START TRANSACTION', true);
        try {
            if ($movement->save() !== $id ||
                !agrosyncInventoryRows($db, "SELECT id FROM ags_movimientos_inventario WHERE id=$key AND deleted=0")) {
                throw new RuntimeException('El movimiento quedó sin confirmar.');
            }
            agrosyncSetRecordGroups($movement, $groups);
            $db->query('COMMIT', true);
        } catch (Throwable $failure) {
            $db->query('ROLLBACK');
            return $response->withStatus(503);
        }
        return agrosyncLivestockReply($response, 201, $receipt);
    } finally {
        $db->query("SELECT RELEASE_LOCK($lock)");
    }
});
