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

use Dotclear\Core\Backend\Menus;
use Dotclear\Core\Process;
use Dotclear\App;

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

        App::behavior()->addBehavior('adminDashboardFavoritesV2', AdminBehaviors::dashboardFavorites(...));
        App::behavior()->addBehavior('adminDashboardFavsIconV2', AdminBehaviors::dashboardFavsIcon(...));

        App::behavior()->addBehavior('initWidgets', Widgets::init(...));
        App::behavior()->addBehavior('initDefaultWidgets', Widgets::initDefaultWidgets(...));

        return true;
    }
}
