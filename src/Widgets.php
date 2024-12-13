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
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsElement;
use Dotclear\Plugin\widgets\WidgetsStack;

class Widgets
{
    public static function initDefaultWidgets(WidgetsStack $w, array $d)
    {
        $d['extra']->append($w->related);
    }

    public static function init(WidgetsStack $w)
    {
        $w->create('related', __('Related pages'), self::pagesList(...));
        $w->related->setting('title', __('Title (optional)') . ' :', __('Related pages'));
        $w->related->setting('limit', __('Pages limit:'), 10);
        $w->related->setting(
            'homeonly',
            __('Display on:'),
            0,
            'combo',
            [__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2]
        );
        $w->related->setting('content_only', __('Content only'), 0, 'check');
        $w->related->setting('class', __('CSS class:'), '');
        $w->related->setting('offline', __('Offline'), 0, 'check');
    }

    public static function pagesList(WidgetsElement $w)
    {
        if ($w->offline) {
            return;
        }

        if (($w->homeonly == 1 && App::url()->getType() != 'default') ||
        ($w->homeonly == 2 && App::url()->getType() == 'default')) {
            return;
        }

        $params['post_type'] = 'related';
        $params['no_content'] = true;
        $params['post_selected'] = true;
        $params['limit'] = abs((integer) $w->limit);
        $rs = App::blog()->getPosts($params);
        $rs->extend(RsRelated::class);

        if ($rs->isEmpty()) {
            return;
        }

        $res = ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) : '');
        $res .= '<ul>';

        $pages_list = PagesHelper::getPublicList($rs);
        foreach ($pages_list as $page) {
            $res .= '<li><a href="' . $page['url'] . '">' . Html::escapeHTML($page['title']) . '</a></li>';
        }

        $res .= '</ul>';

        return $w->renderDiv((bool) $w->content_only, 'related-pages-widget ' . $w->class, '', $res);
    }
}
