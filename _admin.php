<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of Related, a plugin for DotClear2.
#
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) return;

$_menu['Blog']->addItem(
	__('Related pages'),
	'plugin.php?p=related',
	'index.php?pf=related/icon.svg',
	preg_match('/plugin.php\?p=related(&.*)?$/',$_SERVER['REQUEST_URI']),
	$core->auth->check('contentadmin,pages',$core->blog->id)
);

$core->auth->setPermissionType('pages',__('manage related pages'));

$core->addBehavior('adminDashboardFavs', array('relatedAdminBehaviors', 'dashboardFavs'));
$core->addBehavior('adminDashboardFavsIcon', array('relatedAdminBehaviors', 'dashboardFavsIcon'));
$core->addBehavior('sitemapsDefineParts',array('relatedAdminBehaviors','sitemapsDefineParts'));

require dirname(__FILE__).'/_widgets.php';
