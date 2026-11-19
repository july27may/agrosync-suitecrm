<?php

function agrosyncDelegatedUser($request): ?User
{
    $clientId = $request->getAttribute('oauth_client_id');
    $userId = $request->getAttribute('oauth_user_id');
    if (!is_string($clientId) || !is_string($userId) || $userId === '') {
        return null;
    }
    $client = BeanFactory::getBean('OAuth2Clients', $clientId);
    $user = BeanFactory::getBean('Users', $userId);
    if (empty($client->id) || $client->allowed_grant_type !== 'authorization_code'
        || empty($user->id) || $user->status !== 'Active' || !$user->isEnabled()) {
        return null;
    }

    return $user;
}

function agrosyncPermissionDocument(User $user): array
{
    $userId = $user->id;
    require_once 'modules/SecurityGroups/SecurityGroup.php';
    $memberships = SecurityGroup::getUserSecurityGroups($userId);
    $roles = [
        'AgroSync Administrador de finca' => 'ADMINISTRADOR_FINCA',
        'AgroSync Registrador' => 'REGISTRADOR',
        'AgroSync Consulta' => 'CONSULTA',
    ];

    $matched = [];
    if ($memberships) {
        $db = DBManagerFactory::getInstance();
        $ids = implode(',', array_map(fn ($id) => $db->quoted($id), array_keys($memberships)));
        $sql = "SELECT DISTINCT f.id, ar.name
                FROM securitygroups_records sr
                JOIN ags_fincas f ON f.id = sr.record_id AND f.deleted = 0
                JOIN securitygroups_acl_roles gr ON gr.securitygroup_id = sr.securitygroup_id AND gr.deleted = 0
                JOIN acl_roles ar ON ar.id = gr.role_id AND ar.deleted = 0
                WHERE sr.deleted = 0 AND sr.module = 'ags_Fincas' AND sr.securitygroup_id IN ($ids)";
        $rows = $db->query($sql, true);
        while ($row = $db->fetchByAssoc($rows)) {
            if (isset($roles[$row['name']])) {
                $matched[$row['id']][$roles[$row['name']]] = true;
            }
        }
    }

    $GLOBALS['current_user'] = $user;
    $fincas = [];
    $conflicts = [];
    foreach ($matched as $id => $profiles) {
        $farm = BeanFactory::getBean('ags_Fincas', $id);
        if (empty($farm->id)) continue;
        $names = array_keys($profiles);
        sort($names, SORT_STRING);
        if (count($names) > 1) {
            $conflicts[] = ['id' => $id, 'nombre' => $farm->name, 'roles' => $names];
        } elseif ($farm->ACLAccess('view')) {
            $fincas[] = ['id' => $id, 'role' => $names[0]];
        }
    }
    usort($fincas, fn ($a, $b) => strcmp($a['id'], $b['id']));
    usort($conflicts, fn ($a, $b) => strcmp($a['id'], $b['id']));
    return [
        'id' => $user->id,
        'username' => $user->user_name,
        'fincas' => $fincas,
        'conflictos' => $conflicts,
    ];
}

$app->get('/agrosync/access', function ($request, $response) {
    $user = agrosyncDelegatedUser($request);
    return $user
        ? $response->withHeader('Cache-Control', 'no-store')->withJson(agrosyncPermissionDocument($user))
        : $response->withStatus(403);
});
