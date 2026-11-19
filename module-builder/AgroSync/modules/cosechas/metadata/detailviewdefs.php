<?php
// created: 2026-11-19 11:44:59
$viewdefs['ags_cosechas']['DetailView'] = array (
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
        1 => 'lote',
      ),
      1 => 
      array (
        0 => 'recolector',
        1 => 'fecha',
      ),
      2 => 
      array (
        0 => 'cantidad_kg',
        1 => 'tarifa_kg',
      ),
      3 => 
      array (
        0 => 'estado_cafe',
        1 => 'assigned_user_name',
      ),
      4 => 
      array (
        0 => 'description',
      ),
    ),
  ),
);