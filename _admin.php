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

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$_menu['Blog']->addItem(
    __('Related pages'),
    $core->adminurl->get('admin.plugin.related'),
    [dcPage::getPF('related/icon.svg'), dcPage::getPF('related/icon-dark.svg')],
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.related')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('contentadmin,pages', $core->blog->id)
);

$core->auth->setPermissionType('pages', __('manage related pages'));

$core->addBehavior('adminDashboardFavs', ['relatedAdminBehaviors', 'dashboardFavs']);
$core->addBehavior('adminDashboardFavsIcon', ['relatedAdminBehaviors', 'dashboardFavsIcon']);
$core->addBehavior('sitemapsDefineParts', ['relatedAdminBehaviors', 'sitemapsDefineParts']);

require dirname(__FILE__) . '/_widgets.php';
