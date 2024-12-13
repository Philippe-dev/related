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
use Dotclear\Core\Process;

class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehavior('coreBlogGetPosts', [PublicBehaviors::class, 'coreBlogGetPosts']);
        App::behavior()->addBehavior('publicBeforeDocument', [PublicBehaviors::class, 'publicBeforeDocument']);
        App::behavior()->addBehavior('templateBeforeBlock', [PublicBehaviors::class, 'templateBeforeBlock']);

        App::frontend()->template()->addValue('EntryContent', [Templates::class, 'PageContent']);

        App::behavior()->addBehavior('initWidgets', [Widgets::class, 'init']);
        App::behavior()->addBehavior('initDefaultWidgets', [Widgets::class, 'initDefaultWidgets']);

        return true;
    }
}
