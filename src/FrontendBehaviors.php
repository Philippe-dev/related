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
use Dotclear\Core\Frontend\Utility;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\File\Path;

class FrontendBehaviors
{
    public static function publicBreadcrumb(string $context): string
    {
        if ($context === 'related'
            && App::frontend()->context()->posts instanceof MetaRecord
        ) {
            return App::frontend()->context()->posts->strField('post_title');
        }

        return '';
    }

    /**
     * Adds related tpl path.
     */
    public static function publicBeforeDocument(): void
    {
        $theme  = is_string($theme = App::blog()->settings()->system->theme) ? $theme : '';
        $tplset = is_string($tplset = App::themes()->moduleInfo($theme, 'tplset')) ? $tplset : '';

        $default_template = Path::real(My::path()) . DIRECTORY_SEPARATOR . Utility::TPL_ROOT . DIRECTORY_SEPARATOR;
        if ($tplset !== '' && is_dir($default_template . $tplset)) {
            App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . $tplset);
        } else {
            App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . App::config()->defaultTplset());
        }
    }

    /**
     * @param  ArrayObject<array-key, mixed>    $attr
     */
    public static function templateBeforeBlock(string $block, ArrayObject $attr): string
    {
        if ($block === 'Entries' && !empty($attr['type']) && $attr['type'] == 'related') {
            $p = "<?php \$params['post_type'] = 'related'; ?>\n";
            if (isset($attr['basename']) && is_string($attr['basename']) && $attr['basename'] !== '') {
                $p .= "<?php \$params['post_url'] = '" . $attr['basename'] . "'; ?>\n";
            }

            return $p;
        }

        return '';
    }

    /**
     * Overload posts record extension
     */
    public static function coreBlogGetPosts(MetaRecord $rs): void
    {
        $rs->extend(RelatedExtentions::class);
    }
}
