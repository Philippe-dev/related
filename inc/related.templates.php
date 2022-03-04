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

// Templates tags definition and binding
/**
 *
 */
class relatedTemplates
{
	/**
	 *
	 */
	public static function PageContent($attr) {
		global $core, $_ctx;

		$urls = '0';
		if (!empty($attr['absolute_urls'])) {
			$urls = '1';
		}
		$f = $core->tpl->getFilters($attr);

        if (!empty($attr['full'])) {
            $content = 'echo '.sprintf($f, '$_ctx->posts->getExcerpt('.$urls.')." ".$_ctx->posts->getContent('.$urls.')').';';
        } else {
            $content = 'echo '.sprintf($f,'$_ctx->posts->getContent('.$urls.')').';';
        }

		$p =
		"<?php if ((\$related_file = \$_ctx->posts->getRelatedFilename()) !== false) { \n".
			"if (files::getExtension(\$related_file) == 'php') { \n".
				'include $related_file;'."\n".
			"} else { \n".
				'$previous_tpl_path = $core->tpl->getPath();'."\n".
				'$core->tpl->setPath($core->blog->settings->related->files_path);'."\n".
				'echo $core->tpl->getData(basename($related_file));'."\n".
				'$core->tpl->setPath($previous_tpl_path);'."\n".
				'unset($previous_tpl_path);'."\n".
			"}\n".
			'unset($related_file);'."\n".
		"} else { \n".
            $content.
		"} ?>\n";

		return $p;
	}
}
