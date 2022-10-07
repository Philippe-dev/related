<?php

# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of Related, a plugin for DotClear2.
#
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) {
    return;
}

$this->registerModule(
    /* Name */		
    "Related Pages",
    /* Description*/	
    "Serve pages & scripts",
    /* Author */		
    "Pep, contributors, Nicolas Roudaire",
    /* Version */		
    '1.7.4',
    /* Properties */	
    array('contentadmin,pages',
                              'type' => 'plugin',
                              'dc_min' => '2.24',
                              'support' => 'http://forum.dotclear.net/viewtopic.php?id=48205',
                              'details' => 'http://plugins.dotaddict.org/dc2/details/related'
    )
);
