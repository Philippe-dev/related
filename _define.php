<?php
/**
 * @brief related, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Pep, Nicolas Roudaire and contributors
 *
 * @copyright AGPL-3.0
 */
$this->registerModule(
    'Related pages',
    'Serve pages & scripts',
    'Pep, Nicolas Roudaire and contributors',
    '3.2',
    [
        'date'        => '2025-06-27T00:00:17+0100',
        'permissions' => 'My',
        'type'        => 'plugin',
        'dc_min'      => '2.34',
        'requires'    => [['core', '2.34']],
        'repository'  => 'https://github.com/Philippe-dev/related',
        'support'     => 'http://forum.dotclear.net/viewtopic.php?id=48205',
        'details'     => 'http://plugins.dotaddict.org/dc2/details/related',
    ]
);
