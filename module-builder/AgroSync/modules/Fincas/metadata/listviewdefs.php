<?php
$module_name = 'ags_Fincas';
$listViewDefs [$module_name] = 
array (
  'NAME' => 
  array (
    'width' => '32%',
    'label' => 'LBL_NAME',
    'default' => true,
    'link' => true,
  ),
  'CODIGO_FINCA' => 
  array (
    'type' => 'varchar',
    'label' => 'LBL_CODIGO_FINCA',
    'width' => '10%',
    'default' => true,
  ),
  'DEPARTAMENTO' => 
  array (
    'type' => 'relate',
    'studio' => 'visible',
    'label' => 'LBL_DEPARTAMENTO',
    'id' => 'AGS_DEPARTAMENTOS_ID_C',
    'link' => true,
    'width' => '10%',
    'default' => true,
  ),
  'MUNICIPIO' => 
  array (
    'type' => 'relate',
    'studio' => 'visible',
    'label' => 'LBL_MUNICIPIO',
    'id' => 'AGS_MUNICIPIOS_ID_C',
    'link' => true,
    'width' => '10%',
    'default' => true,
  ),
  'VEREDA' => 
  array (
    'type' => 'relate',
    'studio' => 'visible',
    'label' => 'LBL_VEREDA',
    'id' => 'AGS_VEREDAS_ID_C',
    'link' => true,
    'width' => '10%',
    'default' => true,
  ),
  'PROPIETARIO' => 
  array (
    'type' => 'relate',
    'studio' => 'visible',
    'label' => 'LBL_PROPIETARIO',
    'id' => 'CONTACT_ID_C',
    'link' => true,
    'width' => '10%',
    'default' => true,
  ),
  'DATE_ENTERED' => 
  array (
    'type' => 'datetime',
    'label' => 'LBL_DATE_ENTERED',
    'width' => '10%',
    'default' => true,
  ),
  'ASSIGNED_USER_NAME' => 
  array (
    'width' => '9%',
    'label' => 'LBL_ASSIGNED_TO_NAME',
    'module' => 'Employees',
    'id' => 'ASSIGNED_USER_ID',
    'default' => false,
  ),
);
