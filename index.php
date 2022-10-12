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

if (!empty($_REQUEST['do']) && $_REQUEST['do'] === 'edit') {
    include_once __DIR__ . '/inc/page.php';
} else {
    include_once __DIR__ . '/inc/panel.php';
}
