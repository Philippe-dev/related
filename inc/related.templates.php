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

// Templates tags definition and binding
class relatedTemplates
{
    public static function PageContent($attr)
    {
        $urls = '0';
        if (!empty($attr['absolute_urls'])) {
            $urls = '1';
        }
        $f = dcCore::app()->tpl->getFilters($attr);

        if (!empty($attr['full'])) {
            $content = 'echo ' . sprintf($f, 'dcCore::app()->ctx->posts->getExcerpt(' . $urls . ')." ".dcCore::app()->ctx->posts->getContent(' . $urls . ')') . ';';
        } else {
            $content = 'echo ' . sprintf($f, 'dcCore::app()->ctx->posts->getContent(' . $urls . ')') . ';';
        }

        $p =
        "<?php if ((\$related_file = dcCore::app()->ctx->posts->getRelatedFilename()) !== false) { \n" .
        	"if (files::getExtension(\$related_file) == 'php') { \n" .
        		'include $related_file;' . "\n" .
        	"} else { \n" .
        		'$previous_tpl_path = dcCore::app()->tpl->getPath();' . "\n" .
        		'dcCore::app()->tpl->setPath(dcCore::app()->blog->settings->related->files_path);' . "\n" .
        		'echo dcCore::app()->tpl->getData(basename($related_file));' . "\n" .
        		'dcCore::app()->tpl->setPath($previous_tpl_path);' . "\n" .
        		'unset($previous_tpl_path);' . "\n" .
        	"}\n" .
        	'unset($related_file);' . "\n" .
        "} else { \n" .
            $content .
        "} ?>\n";

        return $p;
    }
}
