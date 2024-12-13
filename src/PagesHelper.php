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

namespace Dotclear\Plugin\related;

use Dotclear\Core\Blog;
use Dotclear\Database\MetaRecord;

class PagesHelper
{
    public static function getPublicList(MetaRecord $rs)
    {
        if ($rs->isEmpty()) {
            return;
        }

        /** @var array $res */
        $res = [];
        while ($rs->fetch()) {
            if ($rs->post_status != Blog::POST_PUBLISHED) {
                continue;
            }

            if (is_null($pos = $rs->getPosition())) {
                continue;
            }

            if ($pos <= 0) {
                $pos = 10000;
            }

            $res[] = [
                'id' => $rs->post_id,
                'title' => $rs->post_title,
                'url' => $rs->getURL(),
                'active' => $rs->post_selected,
                'order' => $pos
            ];
        }
        usort($res, self::orderCallBack(...));

        return $res;
    }

    protected static function orderCallBack($a, $b): int
    {
        return $a['order'] <=> $b['order'];
    }
}
