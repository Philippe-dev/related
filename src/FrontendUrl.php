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
use Dotclear\Core\Url;
use Dotclear\Core\Frontend\Utility;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text;
use Exception;

/**
 * @brief   The module frontend URL.
 * @ingroup pages
 */
class FrontendUrl extends Url
{
    /**
     * Output the Page page.
     *
     * @param   null|string     $args   The arguments
     */
    public static function related(?string $args): void
    {
        if ($args == '') {
            // No page was specified.
            self::p404();
        } else {
            App::blog()->withoutPassword(false);

            $params = new ArrayObject([
                'post_type' => 'related',
                'post_url'  => $args, ]);

            # --BEHAVIOR-- publicPagesBeforeGetPosts -- ArrayObject, string
            App::behavior()->callBehavior('publicPagesBeforeGetPosts', $params, $args);

            App::frontend()->context()->posts = App::blog()->getPosts($params);

            App::blog()->withoutPassword(true);

            if (App::frontend()->context()->posts->isEmpty()) {
                # The specified page does not exist.
                self::p404();
            } else {
                $post_id       = App::frontend()->context()->posts->post_id;
                $post_password = App::frontend()->context()->posts->post_password;

                # Password protected entry
                if ($post_password != '' && !App::frontend()->context()->preview) {
                    # Get passwords cookie
                    if (isset($_COOKIE['dc_passwd'])) {
                        $pwd_cookie = json_decode((string) $_COOKIE['dc_passwd'], null, 512, JSON_THROW_ON_ERROR);
                        $pwd_cookie = $pwd_cookie === null ? [] : (array) $pwd_cookie;
                    } else {
                        $pwd_cookie = [];
                    }

                    # Check for match
                    # Note: We must prefix post_id key with '#'' in pwd_cookie array in order to avoid integer conversion
                    # because MyArray["12345"] is treated as MyArray[12345]
                    if ((!empty($_POST['password']) && $_POST['password'] == $post_password)
                        || (isset($pwd_cookie['#' . $post_id]) && $pwd_cookie['#' . $post_id] == $post_password)) {
                        $pwd_cookie['#' . $post_id] = $post_password;
                        setcookie('dc_passwd', json_encode($pwd_cookie, JSON_THROW_ON_ERROR), ['expires' => 0, 'path' => '/']);
                    } else {
                        self::serveDocument('password-form.html', 'text/html', false);

                        return;
                    }
                }

                

                $tplset           = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'tplset');
                $default_template = Path::real(App::plugins()->moduleInfo('related', 'root')) . DIRECTORY_SEPARATOR . Utility::TPL_ROOT . DIRECTORY_SEPARATOR;
                if (!empty($tplset) && is_dir($default_template . $tplset)) {
                    App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . $tplset);
                } else {
                    App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . App::config()->defaultTplset());
                }
                self::serveDocument('external.html');
            }
        }
    }

    /**
     * Output the Page preview page.
     *
     * @param   null|string     $args   The arguments
     */
    public static function relatedpreview(?string $args): void
    {
        if (!preg_match('#^(.+?)/([0-9a-z]{40})/(.+?)$#', (string) $args, $m)) {
            # The specified Preview URL is malformed.
            self::p404();
        } else {
            $user_id  = $m[1];
            $user_key = $m[2];
            $post_url = $m[3];
            if (!App::auth()->checkUser($user_id, null, $user_key)) {
                # The user has no access to the entry.
                self::p404();
            } else {
                App::frontend()->context()->preview = true;
                if (App::config()->adminUrl() !== '') {
                    App::frontend()->context()->xframeoption = App::config()->adminUrl();
                }

                self::related($post_url);
            }
        }
    }
}
