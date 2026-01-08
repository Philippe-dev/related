<?php
/**
 * @brief related, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Pep, Nicolas Roudaire and contributors
 *
 * @copyright AGPL-3.0
 */

declare(strict_types=1);

namespace Dotclear\Plugin\related;

use ArrayObject;
use Dotclear\App;

class FrontendBehaviors
{
    public static function publicBreadcrumb($context, $separator)
    {
        if ($context == 'related') {
            return App::frontend()->context()->posts->post_title;
        }
    }

    public static function publicBeforeDocument(): void
    {
        $tplset = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'tplset');
        if (!empty($tplset) && is_dir(__DIR__ . '/../default-templates/' . $tplset)) {
            App::frontend()->template()->setPath(App::frontend()->template()->getPath(), __DIR__ . '/../default-templates/' . $tplset);
        } else {
            App::frontend()->template()->setPath(App::frontend()->template()->getPath(), __DIR__ . '/../default-templates/' . DC_DEFAULT_TPLSET);
        }
    }

    public static function templateBeforeBlock(string $block, ArrayObject $attr): string
    {
        if ($block === 'Entries') {
            if (!empty($attr['type']) && $attr['type'] == 'related') {
                $p = "<?php \$params['post_type'] = 'related'; ?>\n";
                if (!empty($attr['basename'])) {
                    $p .= "<?php \$params['post_url'] = '" . $attr['basename'] . "'; ?>\n";
                }

                return $p;
            }
        }

        return '';
    }

    public static function coreBlogGetPosts($rs)
    {
        $rs->extend(self::class, 'getRelatedFilename');
    }

    public static function getRelatedFilename($rs)
    {
        if (is_null(App::blog()->settings()->related->files_path)) {
            return false;
        }

        $meta_rs = App::meta()->getMetaRecordset($rs->post_meta, 'related_file');

        if (!$meta_rs->isEmpty()) {
            $filename = App::blog()->settings()->related->files_path . '/' . $meta_rs->meta_id;
            if (is_readable($filename)) {
                return $filename;
            }

            return false;
        }

        return false;
    }
}
