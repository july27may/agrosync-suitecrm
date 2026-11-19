<?php
$module_name = 'ags_Fincas';
$viewdefs [$module_name] = 
array (
  'DetailView' => 
  array (
    'templateMeta' => 
    array (
      'form' => 
      array (
        'buttons' => 
        array (
          0 => 'EDIT',
          1 => 'DUPLICATE',
          2 => 'DELETE',
          3 => 'FIND_DUPLICATES',
        ),
      ),
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
      'useTabs' => false,
      'tabDefs' => 
      array (
        'DEFAULT' => 
        array (
          'newTab' => false,
          'panelDefault' => 'expanded',
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
          1 => 
          array (
            'name' => 'codigo_finca',
            'label' => 'LBL_CODIGO_FINCA',
          ),
        ),
        1 => 
        array (
          0 => 
          array (
            'name' => 'departamento',
            'studio' => 'visible',
            'label' => 'LBL_DEPARTAMENTO',
          ),
          1 => 
          array (
            'name' => 'municipio',
            'studio' => 'visible',
            'label' => 'LBL_MUNICIPIO',
          ),
        ),
        2 => 
        array (
          0 => 
          array (
            'name' => 'vereda',
            'studio' => 'visible',
            'label' => 'LBL_VEREDA',
          ),
          1 => 
          array (
            'name' => 'propietario',
            'studio' => 'visible',
            'label' => 'LBL_PROPIETARIO',
          ),
        ),
        3 => 
        array (
          0 => 
          array (
            'name' => 'area_ha',
            'label' => 'LBL_AREA_HA',
          ),
          1 => 
          array (
            'name' => 'estado',
            'studio' => 'visible',
            'label' => 'LBL_ESTADO',
          ),
        ),
        4 => 
        array (
          0 => 
          array (
            'name' => 'description',
            'comment' => 'Full text of the note',
            'studio' => 'visible',
            'label' => 'LBL_DESCRIPTION',
          ),
        ),
      ),
    ),
  ),
);
