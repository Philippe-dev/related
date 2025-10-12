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

use Dotclear\Core\Auth;
use Dotclear\Core\Backend\Favorites;
use Dotclear\App;

class BackendBehaviors
{
    public static function dashboardFavorites(Favorites $favorites)
    {
        $favorites->register('related', [
            'title'       => __('Included pages'),
            'url'         => My::manageUrl(),
            'small-icon'  => My::icons(),
            'large-icon'  => My::icons(),
            'permissions' => App::auth()->makePermissions([
                Auth::PERMISSION_USAGE, Auth::PERMISSION_CONTENT_ADMIN,
            ]),
        ]);
    }

    public static function dashboardFavsIcon($name, $icon)
    {
        if ($name === 'related') {
            $params              = [];
            $params['post_type'] = 'related';
            $page_count          = App::blog()->getPosts($params, true)->f(0);
            if ($page_count > 0) {
                $str_pages = ($page_count > 1) ? __('%d included pages') : __('%d included page');
                $icon[0]   = sprintf($str_pages, $page_count);
            } else {
                $icon[0] = __('Included pages');
            }
        }
    }
}
