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

// Public behaviors definition and binding
/**
 *
 */
class relatedPublicBehaviors
{
	/**
	 *
	 */
	public static function addTplPath($core) {
        $tplset = $core->themes->moduleInfo($core->blog->settings->system->theme, 'tplset');
        if (!empty($tplset) && is_dir(dirname(__FILE__).'/../default-templates/'.$tplset)) {
            $core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__).'/../default-templates/'.$tplset);
        } else {
            $core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__).'/../default-templates/'.DC_DEFAULT_TPLSET);
        }
	}

	/**
	 *
	 */
	public static function templateBeforeBlock() {
		$args = func_get_args();
		array_shift($args);

		if ($args[0] == 'Entries') {
			if (!empty($args[1])) {
				$attrs = $args[1];
				if (!empty($attrs['type']) && $attrs['type'] == 'related') {
					$p = "<?php \$params['post_type'] = 'related'; ?>\n";
					if (!empty($attrs['basename'])) {
						$p .= "<?php \$params['post_url'] = '".$attrs['basename']."'; ?>\n";
					}
					return $p;
				}
			}
		}
	}

	/**
	 *
	 */
	public static function coreBlogGetPosts($rs) {
		$rs->extend("rsRelatedBase");
	}

	/**
	 *
	 */
	public static function sitemapsURLsCollect($sitemaps) {
		global $core;

		if ($core->blog->settings->sitemaps->sitemaps_related_url) {
			$freq = $sitemaps->getFrequency($core->blog->settings->sitemaps->sitemaps_related_fq);
			$prio = $sitemaps->getPriority($core->blog->settings->sitemaps->sitemaps_related_pr);

			$rs = $core->blog->getPosts(array('post_type' => 'related','post_status' => 1,'no_content' => true));
			$rs->extend('rsRelated');

			while ($rs->fetch()) {
				if ($rs->post_password != '') continue;
				$sitemaps->addEntry($rs->getURL(),$prio,$freq,$rs->getISO8601Date());
			}
		}
	}
}
