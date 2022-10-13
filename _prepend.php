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

Clearbricks::lib()->autoload(
    [
        'relatedHelpers' => __DIR__ . '/inc/related.helpers.php',
        'adminPageList' => __DIR__ . '/inc/admin.page.list.php',
        'widgetsRelated' => __DIR__ . '/inc/widgets.related.php',
        'relatedAdminBehaviors' => __DIR__ . '/inc/related.admin.behaviors.php',
        'relatedPublicBehaviors' => __DIR__ . '/inc/related.public.behaviors.php',
        'relatedUrlHandlers' => __DIR__ . '/inc/related.url.handlers.php',
        'relatedTemplates' => __DIR__ . '/inc/related.templates.php',
        'relatedPagesActionsPage' => __DIR__ . '/inc/related.pages.actionspage.php',
        'rsRelated' => __DIR__ . '/inc/rs.related.php',
        'rsRelatedBase' => __DIR__ . '/inc/rs.related.base.php',
    ]
);

$self_ns = dcCore::app()->blog->settings->addNamespace('related');
if ($self_ns->active) {
    // Setting custom URL handlers
    $url_prefix = dcCore::app()->blog->settings->related->url_prefix;
    $url_prefix = (empty($url_prefix))?'static':$url_prefix;
    $url_pattern = $url_prefix . '/(.+)$';
    dcCore::app()->url->register('related', $url_prefix, $url_pattern, [relatedUrlHandlers::class, 'related']);
    dcCore::app()->url->register('relatedpreview', 'relatedpreview', '^relatedpreview/(.+)$', [relatedUrlHandlers::class, 'relatedpreview']);
    unset($url_prefix, $url_pattern);

    // Registering new post_type
    dcCore::app()->setPostType('related', 'plugin.php?p=related&do=edit&id=%d', dcCore::app()->url->getBase('related') . '/%s');
}
