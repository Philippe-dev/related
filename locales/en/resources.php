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

declare(strict_types=1);

namespace Dotclear\Plugin\related;

use Dotclear\App;

App::backend()->resources()->set('help', 'related_pages', __DIR__ . '/help/related_pages.html');
App::backend()->resources()->set('help', 'related_pages_edit', __DIR__ . '/help/related_pages_edit.html');
