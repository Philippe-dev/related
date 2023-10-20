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

use dcCore;
use dcUrlHandlers;

class UrlHandler extends dcUrlHandlers
{
    public static function related($args)
    {
        if ($args == '') {
            self::p404();
        }

        dcCore::app()->blog->withoutPassword(false);

        $params['post_url'] = $args;
        $params['post_type'] = 'related';
        dcCore::app()->ctx->posts = dcCore::app()->blog->getPosts($params);
        dcCore::app()->ctx->posts->extend(RsRelated::class);

        dcCore::app()->blog->withoutPassword(true);

        if (dcCore::app()->ctx->posts->isEmpty()) {
            // No entry
            self::p404();
        }

        $post_id = dcCore::app()->ctx->posts->post_id;
        $post_password = dcCore::app()->ctx->posts->post_password;

        // Password protected entry
        if ($post_password != '' && !dcCore::app()->ctx->preview) {
            // Get passwords cookie
            if (isset($_COOKIE['dc_passwd'])) {
                $pwd_cookie = unserialize($_COOKIE['dc_passwd']);
            } else {
                $pwd_cookie = [];
            }

            // Check for match
            if ((!empty($_POST['password']) && $_POST['password'] === $post_password)
                || (isset($pwd_cookie[$post_id]) && $pwd_cookie[$post_id] === $post_password)) {
                $pwd_cookie[$post_id] = $post_password;
                setcookie('dc_passwd', serialize($pwd_cookie), 0, '/');
            } else {
                self::serveDocument('password-form.html', 'text/html', false);
                exit;
            }
        }

        if ($filename = dcCore::app()->ctx->posts->getRelatedFilename()) {
            $GLOBALS['mod_files'][] = $filename;
        }

        self::serveDocument('external.html');
    }

    public static function relatedpreview($args)
    {
        if (!preg_match('#^(.+?)/([0-9a-z]{40})/(.+?)$#', $args, $m)) {
            // The specified Preview URL is malformed.
            self::p404();
        } else {
            $user_id = $m[1];
            $user_key = $m[2];
            $post_url = $m[3];
            if (!dcCore::app()->auth->checkUser($user_id, null, $user_key)) {
                // The user has no access to the entry.
                self::p404();
            } else {
                dcCore::app()->ctx->preview = true;
                self::related($post_url);
            }
        }
    }
}
