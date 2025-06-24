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

namespace Dotclear\Plugin\related;

use Dotclear\App;

App::backend()->resources()->set('help', 'related_pages', __DIR__ . '/help/related_pages.html');
App::backend()->resources()->set('help', 'related_pages_edit', __DIR__ . '/help/related_pages_edit.html');
