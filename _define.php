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
declare(strict_types=1);

if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'Included pages',
        'Serve HTML templates & PHP scripts',
        'Pep, Nicolas Roudaire and contributors',
        '7.11.0',
        [
            'date'     => '2026-07-16T12:16:00+0100',
            'requires' => [
                ['core', '2.39'],
                ['TemplateHelper'],
            ],
            'permissions' => 'My',
            'type'        => 'plugin',
            'repository'  => 'https://github.com/Philippe-dev/related',
            'support'     => 'https://github.com/Philippe-dev/related/issues',
        ]
    );
}
