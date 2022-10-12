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

class relatedPublicBehaviors
{
    public static function addTplPath()
    {
        $tplset = dcCore::app()->themes->moduleInfo(dcCore::app()->blog->settings->system->theme, 'tplset');
        if (!empty($tplset) && is_dir(__DIR__ . '/../default-templates/' . $tplset)) {
            dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), __DIR__ . '/../default-templates/' . $tplset);
        } else {
            dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), __DIR__ . '/../default-templates/' . DC_DEFAULT_TPLSET);
        }
    }

    public static function templateBeforeBlock()
    {
        $args = func_get_args();
        array_shift($args);

        if ($args[0] === 'Entries') {
            if (!empty($args[1])) {
                $attrs = $args[1];
                if (!empty($attrs['type']) && $attrs['type'] == 'related') {
                    $p = "<?php \$params['post_type'] = 'related'; ?>\n";
                    if (!empty($attrs['basename'])) {
                        $p .= "<?php \$params['post_url'] = '" . $attrs['basename'] . "'; ?>\n";
                    }
                    return $p;
                }
            }
        }
    }

    public static function coreBlogGetPosts($rs)
    {
        $rs->extend("rsRelatedBase");
    }

    public static function sitemapsURLsCollect($sitemaps)
    {
        if (dcCore::app()->blog->settings->sitemaps->sitemaps_related_url) {
            $freq = $sitemaps->getFrequency(dcCore::app()->blog->settings->sitemaps->sitemaps_related_fq);
            $prio = $sitemaps->getPriority(dcCore::app()->blog->settings->sitemaps->sitemaps_related_pr);

            $rs = dcCore::app()->blog->getPosts(['post_type' => 'related', 'post_status' => dcBlog::POST_PUBLISHED, 'no_content' => true]);
            $rs->extend('rsRelated');

            while ($rs->fetch()) {
                if ($rs->post_password != '') {
                    continue;
                }
                $sitemaps->addEntry($rs->getURL(), $prio, $freq, $rs->getISO8601Date());
            }
        }
    }
}
