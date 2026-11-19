<?php
// created: 2026-11-19 11:44:59
$listViewDefs['ags_movimientos_inventario'] = array (
  'NAME' => 
  array (
    'width' => '25%',
    'label' => 'LBL_NAME',
    'link' => true,
    'default' => true,
  ),
  'INSUMO' => 
  array (
    'width' => '15%',
    'label' => 'LBL_INSUMO',
    'default' => true,
    'type' => 'relate',
    'id' => 'INSUMO_ID',
    'link' => true,
  ),
  'FECHA_HORA' => 
  array (
    'width' => '15%',
    'label' => 'LBL_FECHA_HORA',
    'default' => true,
  ),
  'TIPO_MOVIMIENTO' => 
  array (
    'width' => '15%',
    'label' => 'LBL_TIPO_MOVIMIENTO',
    'default' => true,
  ),
  'CANTIDAD' => 
  array (
    'width' => '15%',
    'label' => 'LBL_CANTIDAD',
    'default' => false,
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
  'REFERENCIA_DOCUMENTO' => 
  array (
    'width' => '15%',
    'label' => 'LBL_REFERENCIA_DOCUMENTO',
    'default' => false,
  ),
  'MOVIMIENTO_ORIGEN' => 
  array (
    'width' => '15%',
    'label' => 'LBL_MOVIMIENTO_ORIGEN',
    'default' => false,
    'type' => 'relate',
    'id' => 'MOVIMIENTO_ORIGEN_ID',
    'link' => true,
  ),
);