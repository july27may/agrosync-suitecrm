<?php

function agrosyncAuditUuid($value): bool
{
    return is_string($value) && preg_match('/^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/D', $value);
}

function agrosyncAuditWriteAllowed(string $module, string $role): bool
{
    return $role === 'ADMINISTRADOR_FINCA' || ($role === 'REGISTRADOR' &&
        in_array($module, ['cosechas', 'ganaderia/eventos', 'inventario/movimientos', 'registros/labores'], true));
}

$app->get('/agrosync/bitacora', function ($request, $response) {
    $user = agrosyncDelegatedUser($request);
    $farm = $request->getQueryParam('fincaId');
    if (!$user || !agrosyncLivestockId($farm) || !agrosyncLivestockRole($user, $farm)) {
        return $response->withStatus(403);
    }
    $db = DBManagerFactory::getInstance();
    $cursor = $request->getQueryParam('antes');
    $operation = $request->getQueryParam('operationId');
    if (($cursor !== null && (!is_string($cursor) || !preg_match('/^[0-9]{1,20}$/D', $cursor)))
        || ($operation !== null && !agrosyncAuditUuid($operation))) {
        return agrosyncLivestockReply($response, 400, ['code'=>'SOLICITUD_INVALIDA']);
    }
    $where = 'b.finca=' . $db->quoted($farm);
    if ($cursor !== null) $where .= " AND b.id < $cursor";
    if ($operation !== null) $where .= ' AND b.operacion=' . $db->quoted($operation);
    $rows = agrosyncInventoryRows($db, "SELECT CAST(b.id AS CHAR) AS id,b.intento AS intentoId,
        b.operacion AS operationId,b.relacionada AS relatedOperationId,b.usuario AS usuarioId,
        COALESCE(u.user_name,b.usuario) AS usuario,b.finca AS fincaId,b.modulo,b.evento,b.causa,
        CONCAT(REPLACE(b.fecha,' ','T'),'Z') AS fecha
        FROM agrosync_bitacora b LEFT JOIN users u ON u.id=b.usuario AND u.deleted=0
        WHERE $where ORDER BY b.id DESC LIMIT 51");
    $next = count($rows) > 50 ? $rows[49]['id'] : null;
    return agrosyncLivestockReply($response, 200, ['eventos'=>array_slice($rows, 0, 50), 'siguiente'=>$next]);
});

$app->post('/agrosync/bitacora', function ($request, $response) {
    $user = agrosyncDelegatedUser($request);
    $input = $request->getParsedBody();
    $farm = $input['fincaId'] ?? null;
    $operation = $input['operationId'] ?? null;
    $attempt = $input['intentoId'] ?? null;
    $related = $input['relatedOperationId'] ?? null;
    $module = $input['modulo'] ?? null;
    $event = $input['evento'] ?? null;
    $code = $input['causa'] ?? null;
    $modules = ['cosechas','ganaderia/animales','ganaderia/eventos','inventario/movimientos','registros/fincas',
        'registros/fincas/alta','registros/lotes','registros/labores','registros/insumos','registros/cosechas',
        'registros/animales','registros/eventos'];
    $errors = ['CRM_NO_DISPONIBLE','CRM_TIEMPO_AGOTADO','CRM_TIMEOUT','CRM_RESPUESTA_INVALIDA',
        'AUTENTICACION_REQUERIDA','ACCESO_DENEGADO','COSECHA_CONFLICTIVA','GANADERIA_CONFLICTIVA',
        'GANADERIA_CONFLICTO','INVENTARIO_CONFLICTO','REGISTRO_CONFLICTIVO','VERSION_INCOMPATIBLE','CODIGO_DUPLICADO',
        'OPERACION_REUTILIZADA','REFERENCIA_CICLICA','UNIDAD_INMUTABLE','CONFIGURACION_ROL','SALDO_INSUFICIENTE',
        'INSUMO_CAMBIADO','COMPENSACION_INVALIDA','ERROR_ENVIO'];
    $validCause = match ($event) {
        'INTENTO', 'CONFIRMADO' => $code === null && $related === null,
        'ERROR', 'CONFLICTO' => in_array($code, $errors, true) && $related === null,
        'RESOLUCION' => ($code === 'PROPONER_REVISION' && agrosyncAuditUuid($related) && $related !== $operation)
            || ($related === null && in_array($code, ['CONSERVAR_CENTRAL','DESCARTAR_PENDIENTE'], true)),
        default => false,
    };
    if (!$user || !agrosyncLivestockId($farm)) return $response->withStatus(403);
    if (!agrosyncAuditUuid($operation) || !agrosyncAuditUuid($attempt)
        || !in_array($module, $modules, true) || !$validCause) {
        return agrosyncLivestockReply($response, 400, ['code'=>'INCIDENCIA_INVALIDA']);
    }
    $allowed = $module === 'registros/fincas/alta'
        ? $user->isAdmin() && $farm === $operation && $event !== 'RESOLUCION'
        : agrosyncAuditWriteAllowed($module, agrosyncLivestockRole($user, $farm) ?? '');
    if (!$allowed) return $response->withStatus(403);

    $db = DBManagerFactory::getInstance();
    [$attemptKey, $opKey, $userKey, $farmKey, $moduleKey, $eventKey] =
        array_map(static fn ($value) => $db->quoted($value), [$attempt,$operation,$user->id,$farm,$module,$event]);
    if ($event !== 'INTENTO' && $event !== 'RESOLUCION') {
        $start = agrosyncInventoryRows($db, "SELECT id FROM agrosync_bitacora WHERE intento=$attemptKey AND operacion=$opKey
            AND usuario=$userKey AND finca=$farmKey AND modulo=$moduleKey AND evento='INTENTO'");
        if (!$start) return $response->withStatus(403);
    }
    if ($event === 'RESOLUCION') {
        $conflict = agrosyncInventoryRows($db, "SELECT id FROM agrosync_bitacora WHERE operacion=$opKey
            AND usuario=$userKey AND finca=$farmKey AND modulo=$moduleKey AND evento='CONFLICTO' LIMIT 1");
        if (!$conflict) return agrosyncLivestockReply($response, 409, ['code'=>'CONFLICTO_NO_REGISTRADO']);
    }

    $phaseKey = $db->quoted(in_array($event, ['INTENTO','RESOLUCION'], true) ? $event : 'RESULTADO');
    $relatedKey = $related === null ? 'NULL' : $db->quoted($related);
    $codeKey = $code === null ? 'NULL' : $db->quoted($code);
    $saved = $db->query("INSERT INTO agrosync_bitacora (intento,operacion,relacionada,usuario,finca,modulo,evento,fase,causa,fecha)
        VALUES ($attemptKey,$opKey,$relatedKey,$userKey,$farmKey,$moduleKey,$eventKey,$phaseKey,$codeKey,UTC_TIMESTAMP(6))
        ON DUPLICATE KEY UPDATE id=id", false);
    if (!$saved) return $response->withStatus(503);
    $stored = agrosyncInventoryRows($db, "SELECT operacion,relacionada,usuario,finca,modulo,causa,evento FROM agrosync_bitacora
        WHERE intento=$attemptKey AND fase=$phaseKey")[0] ?? null;
    if (!$stored || $stored['operacion'] !== $operation || $stored['relacionada'] !== $related
        || $stored['usuario'] !== $user->id || $stored['finca'] !== $farm || $stored['modulo'] !== $module
        || $stored['causa'] !== $code || $stored['evento'] !== $event) {
        return agrosyncLivestockReply($response, 409, ['code'=>'OPERACION_REUTILIZADA']);
    }
    return agrosyncLivestockReply($response, 200, ['intentoId'=>$attempt,'evento'=>$event]);
});
