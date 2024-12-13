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
    '2.1.1', // Version
    [
        'permissions' => 'My',
        'type' => 'plugin',
        'dc_min' => '2.32',
        'requires' => [['core', '2.32']],
        'repository' => 'https://github.com/Philippe-dev/related',
        'support' => 'http://forum.dotclear.net/viewtopic.php?id=48205',
        'details' => 'http://plugins.dotaddict.org/dc2/details/related'
    ]
);
