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

namespace Dotclear\Plugin\related;

use Dotclear\Core\Auth;
use Dotclear\Plugin\pages\Pages;
use Dotclear\App;

class RsRelated
{
    public static function isEditable($rs): bool
    {
        if (App::auth()->check(App::auth()->makePermissions([Auth::PERMISSION_CONTENT_ADMIN]), App::blog()->id())) {
            return true;
        }

        if (!$rs->exists('user_id')) {
            return false;
        }

        if (App::auth()->check(App::auth()->makePermissions([Pages::PERMISSION_PAGES]), App::blog()->id())
            && $rs->user_id == App::auth()->userID()) {
            return true;
        }

        return false;
    }

    public static function isDeletable($rs): bool
    {
        if (App::auth()->check(App::auth()->makePermissions([Auth::PERMISSION_CONTENT_ADMIN]), App::blog()->id())) {
            return true;
        }

        if (!$rs->exists('user_id')) {
            return false;
        }

        if (App::auth()->check(App::auth()->makePermissions([Pages::PERMISSION_PAGES]), App::blog()->id())
            && $rs->user_id == App::auth()->userID()) {
            return true;
        }

        return false;
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

    public static function getPosition($rs)
    {
        if (is_null(App::blog()->settings()->related->files_path)) {
            return false;
        }

        $meta_rs = App::meta()->getMetaRecordset($rs->post_meta, 'related_position');

        if (!$meta_rs->isEmpty()) {
            return (int) $meta_rs->meta_id;
        }

        return -1;
    }
}
