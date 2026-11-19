<?php
// created: 2026-11-19 11:44:59
$listViewDefs['ags_animales'] = array (
  'NAME' => 
  array (
    'width' => '25%',
    'label' => 'LBL_NAME',
    'link' => true,
    'default' => true,
  ),
  'CODIGO_ANIMAL' => 
  array (
    'width' => '15%',
    'label' => 'LBL_CODIGO_ANIMAL',
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
  'FECHA_NACIMIENTO' => 
  array (
    'width' => '15%',
    'label' => 'LBL_FECHA_NACIMIENTO',
    'default' => true,
  ),
  'MADRE' => 
  array (
    'width' => '15%',
    'label' => 'LBL_MADRE',
    'default' => false,
    'type' => 'relate',
    'id' => 'MADRE_ID',
    'link' => true,
  ),
  'SEXO' => 
  array (
    'width' => '15%',
    'label' => 'LBL_SEXO',
    'default' => false,
  ),
  'CATEGORIA' => 
  array (
    'width' => '15%',
    'label' => 'LBL_CATEGORIA',
    'default' => false,
  ),
  'RAZA' => 
  array (
    'width' => '15%',
    'label' => 'LBL_RAZA',
    'default' => false,
  ),
  'ESTADO' => 
  array (
    'width' => '15%',
    'label' => 'LBL_ESTADO',
    'default' => true,
  ),
);