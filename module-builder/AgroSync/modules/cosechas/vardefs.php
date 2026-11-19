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
    'lote_id' => 
    array (
      'name' => 'lote_id',
      'vname' => 'LBL_LOTE_ID',
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
    'lote' => 
    array (
      'name' => 'lote',
      'vname' => 'LBL_LOTE',
      'type' => 'relate',
      'required' => true,
      'audited' => false,
      'reportable' => true,
      'importable' => 'true',
      'source' => 'non-db',
      'len' => 255,
      'id_name' => 'lote_id',
      'ext2' => 'ags_lotes',
      'module' => 'ags_lotes',
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
    'recolector_id' => 
    array (
      'name' => 'recolector_id',
      'vname' => 'LBL_RECOLECTOR_ID',
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
    'recolector' => 
    array (
      'name' => 'recolector',
      'vname' => 'LBL_RECOLECTOR',
      'type' => 'relate',
      'required' => true,
      'audited' => false,
      'reportable' => true,
      'importable' => 'true',
      'source' => 'non-db',
      'len' => 255,
      'id_name' => 'recolector_id',
      'ext2' => 'Contacts',
      'module' => 'Contacts',
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
    'fecha' => 
    array (
      'name' => 'fecha',
      'vname' => 'LBL_FECHA',
      'type' => 'date',
      'required' => true,
      'audited' => true,
      'reportable' => true,
      'importable' => 'true',
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
    ),
    'cantidad_kg' => 
    array (
      'name' => 'cantidad_kg',
      'vname' => 'LBL_CANTIDAD_KG',
      'type' => 'decimal',
      'required' => true,
      'audited' => true,
      'reportable' => true,
      'importable' => 'true',
      'help' => 'Registra el peso recolectado en kilogramos.',
      'len' => '14',
      'precision' => '3',
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
      'enable_range_search' => false,
    ),
    'tarifa_kg' => 
    array (
      'name' => 'tarifa_kg',
      'vname' => 'LBL_TARIFA_KG',
      'type' => 'decimal',
      'required' => true,
      'audited' => true,
      'reportable' => true,
      'importable' => 'true',
      'help' => 'Registra la tarifa aplicada por kilogramo, en pesos colombianos.',
      'len' => '14',
      'precision' => '2',
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
      'enable_range_search' => false,
    ),
    'estado_cafe' => 
    array (
      'name' => 'estado_cafe',
      'vname' => 'LBL_ESTADO_CAFE',
      'type' => 'enum',
      'required' => true,
      'audited' => true,
      'reportable' => true,
      'importable' => 'true',
      'len' => 40,
      'options' => 'ags_operaciones_estado_cafe_list',
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