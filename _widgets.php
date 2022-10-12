<?php
/*
 *  -- BEGIN LICENSE BLOCK ----------------------------------
 *
 *  This file is part of Related, a plugin for DotClear2.
 *
 *  Licensed under the GPL version 2.0 license.
 *  See LICENSE file or
 *  http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 *  -- END LICENSE BLOCK ------------------------------------
 */

dcCore::app()->addBehavior('initWidgets', ['widgetsRelated', 'init']);
dcCore::app()->addBehavior('initDefaultWidgets', ['widgetsRelated', 'initDefaultWidgets']);
