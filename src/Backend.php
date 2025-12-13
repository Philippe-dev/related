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

use ArrayObject;
use Dotclear\Core\Backend\Utility;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Stack\Filter;
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
        App::behavior()->addBehavior('adminDashboardFavsIconV2', BackendBehaviors::dashboardFavsIcon(...));

        App::behavior()->addBehavior('adminPostFilterV2', [self::class,  'adminPostFilter']);

        App::behavior()->addBehavior('initWidgets', Widgets::initWidgets(...));

        return true;
    }

    public static function adminPostFilter(ArrayObject $filters)
    {
        if (App::backend()->getPageURL() === App::backend()->url()->get('admin.plugin.' . My::id())) {
            $filters->append((new Filter('comment'))
                ->param());

            $filters->append((new Filter('trackback'))
                ->param());

            $filters->append((new Filter('cat_id'))
                ->param());

            $filters->append((new Filter('selected'))
                ->param('post_selected')
                ->title(__('In widget:'))
                ->options([
                    '-'       => '',
                    __('yes') => '1',
                    __('no')  => '0',
                ]));

        }
    }
}
