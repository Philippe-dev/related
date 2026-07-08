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
use Dotclear\Helper\File\Path;

/**
 * @brief   The module frontend URL.
 * @ingroup pages
 */
class FrontendUrl
{
    /**
     * Output the related page
     *
     * @param   null|string     $args   The arguments
     */
    public static function related(?string $args): void
    {
        if (!$args) {
            // No page was specified.
            App::url()->p404();
        } else {
            App::blog()->withoutPassword(false);

            $params = new ArrayObject([
                'post_type' => 'related',
                'post_url'  => $args,
            ]);

            # --BEHAVIOR-- publicPagesBeforeGetPosts -- ArrayObject, string
            App::behavior()->callBehavior('publicPagesBeforeGetPosts', $params, $args);

            App::frontend()->context()->posts = App::blog()->getPosts($params->getArrayCopy());

            App::blog()->withoutPassword(true);

            if (App::frontend()->context()->posts->isEmpty()) {
                # The specified page does not exist.
                App::url()->p404();
            } else {
                $post_id       = is_numeric($post_id = App::frontend()->context()->posts->post_id) ? (int) $post_id : 0;
                $post_password = is_string($post_password = App::frontend()->context()->posts->post_password) ? $post_password : '';

                # Password protected entry
                if ($post_password !== '' && !App::frontend()->context()->preview) {
                    # Get passwords cookie
                    if (isset($_COOKIE['dc_passwd']) && is_string($_COOKIE['dc_passwd'])) {
                        $pwd_cookie = json_decode($_COOKIE['dc_passwd'], null, 512, JSON_THROW_ON_ERROR);
                        $pwd_cookie = $pwd_cookie === null ? [] : (array) $pwd_cookie;
                    } else {
                        $pwd_cookie = [];
                    }

                    # Check for match
                    # Note: We must prefix post_id key with '#'' in pwd_cookie array in order to avoid integer conversion
                    # because MyArray["12345"] is treated as MyArray[12345]
                    if ((!empty($_POST['password']) && $_POST['password'] === $post_password)
                        || (isset($pwd_cookie['#' . $post_id]) && $pwd_cookie['#' . $post_id] === $post_password)) {
                        $pwd_cookie['#' . $post_id] = $post_password;
                        setcookie('dc_passwd', json_encode($pwd_cookie, JSON_THROW_ON_ERROR), ['expires' => 0, 'path' => '/']);
                    } else {
                        App::url()::serveDocument('password-form.html', 'text/html', false);

                        return;
                    }
                }

                $theme  = App::blog()->settings()->get('system')->getStr('theme', false);
                $tplset = is_string($tplset = App::themes()->moduleInfo($theme, 'tplset')) ? $tplset : '';
                $root   = is_string($root = App::plugins()->moduleInfo('related', 'root')) ? $root : '';

                $default_template = Path::real($root) . DIRECTORY_SEPARATOR . Utility::TPL_ROOT . DIRECTORY_SEPARATOR;
                if ($tplset !== '' && is_dir($default_template . $tplset)) {
                    App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . $tplset);
                } else {
                    App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . App::config()->defaultTplset());
                }

                App::url()::serveDocument('external.html');
            }
        }
    }

    /**
     * Output the related page preview
     *
     * @param   null|string     $args   The arguments
     */
    public static function relatedpreview(?string $args): void
    {
        if (!preg_match('#^(.+?)/([0-9a-z]{40})/(.+?)$#', (string) $args, $m)) {
            # The specified Preview URL is malformed.
            App::url()->p404();
        } else {
            $user_id  = $m[1];
            $user_key = $m[2];
            $post_url = $m[3];
            if (!App::auth()->checkUser($user_id, null, $user_key)) {
                # The user has no access to the entry.
                App::url()->p404();
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
