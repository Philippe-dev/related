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

use Dotclear\Core\Auth;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\Page;
use Dotclear\App;

class AdminBehaviors
{
    public static function dashboardFavorites(Favorites $favorites)
    {
        $favorites->register('related', [
            'title' => __('Related pages'),
            'url' => My::manageUrl(),
            'small-icon' => [Page::getPF('related/icon.svg'), Page::getPF('related/icon-dark.svg')],
            'large-icon' => [Page::getPF('related/icon.svg'), Page::getPF('related/icon-dark.svg')],
            'permissions' => App::auth()->makePermissions([
                Auth::PERMISSION_USAGE, Auth::PERMISSION_CONTENT_ADMIN
            ])
        ]);
    }

    public static function dashboardFavsIcon($name, $icon)
    {
        if ($name === 'related') {
            $params = [];
            $params['post_type'] = 'related';
            $page_count = App::blog()->getPosts($params, true)->f(0);
            if ($page_count > 0) {
                $str_pages = ($page_count > 1) ? __('%d related pages') : __('%d related page');
                $icon[0] = sprintf($str_pages, $page_count);
            } else {
                $icon[0] = __('Related pages');
            }
        }
    }
}
