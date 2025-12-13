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
    '5.6.1',
    [
        'date'        => '2025-12-13T12:18:00+0100',
        'requires'    => [['core', '2.36']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'repository'  => 'https://github.com/Philippe-dev/related',
        'support'     => 'https://github.com/Philippe-dev/related/issues',
    ]
);
