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

use Dotclear\Core\Backend\Menus;
use Dotclear\Core\Process;
use dcCore;

class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem(Menus::MENU_BLOG);

        dcCore::app()->addBehavior('adminDashboardFavoritesV2', [AdminBehaviors::class, 'dashboardFavorites']);
        dcCore::app()->addBehavior('adminDashboardFavsIconV2', [AdminBehaviors::class, 'dashboardFavsIcon']);

        dcCore::app()->addBehavior('initWidgets', [Widgets::class, 'init']);
        dcCore::app()->addBehavior('initDefaultWidgets', [Widgets::class, 'initDefaultWidgets']);

        return true;
    }
}
