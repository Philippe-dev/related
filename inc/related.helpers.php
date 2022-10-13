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

class relatedHelpers
{
    public static function getPublicList($rs)
    {
        if (!$rs || $rs->isEmpty()) {
            return;
        }

        $res = [];
        while ($rs->fetch()) {
            if ($rs->post_status != dcBlog::POST_PUBLISHED) {
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
        usort($res, ['relatedHelpers', 'orderCallBack']);
        return $res;
    }

    protected static function orderCallBack($a, $b)
    {
        if ($a['order'] == $b['order']) {
            return 0;
        }
        return $a['order'] > $b['order'] ? 1 : -1;
    }
}
