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

dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
    __('Related pages'),
    dcCore::app()->adminurl->get('admin.plugin.related'),
    [dcPage::getPF('related/icon.svg'), dcPage::getPF('related/icon-dark.svg')],
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.related')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcPages::PERMISSION_PAGES, dcAuth::PERMISSION_CONTENT_ADMIN]), dcCore::app()->blog->id)
);

dcCore::app()->auth->setPermissionType('pages', __('manage related pages'));

dcCore::app()->addBehavior('adminDashboardFavoritesV2', [relatedAdminBehaviors::class, 'dashboardFavorites']);
dcCore::app()->addBehavior('adminDashboardFavsIconV2', [relatedAdminBehaviors::class, 'dashboardFavsIcon']);
dcCore::app()->addBehavior('sitemapsDefineParts', [relatedAdminBehaviors::class, 'sitemapsDefineParts']);

require __DIR__ . '/_widgets.php';
