<?php
// created: 2026-11-19 11:44:59
$listViewDefs['ags_eventos_sanitarios'] = array (
  'NAME' => 
  array (
    'width' => '25%',
    'label' => 'LBL_NAME',
    'link' => true,
    'default' => true,
  ),
  'ANIMAL' => 
  array (
    'width' => '15%',
    'label' => 'LBL_ANIMAL',
    'default' => true,
    'type' => 'relate',
    'id' => 'ANIMAL_ID',
    'link' => true,
  ),
  'FECHA' => 
  array (
    'width' => '15%',
    'label' => 'LBL_FECHA',
    'default' => true,
  ),
  'TIPO_EVENTO' => 
  array (
    'width' => '15%',
    'label' => 'LBL_TIPO_EVENTO',
    'default' => true,
  ),
  'PRODUCTO' => 
  array (
    'width' => '15%',
    'label' => 'LBL_PRODUCTO',
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
  'REFERENCIA_CERTIFICADO' => 
  array (
    'width' => '15%',
    'label' => 'LBL_REFERENCIA_CERTIFICADO',
    'default' => false,
  ),
);