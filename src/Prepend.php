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

use Dotclear\Core\Process;
use dcCore;

class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (My::settings()->active) {
            $url_prefix = My::settings()->url_prefix;
            $url_prefix = (empty($url_prefix))?'static':$url_prefix;
            $url_pattern = $url_prefix . '/(.+)$';

            dcCore::app()->url->register('related', $url_prefix, $url_pattern, [UrlHandler::class, 'related']);
            dcCore::app()->url->register('relatedpreview', 'relatedpreview', '^relatedpreview/(.+)$', [UrlHandler::class, 'relatedpreview']);
            unset($url_prefix, $url_pattern);

            // Registering new post_type
            dcCore::app()->setPostType('related', 'plugin.php?p=related&part=page&id=%d', dcCore::app()->url->getBase('related') . '/%s');
        }

        return true;
    }
}
