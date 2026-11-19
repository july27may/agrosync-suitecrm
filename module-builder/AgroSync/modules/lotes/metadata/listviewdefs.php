<?php
// created: 2026-11-19 11:44:59
$listViewDefs['ags_lotes'] = array (
  'NAME' => 
  array (
    'width' => '25%',
    'label' => 'LBL_NAME',
    'link' => true,
    'default' => true,
  ),
  'CODIGO_LOTE' => 
  array (
    'width' => '15%',
    'label' => 'LBL_CODIGO_LOTE',
    'default' => true,
  ),
  'FINCA' => 
  array (
    'width' => '15%',
    'label' => 'LBL_FINCA',
    'default' => true,
    'type' => 'relate',
    'id' => 'FINCA_ID',
    'link' => true,
  ),
  'AREA_HA' => 
  array (
    'width' => '15%',
    'label' => 'LBL_AREA_HA',
    'default' => true,
  ),
  'CULTIVO' => 
  array (
    'width' => '15%',
    'label' => 'LBL_CULTIVO',
    'default' => false,
  ),
  'VARIEDAD' => 
  array (
    'width' => '15%',
    'label' => 'LBL_VARIEDAD',
    'default' => false,
  ),
  'ESTADO' => 
  array (
    'width' => '15%',
    'label' => 'LBL_ESTADO',
    'default' => true,
  ),
);