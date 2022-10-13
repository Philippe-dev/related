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

$self_ns = dcCore::app()->blog->settings->addNamespace('related');
if ($self_ns->active) {
    dcCore::app()->addBehavior('coreBlogGetPosts', [relatedPublicBehaviors::class, 'coreBlogGetPosts']);
    dcCore::app()->addBehavior('publicBeforeDocument', [relatedPublicBehaviors::class, 'addTplPath']);
    dcCore::app()->addBehavior('templateBeforeBlock', [relatedPublicBehaviors::class, 'templateBeforeBlock']);
    dcCore::app()->addBehavior('sitemapsURLsCollect', [relatedPublicBehaviors::class, 'sitemapsURLsCollect']);
    dcCore::app()->addBehavior('initWidgets', [widgetsRelated::class, 'init']);

    dcCore::app()->tpl->addValue('EntryContent', [relatedTemplates::class, 'PageContent']);
}

dcCore::app()->addBehavior('publicBreadcrumb', [relatedBehavior::class, 'publicBreadcrumb']);

class relatedBehavior
{
    public static function publicBreadcrumb($context, $separator)
    {
        if ($context === 'related') {
            return  dcCore::app()->ctx->posts->post_title;
        }
    }
}
