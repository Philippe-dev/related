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

declare(strict_types=1);

namespace Dotclear\Plugin\related;

use Dotclear\App;

App::backend()->resources()->set('help', 'related_pages', __DIR__ . '/help/related_pages.html');
App::backend()->resources()->set('help', 'related_pages_edit', __DIR__ . '/help/related_pages_edit.html');
