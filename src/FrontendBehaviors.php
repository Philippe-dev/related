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
use Dotclear\Database\MetaRecord;
use Dotclear\Core\Frontend\Utility;
use Dotclear\Helper\File\Path;

class FrontendBehaviors
{
    public static function publicBreadcrumb($context, $separator)
    {
        if ($context == 'related') {
            return App::frontend()->context()->posts->post_title;
        }
    }

    /*
     * Adds related tpl path.
     */
    public static function publicBeforeDocument(): void
    {
        $tplset           = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'tplset');
        $default_template = Path::real(My::path()) . DIRECTORY_SEPARATOR . Utility::TPL_ROOT . DIRECTORY_SEPARATOR;

        if (!empty($tplset) && is_dir($default_template . $tplset)) {
            App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . $tplset);
        } else {
            App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . App::config()->defaultTplset());
        }
    }

    /*
     * @param  ArrayObject<array-key, mixed>    $attr
     */
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
    /**
     * Overload posts record extension
     *
     * @param [type] $rs
     * @return string
     */
    public static function coreBlogGetPosts(MetaRecord $rs): void
    {
        $rs->extend(self::class, 'getRelatedFilename');
    }

    /**
     * getRelatedFilename function
     *
     * @param [type] $rs
     * @return bool|string
     */
    public static function getRelatedFilename($rs): bool|string
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
