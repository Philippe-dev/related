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

class rsRelatedBase
{
    public static function getRelatedFilename($rs)
    {
        if (dcCore::app()->blog->settings->related->files_path === null) {
            return false;
        }

        $meta = new dcMeta(dcCore::app());
        $meta_rs = $meta->getMetaRecordset($rs->post_meta, 'related_file');

        if (!$meta_rs->isEmpty()) {
            $filename = dcCore::app()->blog->settings->related->files_path . '/' . $meta_rs->meta_id;
            if (is_readable($filename)) {
                return $filename;
            } else {
                return false;
            }
        }

        return false;
    }

    public static function getPosition($rs)
    {
        if (dcCore::app()->blog->settings->related->files_path === null) {
            return false;
        }

        $meta = new dcMeta(dcCore::app());
        $meta_rs = $meta->getMetaRecordset($rs->post_meta, 'related_position');

        if (!$meta_rs->isEmpty()) {
            return (int)$meta_rs->meta_id;
        }

        return -1;
    }
}
