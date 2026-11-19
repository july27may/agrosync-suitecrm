<?php
// created: 2026-11-19 11:44:59
$listViewDefs['ags_insumos'] = array (
  'NAME' => 
  array (
    'width' => '25%',
    'label' => 'LBL_NAME',
    'link' => true,
    'default' => true,
  ),
  'CODIGO_INSUMO' => 
  array (
    'width' => '15%',
    'label' => 'LBL_CODIGO_INSUMO',
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
  'CATEGORIA' => 
  array (
    'width' => '15%',
    'label' => 'LBL_CATEGORIA',
    'default' => true,
  ),
  'UNIDAD' => 
  array (
    'width' => '15%',
    'label' => 'LBL_UNIDAD',
    'default' => false,
  ),
  'ESTADO' => 
  array (
    'width' => '15%',
    'label' => 'LBL_ESTADO',
    'default' => true,
  ),
);