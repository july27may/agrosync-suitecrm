<?php
/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2018 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for technical reasons, the Appropriate Legal Notices must
 * display the words "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 */
$vardefs = array (
  'fields' => 
  array (
    'name' => 
    array (
      'name' => 'name',
      'vname' => 'LBL_NAME',
      'type' => 'name',
      'link' => true,
      'dbType' => 'varchar',
      'len' => 255,
      'unified_search' => true,
      'full_text_search' => 
      array (
        'boost' => 3,
      ),
      'required' => true,
      'importable' => 'required',
      'duplicate_merge' => 'enabled',
      'merge_filter' => 'selected',
      'audited' => true,
    ),
    'description' => 
    array (
      'name' => 'description',
      'vname' => 'LBL_DESCRIPTION',
      'type' => 'text',
      'comment' => 'Full text of the note',
      'rows' => 6,
      'cols' => 80,
      'audited' => true,
    ),
    'codigo_animal' => 
    array (
      'name' => 'codigo_animal',
      'vname' => 'LBL_CODIGO_ANIMAL',
      'type' => 'varchar',
      'required' => true,
      'audited' => true,
      'reportable' => true,
      'importable' => 'true',
      'len' => 30,
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
    ),
    'finca_id' => 
    array (
      'name' => 'finca_id',
      'vname' => 'LBL_FINCA_ID',
      'type' => 'id',
      'len' => 36,
      'required' => false,
      'audited' => true,
      'reportable' => false,
      'importable' => 'true',
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
    ),
    'finca' => 
    array (
      'name' => 'finca',
      'vname' => 'LBL_FINCA',
      'type' => 'relate',
      'required' => true,
      'audited' => false,
      'reportable' => true,
      'importable' => 'true',
      'source' => 'non-db',
      'len' => 255,
      'id_name' => 'finca_id',
      'ext2' => 'ags_Fincas',
      'module' => 'ags_Fincas',
      'rname' => 'name',
      'quicksearch' => 'enabled',
      'studio' => 'visible',
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
      'resetFieldInStudio' => 'true',
    ),
    'fecha_nacimiento' => 
    array (
      'name' => 'fecha_nacimiento',
      'vname' => 'LBL_FECHA_NACIMIENTO',
      'type' => 'date',
      'required' => false,
      'audited' => true,
      'reportable' => true,
      'importable' => 'true',
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
    ),
    'madre_id' => 
    array (
      'name' => 'madre_id',
      'vname' => 'LBL_MADRE_ID',
      'type' => 'id',
      'len' => 36,
      'required' => false,
      'audited' => true,
      'reportable' => false,
      'importable' => 'true',
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
    ),
    'madre' => 
    array (
      'name' => 'madre',
      'vname' => 'LBL_MADRE',
      'type' => 'relate',
      'required' => false,
      'audited' => false,
      'reportable' => true,
      'importable' => 'true',
      'source' => 'non-db',
      'len' => 255,
      'id_name' => 'madre_id',
      'ext2' => 'ags_animales',
      'module' => 'ags_animales',
      'rname' => 'name',
      'quicksearch' => 'enabled',
      'studio' => 'visible',
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
      'resetFieldInStudio' => 'true',
    ),
    'sexo' => 
    array (
      'name' => 'sexo',
      'vname' => 'LBL_SEXO',
      'type' => 'enum',
      'required' => true,
      'audited' => true,
      'reportable' => true,
      'importable' => 'true',
      'len' => 40,
      'options' => 'ags_operaciones_sexo_list',
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
    ),
    'categoria' => 
    array (
      'name' => 'categoria',
      'vname' => 'LBL_CATEGORIA',
      'type' => 'enum',
      'required' => false,
      'audited' => true,
      'reportable' => true,
      'importable' => 'true',
      'len' => 40,
      'options' => 'ags_operaciones_categoria_animal_list',
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
    ),
    'raza' => 
    array (
      'name' => 'raza',
      'vname' => 'LBL_RAZA',
      'type' => 'varchar',
      'required' => false,
      'audited' => true,
      'reportable' => true,
      'importable' => 'true',
      'len' => 100,
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
    ),
    'estado' => 
    array (
      'name' => 'estado',
      'vname' => 'LBL_ESTADO',
      'type' => 'enum',
      'required' => true,
      'audited' => true,
      'reportable' => true,
      'importable' => 'true',
      'len' => 40,
      'options' => 'ags_operaciones_estado_animal_list',
      'default' => 'activo',
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
    ),
  ),
  'relationships' => 
  array (
  ),
);