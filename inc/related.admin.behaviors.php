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

class relatedAdminBehaviors
{
    public static function sitemapsDefineParts($map)
    {
        $map[__('Related pages')] = 'related';
    }

    public static function dashboardFavorites(dcFavorites $favorites)
    {
        $favorites->register('related', [
            'title' => __('Related pages'),
            'url' => dcCore::app()->adminurl->get('admin.plugin.related'),
            'small-icon' => [dcPage::getPF('related/icon.svg'), dcPage::getPF('related/icon-dark.svg')],
            'large-icon' => [dcPage::getPF('related/icon.svg'), dcPage::getPF('related/icon-dark.svg')],
            'permissions' => dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_USAGE, dcAuth::PERMISSION_CONTENT_ADMIN
            ])
        ]);
    }

    public static function dashboardFavsIcon($name, $icon)
    {
        if ($name === 'related') {
            $params = new ArrayObject();
            $params['post_type'] = 'related';
            $page_count = dcCore::app()->blog->getPosts($params, true)->f(0);
            if ($page_count > 0) {
                $str_pages = ($page_count > 1) ? __('%d related pages') : __('%d related page');
                $icon[0] = sprintf($str_pages, $page_count);
            } else {
                $icon[0] = __('Related pages');
            }
        }
    }
}
