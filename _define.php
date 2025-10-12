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
    '5.0',
    [
        'date'        => '2025-10-12T12:12:00+0100',
        'requires'    => [['core', '2.36']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'repository'  => 'https://github.com/Philippe-dev/related',
        'support'     => 'http://forum.dotclear.net/viewtopic.php?id=48205',
        'details'     => 'http://plugins.dotaddict.org/dc2/details/related',
    ]
);
