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

use Dotclear\Core\Backend\Utility;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\App;

class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem(Utility::MENU_BLOG);

        App::behavior()->addBehavior('adminDashboardFavoritesV2', BackendBehaviors::dashboardFavorites(...));
        App::behavior()->addBehavior('adminPostFilterV2', BackendBehaviors::adminPostFilter(...));
        App::behavior()->addBehavior('initWidgets', Widgets::initWidgets(...));

        return true;
    }
}
