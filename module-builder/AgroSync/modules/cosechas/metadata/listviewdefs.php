<?php
// created: 2026-11-19 11:44:59
$listViewDefs['ags_cosechas'] = array (
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
  'RECOLECTOR' => 
  array (
    'width' => '15%',
    'label' => 'LBL_RECOLECTOR',
    'default' => true,
    'type' => 'relate',
    'id' => 'RECOLECTOR_ID',
    'link' => true,
  ),
  'FECHA' => 
  array (
    'width' => '15%',
    'label' => 'LBL_FECHA',
    'default' => true,
  ),
  'CANTIDAD_KG' => 
  array (
    'width' => '15%',
    'label' => 'LBL_CANTIDAD_KG',
    'default' => false,
  ),
  'TARIFA_KG' => 
  array (
    'width' => '15%',
    'label' => 'LBL_TARIFA_KG',
    'default' => false,
  ),
  'ESTADO_CAFE' => 
  array (
    'width' => '15%',
    'label' => 'LBL_ESTADO_CAFE',
    'default' => true,
  ),
);