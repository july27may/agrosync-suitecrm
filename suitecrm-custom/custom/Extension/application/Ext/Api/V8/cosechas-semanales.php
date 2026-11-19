<?php

$app->get('/agrosync/cosechas', function ($request, $response) {
    $user = agrosyncDelegatedUser($request);
    if (!$user) return $response->withStatus(403);
    $farm = $request->getQueryParam('fincaId');
    $start = $request->getQueryParam('semana');
    if (!is_string($farm) || !preg_match('/^[A-Za-z0-9_-]{1,36}$/D', $farm)
        || !is_string($start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $start)) {
        return $response->withStatus(400);
    }
    $monday = DateTimeImmutable::createFromFormat('!Y-m-d', $start, new DateTimeZone('UTC'));
    if (!$monday || $monday->format('Y-m-d') !== $start || $monday->format('N') !== '1') {
        return $response->withStatus(400);
    }

    $authorized = array_column(agrosyncPermissionDocument($user)['fincas'], 'id');
    if (!in_array($farm, $authorized, true)) return $response->withStatus(403);

    $db = DBManagerFactory::getInstance();
    $farmKey = $db->quoted($farm);
    $from = $db->quoted($start);
    $to = $db->quoted($monday->modify('+6 days')->format('Y-m-d'));
    $rows = $db->query("SELECT c.id, c.fecha, c.estado_cafe, c.lote_id, c.recolector_id,
        l.name AS lote, f.name AS finca, c.cantidad_kg, c.tarifa_kg,
        TRIM(CONCAT_WS(' ', contact.first_name, contact.last_name)) AS recolector
        FROM ags_cosechas c
        JOIN ags_lotes l ON l.id = c.lote_id
        JOIN ags_fincas f ON f.id = l.finca_id AND f.deleted = 0
        LEFT JOIN contacts contact ON contact.id = c.recolector_id AND contact.deleted = 0
        WHERE c.deleted = 0 AND l.finca_id = $farmKey AND c.fecha BETWEEN $from AND $to
        ORDER BY c.fecha DESC, c.id", true);
    $items = [];
    while ($row = $db->fetchByAssoc($rows)) {
        $weight = $row['cantidad_kg'];
        $rate = $row['tarifa_kg'];
        if ($weight === null || $rate === null || !preg_match('/^\d+\.\d{3}$/D', (string) $weight)
            || !preg_match('/^\d+\.\d{2}$/D', (string) $rate)
            || !in_array($row['estado_cafe'], ['cereza', 'pergamino'], true)) {
            return $response->withStatus(500);
        }
        $items[] = [
            'id' => $row['id'], 'crmId' => $row['id'], 'fecha' => $row['fecha'], 'estadoCafe' => $row['estado_cafe'],
            'pesoGramos' => (int) str_replace('.', '', (string) $weight),
            'tarifaCentavos' => (int) str_replace('.', '', (string) $rate),
            'finca' => ['id' => $farm, 'nombre' => $row['finca']],
            'lote' => ['id' => $row['lote_id'], 'nombre' => $row['lote']],
            'recolector' => ['id' => $row['recolector_id'] ?: 'sin-recolector',
                'nombre' => $row['recolector'] ?: 'Recolector no disponible'],
        ];
    }
    return $response->withHeader('Cache-Control', 'no-store')->withJson(['items' => $items]);
});
