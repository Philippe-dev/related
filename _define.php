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
    'Included pages',
    'Serve HTML templates & PHP scripts',
    'Pep, Nicolas Roudaire and contributors',
    '4.1',
    [
        'date'        => '2025-07-02T00:00:17+0100',
        'permissions' => 'My',
        'type'        => 'plugin',
        'dc_min'      => '2.34',
        'requires'    => [['core', '2.34']],
        'repository'  => 'https://github.com/Philippe-dev/related',
        'support'     => 'http://forum.dotclear.net/viewtopic.php?id=48205',
        'details'     => 'http://plugins.dotaddict.org/dc2/details/related',
    ]
);
