<?php
// created: 2026-11-19 11:44:59
$viewdefs['ags_lotes']['DetailView'] = array (
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
        1 => 'codigo_lote',
      ),
      1 => 
      array (
        0 => 'finca',
        1 => 'area_ha',
      ),
      2 => 
      array (
        0 => 'cultivo',
        1 => 'variedad',
      ),
      3 => 
      array (
        0 => 'estado',
        1 => 'assigned_user_name',
      ),
      4 => 
      array (
        0 => 'description',
      ),
    ),
  ),
);