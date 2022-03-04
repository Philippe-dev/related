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

$self_ns = $core->blog->settings->addNamespace('related');
if ($self_ns->active) {
    $core->addBehavior('coreBlogGetPosts', array('relatedPublicBehaviors', 'coreBlogGetPosts'));
    $core->addBehavior('publicBeforeDocument', array('relatedPublicBehaviors', 'addTplPath'));
    $core->addBehavior('templateBeforeBlock', array('relatedPublicBehaviors', 'templateBeforeBlock'));
    $core->addBehavior('sitemapsURLsCollect', array('relatedPublicBehaviors', 'sitemapsURLsCollect'));
    $core->addBehavior('initWidgets', array('widgetsRelated', 'init'));

    $core->tpl->addValue('EntryContent', array('relatedTemplates', 'PageContent'));
}

$core->addBehavior('publicBreadcrumb', ['relatedBehavior', 'publicBreadcrumb']);

class relatedBehavior
{
    public static function publicBreadcrumb($context, $separator)
    {
        if ($context == 'related') {
            global $_ctx;
            return  $_ctx->posts->post_title;
        }
    }
}
