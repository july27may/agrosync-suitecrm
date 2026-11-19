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
    'insumo_id' => 
    array (
      'name' => 'insumo_id',
      'vname' => 'LBL_INSUMO_ID',
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
    'insumo' => 
    array (
      'name' => 'insumo',
      'vname' => 'LBL_INSUMO',
      'type' => 'relate',
      'required' => true,
      'audited' => false,
      'reportable' => true,
      'importable' => 'true',
      'source' => 'non-db',
      'len' => 255,
      'id_name' => 'insumo_id',
      'ext2' => 'ags_insumos',
      'module' => 'ags_insumos',
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
    'fecha_hora' => 
    array (
      'name' => 'fecha_hora',
      'vname' => 'LBL_FECHA_HORA',
      'type' => 'datetimecombo',
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
    'tipo_movimiento' => 
    array (
      'name' => 'tipo_movimiento',
      'vname' => 'LBL_TIPO_MOVIMIENTO',
      'type' => 'enum',
      'required' => true,
      'audited' => true,
      'reportable' => true,
      'importable' => 'true',
      'len' => 40,
      'options' => 'ags_operaciones_tipo_movimiento_list',
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
    ),
    'cantidad' => 
    array (
      'name' => 'cantidad',
      'vname' => 'LBL_CANTIDAD',
      'type' => 'decimal',
      'required' => true,
      'audited' => true,
      'reportable' => true,
      'importable' => 'true',
      'help' => 'Registra la cantidad en la unidad de control del insumo seleccionado.',
      'len' => '14',
      'precision' => '3',
      'massupdate' => 0,
      'inline_edit' => true,
      'metadata' => 
      array (
      ),
      'enable_range_search' => false,
    ),
    'responsable_id' => 
    array (
      'name' => 'responsable_id',
      'vname' => 'LBL_RESPONSABLE_ID',
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
    'responsable' => 
    array (
      'name' => 'responsable',
      'vname' => 'LBL_RESPONSABLE',
      'type' => 'relate',
      'required' => false,
      'audited' => false,
      'reportable' => true,
      'importable' => 'true',
      'source' => 'non-db',
      'len' => 255,
      'id_name' => 'responsable_id',
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
    'referencia_documento' => 
    array (
      'name' => 'referencia_documento',
      'vname' => 'LBL_REFERENCIA_DOCUMENTO',
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
    'movimiento_origen_id' => 
    array (
      'name' => 'movimiento_origen_id',
      'vname' => 'LBL_MOVIMIENTO_ORIGEN_ID',
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
    'movimiento_origen' => 
    array (
      'name' => 'movimiento_origen',
      'vname' => 'LBL_MOVIMIENTO_ORIGEN',
      'type' => 'relate',
      'required' => false,
      'audited' => false,
      'reportable' => true,
      'importable' => 'true',
      'source' => 'non-db',
      'len' => 255,
      'id_name' => 'movimiento_origen_id',
      'ext2' => 'ags_movimientos_inventario',
      'module' => 'ags_movimientos_inventario',
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
  ),
  'relationships' => 
  array (
  ),
);