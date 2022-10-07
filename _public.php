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

if (!defined('DC_RC_PATH')) {
    return;
}

$self_ns = dcCore::app()->blog->settings->addNamespace('related');
if ($self_ns->active) {
    dcCore::app()->addBehavior('coreBlogGetPosts', array('relatedPublicBehaviors', 'coreBlogGetPosts'));
    dcCore::app()->addBehavior('publicBeforeDocument', array('relatedPublicBehaviors', 'addTplPath'));
    dcCore::app()->addBehavior('templateBeforeBlock', array('relatedPublicBehaviors', 'templateBeforeBlock'));
    dcCore::app()->addBehavior('sitemapsURLsCollect', array('relatedPublicBehaviors', 'sitemapsURLsCollect'));
    dcCore::app()->addBehavior('initWidgets', array('widgetsRelated', 'init'));

    dcCore::app()->tpl->addValue('EntryContent', array('relatedTemplates', 'PageContent'));
}

dcCore::app()->addBehavior('publicBreadcrumb', ['relatedBehavior', 'publicBreadcrumb']);

class relatedBehavior
{
    public static function publicBreadcrumb($context, $separator)
    {
        if ($context == 'related') {
            return  dcCore::app()->ctx->posts->post_title;
        }
    }
}
