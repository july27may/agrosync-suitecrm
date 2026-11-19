<?php
// created: 2026-11-19 11:44:59
$viewdefs['ags_movimientos_inventario']['QuickCreate'] = array (
  'templateMeta' => 
  array (
    'maxColumns' => '2',
    'widths' => 
    array (
      0 => 
      array (
        'label' => '10',
        'field' => '30',
      ),
      1 => 
      array (
        'label' => '10',
        'field' => '30',
      ),
    ),
  ),
  'panels' => 
  array (
    'default' => 
    array (
      0 => 
      array (
        0 => 'name',
        1 => 'insumo',
      ),
      1 => 
      array (
        0 => 'fecha_hora',
        1 => 'tipo_movimiento',
      ),
      2 => 
      array (
        0 => 'cantidad',
        1 => 'responsable',
      ),
      3 => 
      array (
        0 => 'referencia_documento',
        1 => 'movimiento_origen',
      ),
      4 => 
      array (
        0 => 'assigned_user_name',
      ),
      5 => 
      array (
        0 => 'description',
      ),
    ),
  ),
);