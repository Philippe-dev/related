<?php
/**
 * @brief related, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Pep, Nicolas Roudaire and contributors
 *
 * @copyright GPL-2.0 [https://www.gnu.org/licenses/gpl-2.0.html]
 */

declare(strict_types=1);

namespace Dotclear\Plugin\related;

use Dotclear\App;

class PublicBehaviors
{
    public static function publicBeforeDocument()
    {
        $tplset = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'tplset');
        if (!empty($tplset) && is_dir(__DIR__ . '/../default-templates/' . $tplset)) {
            App::frontend()->template()->setPath(App::frontend()->template()->getPath(), __DIR__ . '/../default-templates/' . $tplset);
        } else {
            App::frontend()->template()->setPath(App::frontend()->template()->getPath(), __DIR__ . '/../default-templates/' . DC_DEFAULT_TPLSET);
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
        $rs->extend(RsRelated::class);
    }
}
