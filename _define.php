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

use Dotclear\App;
use Dotclear\Core\Auth;
use Dotclear\Plugin\pages\Pages;

$this->registerModule(
    "Related pages", // Name
    "Serve pages & scripts", // Description
    "Pep, contributors, Nicolas Roudaire", // Author
    '2.0.0', // Version
    [
        'permissions' => App::auth()->makePermissions([Auth::PERMISSION_CONTENT_ADMIN, Pages::PERMISSION_PAGES]),
        'type' => 'plugin',
        'dc_min' => '2.28',
        'requires' => [['core', '2.28']],
        'repository' => 'https://github.com/nikrou/related',
        'support' => 'http://forum.dotclear.net/viewtopic.php?id=48205',
        'details' => 'http://plugins.dotaddict.org/dc2/details/related'
    ]
);
