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
	    $w->related->setting('title', __('Title:'), '');
		$w->related->setting('homeonly', __('Display on:'), 0, 'combo',
                             array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
        $w->related->setting('content_only', __('Content only'), 0, 'check');
        $w->related->setting('class', __('CSS class:'), '');
	}

	public static function pagesList($w) {
		global $core;

        if (($w->homeonly == 1 && $core->url->type != 'default') ||
			($w->homeonly == 2 && $core->url->type == 'default')) {
			return;
		}

		$params['post_type'] = 'related';
		$params['no_content'] = true;
		$params['post_selected'] = true;
		$rs = $core->blog->getPosts($params);
		$rs->extend('rsRelatedBase');

		if ($rs->isEmpty()) {
			return;
		}

		$title = $w->title ? html::escapeHTML($w->title) : __('Related pages');

		$res =
		'<div id="related">'.
		'<h2>'.$title.'</h2>'.
		'<ul>';

		$pages_list = relatedHelpers::getPublicList($rs);
		foreach ($pages_list as $page) {
			$res .= '<li><a href="'.$page['url'].'">'.
			html::escapeHTML($page['title']).'</a></li>';
		}

		$res .= '</ul></div>';
		return $res;

        if (version_compare($core->getVersion(), '2.6', '>=')) {
            return '<div class="related-pages">'.$res.'</div>';
        } else {
            return $w->renderDiv($w->content_only, 'related-pages-widget '.$w->class, '', $res);
        }

	}
}
