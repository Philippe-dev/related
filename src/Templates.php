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

declare(strict_types=1);

namespace Dotclear\Plugin\related;

use Dotclear\App;

class Templates
{
    public static function PageContent($attr)
    {
        $urls = '0';
        if (!empty($attr['absolute_urls'])) {
            $urls = '1';
        }

        $f = App::frontend()->template()->getFilters($attr);

        if (!empty($attr['full'])) {
            $content = 'echo ' . sprintf($f, 'App::frontend()->context()->posts->getExcerpt(' . $urls . ')." ".App::frontend()->context()->posts->getContent(' . $urls . ')') . ';';
        } else {
            $content = 'echo ' . sprintf($f, 'App::frontend()->context()->posts->getContent(' . $urls . ')') . ';';
        }

        $p =
        "<?php if ((\$related_file = App::frontend()->context()->posts->getRelatedFilename()) !== false) { \n" .
        	"if (Dotclear\Helper\File\Files::getExtension(\$related_file) == 'php') { \n" .
        		'include $related_file;' . "\n" .
        	"} else { \n" .
        		'$previous_tpl_path = App::frontend()->template()->getPath();' . "\n" .
        		'App::frontend()->template()->setPath(Dotclear\Plugin\related\My::settings()->files_path);' . "\n" .
        		'echo App::frontend()->template()->getData(basename($related_file));' . "\n" .
        		'App::frontend()->template()->setPath($previous_tpl_path);' . "\n" .
        		'unset($previous_tpl_path);' . "\n" .
        	"}\n" .
        	'unset($related_file);' . "\n" .
        "} else { \n" .
            $content .
        "} ?>\n";

        return $p;
    }
}
