<?php

$app->post('/agrosync/cosechas', function ($request, $response) {
    $reply = fn ($status, $data) => $response->withHeader('Cache-Control', 'no-store')->withJson($data, $status);
    $clientId = $request->getAttribute('oauth_client_id');
    $userId = $request->getAttribute('oauth_user_id');
    if (!is_string($clientId) || !is_string($userId) || $userId === '') {
        return $reply(403, ['code' => 'ACCESO_DENEGADO']);
    }
    $client = BeanFactory::getBean('OAuth2Clients', $clientId);
    $user = BeanFactory::getBean('Users', $userId);
    if (empty($client->id) || $client->allowed_grant_type !== 'authorization_code'
        || empty($user->id) || $user->status !== 'Active' || !$user->isEnabled()) {
        return $reply(403, ['code' => 'ACCESO_DENEGADO']);
    }

    $input = $request->getParsedBody();
    $operation = $input['operationId'] ?? null;
    $farmId = $input['fincaId'] ?? null;
    $lotId = $input['loteId'] ?? null;
    $contactId = $input['recolectorId'] ?? null;
    $date = $input['fecha'] ?? null;
    $weight = $input['pesoGramos'] ?? null;
    $rate = $input['tarifaCentavos'] ?? null;
    $state = $input['estadoCafe'] ?? null;
    $validId = fn ($id) => is_string($id) && preg_match('/^[A-Za-z0-9_-]{1,36}$/D', $id);
    if (!is_string($operation) || !preg_match('/^[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}$/D', $operation)
        || !$validId($farmId) || !$validId($lotId) || !$validId($contactId)
        || !is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)
        || !checkdate((int) substr($date, 5, 2), (int) substr($date, 8), (int) substr($date, 0, 4))
        || !is_int($weight) || $weight < 1 || $weight > 99999999999999
        || !is_int($rate) || $rate < 1 || $rate > 99999999999999
        || !in_array($state, ['cereza', 'pergamino'], true)) {
        return $reply(400, ['code' => 'COSECHA_INVALIDA']);
    }

    $GLOBALS['current_user'] = $user;
    $farm = BeanFactory::getBean('ags_Fincas', $farmId);
    if (empty($farm->id) || !$farm->ACLAccess('view')) {
        return $reply(403, ['code' => 'ACCESO_DENEGADO']);
    }

    require_once 'modules/SecurityGroups/SecurityGroup.php';
    $memberships = SecurityGroup::getUserSecurityGroups($userId);
    $db = DBManagerFactory::getInstance();
    $farmKey = $db->quoted($farmId);
    $groups = $db->query("SELECT DISTINCT sr.securitygroup_id, ar.name
        FROM securitygroups_records sr
        JOIN securitygroups sg ON sg.id = sr.securitygroup_id AND sg.deleted = 0
        JOIN securitygroups_acl_roles gr ON gr.securitygroup_id = sg.id AND gr.deleted = 0
        JOIN acl_roles ar ON ar.id = gr.role_id AND ar.deleted = 0
        WHERE sr.deleted = 0 AND sr.module = 'ags_Fincas' AND sr.record_id = $farmKey
          AND ar.name IN ('AgroSync Administrador de finca', 'AgroSync Registrador', 'AgroSync Consulta')", true);
    $profiles = [];
    $groupsForFarm = [];
    while ($row = $db->fetchByAssoc($groups)) {
        $group = $row['securitygroup_id'];
        $groupsForFarm[$group] = true;
        if (isset($memberships[$group])) $profiles[$row['name']] = true;
    }
    if (count($profiles) > 1) return $reply(409, ['code' => 'PERFILES_FINCA_INCOMPATIBLES']);
    if (!isset($profiles['AgroSync Administrador de finca']) && !isset($profiles['AgroSync Registrador'])) {
        return $reply(403, ['code' => 'ACCESO_DENEGADO']);
    }

    $lot = BeanFactory::getBean('ags_lotes', $lotId);
    $contact = BeanFactory::getBean('Contacts', $contactId);
    if (empty($lot->id) || $lot->finca_id !== $farmId || !$lot->ACLAccess('view')
        || empty($contact->id) || !$contact->ACLAccess('view')) {
        return $reply(403, ['code' => 'REFERENCIA_NO_AUTORIZADA']);
    }

    $amount = intdiv($weight, 1000) . '.' . str_pad((string) ($weight % 1000), 3, '0', STR_PAD_LEFT);
    $price = intdiv($rate, 100) . '.' . str_pad((string) ($rate % 100), 2, '0', STR_PAD_LEFT);
    $lock = $db->quoted('agrosync-cosecha-' . $operation);
    $acquired = $db->fetchByAssoc($db->query("SELECT GET_LOCK($lock, 3) AS obtained", true));
    if ((int) ($acquired['obtained'] ?? 0) !== 1) return $reply(503, ['code' => 'OPERACION_OCUPADA']);

    $transaction = false;
    try {
        $key = $db->quoted($operation);
        $row = $db->fetchByAssoc($db->query("SELECT deleted, created_by, lote_id, recolector_id, fecha,
            cantidad_kg, tarifa_kg, estado_cafe FROM ags_cosechas WHERE id = $key", true));
        if ($row) {
            if ((int) $row['deleted'] !== 0 || $row['created_by'] !== $userId || $row['lote_id'] !== $lotId
                || $row['recolector_id'] !== $contactId || $row['fecha'] !== $date || $row['estado_cafe'] !== $state
                || $row['cantidad_kg'] !== $amount || $row['tarifa_kg'] !== $price) {
                return $reply(409, ['code' => 'OPERACION_CONFLICTIVA']);
            }
            return $reply(200, ['operationId' => $operation, 'crmId' => $operation]);
        }

        $db->query('START TRANSACTION', true);
        $transaction = true;
        $bean = BeanFactory::newBean('ags_cosechas');
        $bean->id = $operation;
        $bean->new_with_id = true;
        $bean->name = "Recolección de café $date";
        $bean->lote_id = $lotId;
        $bean->recolector_id = $contactId;
        $bean->fecha = $date;
        $bean->cantidad_kg = $amount;
        $bean->tarifa_kg = $price;
        $bean->estado_cafe = $state;
        $bean->assigned_user_id = $userId;
        if ($bean->save() !== $operation || !$bean->load_relationship('SecurityGroups')) {
            throw new RuntimeException('No se pudo guardar la cosecha');
        }
        $bean->SecurityGroups->load();
        foreach ($bean->SecurityGroups->get() as $existing) {
            if (!isset($groupsForFarm[$existing])) $bean->SecurityGroups->delete($operation, $existing);
        }
        $bean->SecurityGroups->load();
        $missing = array_diff(array_keys($groupsForFarm), $bean->SecurityGroups->get());
        if ($missing) $bean->SecurityGroups->add(array_values($missing));
        $bean->SecurityGroups->load();
        $linked = $bean->SecurityGroups->get();
        if (array_diff(array_keys($groupsForFarm), $linked) || array_diff($linked, array_keys($groupsForFarm))) {
            throw new RuntimeException('No se confirmaron los grupos de finca');
        }
        $db->query('COMMIT', true);
        $transaction = false;
        return $reply(201, ['operationId' => $operation, 'crmId' => $operation]);
    } catch (Throwable $failure) {
        if ($transaction) $db->query('ROLLBACK', false);
        return $reply(500, ['code' => 'COSECHA_NO_CONFIRMADA']);
    } finally {
        $db->query("SELECT RELEASE_LOCK($lock)", false);
    }
});
