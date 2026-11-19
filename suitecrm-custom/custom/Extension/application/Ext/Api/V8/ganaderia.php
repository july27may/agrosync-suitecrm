<?php

function agrosyncLivestockReply($response, int $status, array $data)
{
    return $response->withHeader('Cache-Control', 'no-store')->withJson($data, $status);
}

function agrosyncLivestockRole(User $user, string $farm): ?string
{
    foreach (agrosyncPermissionDocument($user)['fincas'] as $permission) {
        if ($permission['id'] === $farm) return $permission['role'];
    }
    return null;
}

function agrosyncLivestockId($value): bool
{
    return is_string($value) && preg_match('/^[A-Za-z0-9_-]{1,36}$/D', $value) === 1;
}

function agrosyncLivestockDate($value): bool
{
    return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)
        && checkdate((int) substr($value, 5, 2), (int) substr($value, 8), (int) substr($value, 0, 4));
}

function agrosyncLivestockText($value, int $length, bool $required = false): bool
{
    return (!$required && $value === null)
        || (is_string($value) && (!$required || trim($value) !== '') && mb_strlen($value) <= $length);
}

$app->get('/agrosync/ganaderia', function ($request, $response) {
    $user = agrosyncDelegatedUser($request);
    $farm = $request->getQueryParam('fincaId');
    if (!$user || !agrosyncLivestockId($farm) || !agrosyncLivestockRole($user, $farm)) {
        return agrosyncLivestockReply($response, 403, ['code' => 'ACCESO_DENEGADO']);
    }
    $db = DBManagerFactory::getInstance();
    $key = $db->quoted($farm);
    $animals = [];
    $rows = $db->query("SELECT id, finca_id AS fincaId, codigo_animal AS codigo, name AS nombre, sexo,
        categoria, fecha_nacimiento AS nacimiento, madre_id AS madreId, raza, estado
        FROM ags_animales WHERE finca_id=$key AND deleted=0 ORDER BY id", true);
    while ($row = $db->fetchByAssoc($rows)) {
        $bean = BeanFactory::getBean('ags_animales', $row['id']);
        if (!empty($bean->id) && $bean->ACLAccess('view')) $animals[$row['id']] = $row;
    }
    $events = [];
    $rows = $db->query("SELECT e.id, a.finca_id AS fincaId, e.animal_id AS animalId, e.name AS nombre,
        e.fecha, e.tipo_evento AS tipo, e.producto, e.responsable_id AS responsableId,
        e.referencia_certificado AS certificado, e.description AS observaciones
        FROM ags_eventos_sanitarios e JOIN ags_animales a ON a.id=e.animal_id AND a.deleted=0
        WHERE a.finca_id=$key AND e.deleted=0 ORDER BY e.id", true);
    while ($row = $db->fetchByAssoc($rows)) {
        if (!isset($animals[$row['animalId']])) continue;
        $bean = BeanFactory::getBean('ags_eventos_sanitarios', $row['id']);
        if (!empty($bean->id) && $bean->ACLAccess('view')) $events[] = $row;
    }
    return agrosyncLivestockReply($response, 200, ['animales' => array_values($animals), 'eventos' => $events]);
});

foreach (['animales' => 'ags_animales', 'eventos' => 'ags_eventos_sanitarios'] as $kind => $module) {
    $app->post('/agrosync/ganaderia/' . $kind, function ($request, $response) use ($kind, $module) {
        $reply = fn ($status, $data) => agrosyncLivestockReply($response, $status, $data);
        $user = agrosyncDelegatedUser($request);
        if (!$user) return $reply(403, ['code' => 'ACCESO_DENEGADO']);
        $input = $request->getParsedBody();
        if (!is_array($input)) return $reply(400, ['code' => 'GANADERIA_INVALIDA']);
        $id = $input['operationId'] ?? null;
        $farm = $input['fincaId'] ?? null;
        $name = $input['nombre'] ?? null;
        if (!is_string($id) || !preg_match('/^[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}$/D', $id)
            || !agrosyncLivestockId($farm) || !agrosyncLivestockText($name, 255, true)) {
            return $reply(400, ['code' => 'GANADERIA_INVALIDA']);
        }
        $animal = $kind === 'animales';
        $role = agrosyncLivestockRole($user, $farm);
        if (!$role || $role === 'CONSULTA' || ($animal && $role !== 'ADMINISTRADOR_FINCA')) {
            return $reply(403, ['code' => 'ACCESO_DENEGADO']);
        }

        $fields = ['name' => $name];
        $parent = null;
        if ($animal) {
            $code = $input['codigo'] ?? null;
            $sex = $input['sexo'] ?? null;
            $category = $input['categoria'] ?? null;
            $birth = $input['nacimiento'] ?? null;
            $mother = $input['madreId'] ?? null;
            $breed = $input['raza'] ?? null;
            $state = $input['estado'] ?? null;
            if (!agrosyncLivestockText($code, 30, true) || !in_array($sex, ['hembra','macho'], true)
                || !in_array($category, [null,'ternero','ternera','novillo','novilla','vaca','toro'], true)
                || ($birth !== null && (!agrosyncLivestockDate($birth) || $birth > date('Y-m-d')))
                || ($mother !== null && (!agrosyncLivestockId($mother) || $mother === $id))
                || !agrosyncLivestockText($breed, 100)
                || !in_array($state, ['activo','vendido','fallecido','inactivo'], true)) {
                return $reply(400, ['code' => 'GANADERIA_INVALIDA']);
            }
            $fields += ['finca_id' => $farm, 'codigo_animal' => $code, 'sexo' => $sex, 'categoria' => $category,
                'fecha_nacimiento' => $birth, 'madre_id' => $mother, 'raza' => $breed, 'estado' => $state];
            $parent = $mother;
        } else {
            $parent = $input['animalId'] ?? null;
            $date = $input['fecha'] ?? null;
            $type = $input['tipo'] ?? null;
            $product = $input['producto'] ?? null;
            $responsible = $input['responsableId'] ?? null;
            $certificate = $input['certificado'] ?? null;
            $notes = $input['observaciones'] ?? null;
            if (!agrosyncLivestockId($parent) || !agrosyncLivestockDate($date)
                || !in_array($type, ['vacunacion','tratamiento','revision','otro'], true)
                || !agrosyncLivestockText($product, 150) || !agrosyncLivestockText($certificate, 100)
                || !agrosyncLivestockText($notes, 4000) || ($responsible !== null && !agrosyncLivestockId($responsible))) {
                return $reply(400, ['code' => 'GANADERIA_INVALIDA']);
            }
            if ($responsible !== null) {
                $contact = BeanFactory::getBean('Contacts', $responsible);
                if (empty($contact->id) || !$contact->ACLAccess('view')) return $reply(403, ['code' => 'ACCESO_DENEGADO']);
            }
            $fields += ['animal_id' => $parent, 'fecha' => $date, 'tipo_evento' => $type, 'producto' => $product,
                'responsable_id' => $responsible, 'referencia_certificado' => $certificate, 'description' => $notes];
        }

        $db = DBManagerFactory::getInstance();
        $lock = $db->quoted('agrosync-ganado-' . $farm);
        $locked = $db->fetchByAssoc($db->query("SELECT GET_LOCK($lock, 3) AS obtained", true));
        if ((int) ($locked['obtained'] ?? 0) !== 1) return $reply(503, ['code' => 'OPERACION_OCUPADA']);

        $transaction = false;
        try {
            if ($parent !== null) {
                $reference = BeanFactory::getBean('ags_animales', $parent);
                if (empty($reference->id) || $reference->finca_id !== $farm || !$reference->ACLAccess('view')
                    || ($animal && $reference->sexo !== 'hembra')) {
                    return $reply(409, ['code' => 'REFERENCIA_NO_CONFIRMADA']);
                }
            }

            $key = $db->quoted($id);
            $original = $db->fetchByAssoc($db->query("SELECT * FROM $module WHERE id=$key", true));
            if ($original) {
                $same = (int) $original['deleted'] === 0 && $original['created_by'] === $user->id;
                foreach ($fields as $field => $value) {
                    if ((string) $original[$field] !== (string) $value) $same = false;
                }
                return $same ? $reply(200, ['crmId' => $id, 'operationId' => $id])
                    : $reply(409, ['code' => 'OPERACION_CONFLICTIVA']);
            }

            $farmKey = $db->quoted($farm);
            if ($animal) {
                $codeKey = $db->quoted($fields['codigo_animal']);
                if ($db->fetchByAssoc($db->query("SELECT id FROM ags_animales
                    WHERE finca_id=$farmKey AND codigo_animal=$codeKey AND deleted=0 LIMIT 1", true))) {
                    return $reply(409, ['code' => 'CODIGO_ANIMAL_DUPLICADO']);
                }
            }

            $groups = [];
            $rows = $db->query("SELECT DISTINCT sr.securitygroup_id FROM securitygroups_records sr
                JOIN securitygroups sg ON sg.id=sr.securitygroup_id AND sg.deleted=0
                JOIN securitygroups_acl_roles gr ON gr.securitygroup_id=sg.id AND gr.deleted=0
                JOIN acl_roles ar ON ar.id=gr.role_id AND ar.deleted=0
                WHERE sr.deleted=0 AND sr.module='ags_Fincas' AND sr.record_id=$farmKey
                AND ar.name IN ('AgroSync Administrador de finca','AgroSync Registrador','AgroSync Consulta')", true);
            while ($row = $db->fetchByAssoc($rows)) $groups[] = $row['securitygroup_id'];
            if (!$groups) return $reply(403, ['code' => 'ACCESO_DENEGADO']);

            $db->query('START TRANSACTION', true);
            $transaction = true;
            $bean = BeanFactory::newBean($module);
            $bean->id = $id;
            $bean->new_with_id = true;
            $bean->assigned_user_id = $user->id;
            foreach ($fields as $field => $value) $bean->$field = $value;
            if ($bean->save() !== $id || !$bean->load_relationship('SecurityGroups')
                || !$db->fetchByAssoc($db->query("SELECT id FROM $module WHERE id=$key AND deleted=0", true))) {
                throw new RuntimeException('No se guardó el registro ganadero');
            }
            $bean->SecurityGroups->load();
            foreach (array_diff($bean->SecurityGroups->get(), $groups) as $unrelated) {
                $bean->SecurityGroups->delete($id, $unrelated);
            }
            $bean->SecurityGroups->load();
            $missing = array_values(array_diff($groups, $bean->SecurityGroups->get()));
            if ($missing) $bean->SecurityGroups->add($missing);
            $bean->SecurityGroups->load();
            $linked = $bean->SecurityGroups->get();
            if (array_diff($linked, $groups) || array_diff($groups, $linked)) {
                throw new RuntimeException('No se confirmaron los grupos de finca');
            }
            $db->query('COMMIT', true);
            $transaction = false;
            return $reply(201, ['crmId' => $id, 'operationId' => $id]);
        } catch (Throwable $failure) {
            if ($transaction) $db->query('ROLLBACK', false);
            return $reply(500, ['code' => 'GANADERIA_NO_CONFIRMADA']);
        } finally {
            $db->query("SELECT RELEASE_LOCK($lock)", false);
        }
    });
}
