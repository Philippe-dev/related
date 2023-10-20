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

$this->registerModule(
    "Related pages", // Name
    "Serve pages & scripts", // Description
    "Pep, contributors, Nicolas Roudaire", // Author
    '1.9.0', // Version
    [
        'permissions' => dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_CONTENT_ADMIN, initPages::PERMISSION_PAGES]),
        'type' => 'plugin',
        'dc_min' => '2.27',
        'requires' => [['core', '2.27']],
        'repository' => 'https://github.com/nikrou/related',
        'support' => 'http://forum.dotclear.net/viewtopic.php?id=48205',
        'details' => 'http://plugins.dotaddict.org/dc2/details/related'
    ]
);
