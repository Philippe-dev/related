<?php
/**
 * @brief related, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Pep, Nicolas Roudaire and contributors
 *
 * @copyright GPL-2.0 [https://www.gnu.org/licenses/gpl-2.0.html]
 */

 $this->registerModule(
    'Related pages', // Name
    'Serve pages & scripts', // Description
    'Pep, Nicolas Roudaire and contributors', // Author
    '2.4', // Version
    [
        'permissions' => 'My',
        'type'        => 'plugin',
        'dc_min'      => '2.32',
        'requires'    => [['core', '2.32']],
        'repository'  => 'https://github.com/Philippe-dev/related',
        'support'     => 'http://forum.dotclear.net/viewtopic.php?id=48205',
        'details'     => 'http://plugins.dotaddict.org/dc2/details/related',
    ]
);
