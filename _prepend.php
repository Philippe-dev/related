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

if (!defined('DC_RC_PATH')) return;

$__autoload['relatedHelpers'] = dirname(__FILE__).'/inc/related.helpers.php';
$__autoload['rsRelated'] = dirname(__FILE__).'/inc/lib.related.php';
$__autoload['adminPageList'] = dirname(__FILE__).'/inc/lib.related.php';
$__autoload['widgetsRelated'] = dirname(__FILE__).'/inc/widgets.related.php';
$__autoload['relatedAdminBehaviors'] = dirname(__FILE__).'/inc/related.admin.behaviors.php';
$__autoload['relatedUrlHandlers'] = dirname(__FILE__).'/inc/related.url.handlers.php';
$__autoload['rsRelatedBase'] = dirname(__FILE__).'/inc/rs.related.base.php';

// Setting custom URL handlers
$url_prefix = $core->blog->settings->related->url_prefix;
$url_prefix = (empty($url_prefix))?'static':$url_prefix;
$url_pattern = $url_prefix.'/(.+)$';
$core->url->register('related',$url_prefix,$url_pattern,array('relatedUrlHandlers','related'));
$core->url->register('relatedpreview','relatedpreview','^relatedpreview/(.+)$',array('relatedUrlHandlers','relatedpreview'));
unset($url_prefix,$url_pattern);

// Registering new post_type
$core->setPostType('related','plugin.php?p=related&do=edit&id=%d',$core->url->getBase('related').'/%s');
