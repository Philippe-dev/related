<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of Related, a plugin for DotClear2.
#
# Copyright(c) 2014-2015 Nicolas Roudaire <nikrou77@gmail.com> http://www.nikrou.net
#
# Copyright (c) 2006-2010 Pep and contributors.
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

class widgetsRelated
{
    public static function initDefaultWidgets($w, $d) {
        $d['extra']->append($w->related);
    }

	public static function init($w) {
	    $w->create('related', __('Related pages'), array('widgetsRelated', 'pagesList'));
        $w->related->setting('title',__('Title (optional)').' :',__('Related pages'));
        $w->related->setting('limit',__('Pages limit:'),10);
		$w->related->setting('homeonly', __('Display on:'), 0, 'combo',
                             array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
        $w->related->setting('content_only', __('Content only'), 0, 'check');
        $w->related->setting('class', __('CSS class:'), '');
        $w->related->setting('offline',__('Offline'),0,'check');
	}

	public static function pagesList($w) {
		global $core;

        if ($w->offline) {
			return;
        }

        if (($w->homeonly == 1 && $core->url->type != 'default') ||
			($w->homeonly == 2 && $core->url->type == 'default')) {
			return;
		}

		$params['post_type'] = 'related';
		$params['no_content'] = true;
		$params['post_selected'] = true;
        $params['limit'] = abs((integer) $w->limit);
		$rs = $core->blog->getPosts($params);
		$rs->extend('rsRelatedBase');

		if ($rs->isEmpty()) {
			return;
		}

        $res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '');
		$res .= '<ul>';

		$pages_list = relatedHelpers::getPublicList($rs);
		foreach ($pages_list as $page) {
			$res .= '<li><a href="'.$page['url'].'">'.html::escapeHTML($page['title']).'</a></li>';
		}

		$res .= '</ul>';

        return $w->renderDiv($w->content_only, 'related-pages-widget '.$w->class, '', $res);
	}
}
