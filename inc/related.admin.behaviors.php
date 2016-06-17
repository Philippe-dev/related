<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of Related, a plugin for DotClear2.
#
# Copyright(c) 2014-2016 Nicolas Roudaire <nikrou77@gmail.com> http://www.nikrou.net
#
# Copyright (c) 2006-2010 Pep and contributors.
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

class relatedAdminBehaviors
{
	/**
	 *
	 */
	public static function sitemapsDefineParts($map) {
		$map[__('Related pages')] = 'related';
	}

	public static function dashboardFavs($core,$favs) {
		$favs['related'] = new ArrayObject(array(
			'related',
			__('Related pages'),
			'plugin.php?p=related',
			'index.php?pf=related/icon.png',
			'index.php?pf=related/icon-big.png',
			'usage,contentadmin',
			null,
			null));
	}

    public static function dashboardFavsIcon($core, $name, $icon) {
        if ($name == 'related') {
            $params = new ArrayObject();
            $params['post_type'] = 'related';
            $page_count = $core->blog->getPosts($params,true)->f(0);
            if ($page_count > 0) {
                $str_pages = ($page_count > 1) ? __('%d related pages') : __('%d related page');
                $icon[0] = sprintf($str_pages,$page_count);
            } else {
                $icon[0] = __('Related pages');
            }
        }
    }
}
