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
                'id'     => $rs->post_id,
                'title'  => $rs->post_title,
                'url'    => $rs->getURL(),
                'active' => $rs->post_selected,
                'order'  => $pos,
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
