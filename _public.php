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
    dcCore::app()->addBehavior('coreBlogGetPosts', ['relatedPublicBehaviors', 'coreBlogGetPosts']);
    dcCore::app()->addBehavior('publicBeforeDocument', ['relatedPublicBehaviors', 'addTplPath']);
    dcCore::app()->addBehavior('templateBeforeBlock', ['relatedPublicBehaviors', 'templateBeforeBlock']);
    dcCore::app()->addBehavior('sitemapsURLsCollect', ['relatedPublicBehaviors', 'sitemapsURLsCollect']);
    dcCore::app()->addBehavior('initWidgets', ['widgetsRelated', 'init']);

    dcCore::app()->tpl->addValue('EntryContent', ['relatedTemplates', 'PageContent']);
}

dcCore::app()->addBehavior('publicBreadcrumb', ['relatedBehavior', 'publicBreadcrumb']);

class relatedBehavior
{
    public static function publicBreadcrumb($context, $separator)
    {
        if ($context === 'related') {
            return  dcCore::app()->ctx->posts->post_title;
        }
    }
}
