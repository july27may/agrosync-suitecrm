<?php
// created: 2026-11-19 11:44:59
$listViewDefs['ags_labores'] = array (
  'NAME' => 
  array (
    'width' => '25%',
    'label' => 'LBL_NAME',
    'link' => true,
    'default' => true,
  ),
  'LOTE' => 
  array (
    'width' => '15%',
    'label' => 'LBL_LOTE',
    'default' => true,
    'type' => 'relate',
    'id' => 'LOTE_ID',
    'link' => true,
  ),
  'FECHA' => 
  array (
    'width' => '15%',
    'label' => 'LBL_FECHA',
    'default' => true,
  ),
  'TIPO_LABOR' => 
  array (
    'width' => '15%',
    'label' => 'LBL_TIPO_LABOR',
    'default' => true,
  ),
  'RESPONSABLE' => 
  array (
    'width' => '15%',
    'label' => 'LBL_RESPONSABLE',
    'default' => false,
    'type' => 'relate',
    'id' => 'RESPONSABLE_ID',
    'link' => true,
  ),
);