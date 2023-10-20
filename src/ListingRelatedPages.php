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

use Dotclear\Core\Backend\Listing\Listing;
use Dotclear\Helper\Html\Html;
use Dotclear\Core\Backend\Listing\Pager;
use Dotclear\Helper\Date;
use dcBlog;
use dcMeta;
use form;

class ListingRelatedPages extends Listing
{
    public function display(int $page, int $nb_per_page, string $enclose_block = '')
    {
        if ($this->rs->isEmpty()) {
            echo '<p><strong>' . __('No page') . '</strong></p>';
        } else {
            $pager = new Pager($page, $this->rs_count, $nb_per_page, 10);
            $pager->var_page = 'page';

            $columns = [
                '<th colspan="2">' . __('Title') . '</th>',
                '<th>' . __('Date') . '</th>',
                '<th>' . __('Author') . '</th>',
                '<th>' . __('Type') . '</th>' .
                '<th>' . __('Status') . '</th>'
            ];

            $html_block = '<table class="clear"><tr>' .
            join('', $columns) .
            '</tr>%s</table>';

            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }

            echo $pager->getLinks();
            $blocks = explode('%s', $html_block);
            echo $blocks[0];

            $count = 0;
            while ($this->rs->fetch()) {
                echo $this->pageLine($count);
                $count++;
            }
            echo $blocks[1];

            $fmt = fn ($title, $image) => sprintf('<img alt="%1$s" title="%1$s" src="images/%2$s" /> %1$s', $title, $image);
            echo '<p class="info">' . __('Legend: ') .
                $fmt(__('Published'), 'check-on.png') . ' - ' .
                $fmt(__('Unpublished'), 'check-off.png') . ' - ' .
                $fmt(__('Scheduled'), 'scheduled.png') . ' - ' .
                $fmt(__('Pending'), 'check-wrn.png') . ' - ' .
                $fmt(__('Protected'), 'locker.png') . ' - ' .
                $fmt(__('Selected'), 'selected.png') . ' - ' .
                $fmt(__('Attachments'), 'attach.png') .
                '</p>';

            echo $pager->getLinks();
        }
    }

    private function pageLine($count)
    {
        $img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
        switch ($this->rs->post_status) {
            case dcBlog::POST_PUBLISHED :
                $img_status = sprintf($img, __('published'), 'check-on.png');
                break;
            case dcBlog::POST_UNPUBLISHED :
                $img_status = sprintf($img, __('unpublished'), 'check-off.png');
                break;
            case dcBlog::POST_PENDING :
                $img_status = sprintf($img, __('pending'), 'check-wrn.png');
                break;
            case dcBlog::POST_SCHEDULED : $img_status = sprintf($img, __('scheduled'), 'scheduled.png');
                break;
            default:
                $img_status = sprintf($img, __('unpublished'), 'check-off.png');
        }

        $protected = '';
        if ($this->rs->post_password) {
            $protected = sprintf($img, __('protected'), 'locker.png');
        }

        $selected = '';
        if ($this->rs->post_selected) {
            $selected = sprintf($img, __('In widget'), 'selected.png');
        }

        $subtype = '(N/A)';
        $meta = new dcMeta();
        $meta_rs = $meta->getMetaRecordset($this->rs->post_meta, 'related_file');
        $subtype = (!$meta_rs->isEmpty())?__('included page'):__('post as page');

        $res = '<tr class="line' . ($this->rs->post_status != dcBlog::POST_PUBLISHED ? ' offline' : '') . '"' .
            ' id="p' . $this->rs->post_id . '">';

        $res .=
            '<td class="nowrap minimal">' .
            form::checkbox(['entries[]'], $this->rs->post_id, '', '', '', !$this->rs->isEditable()) . '</td>' .
            '<td class="maximal"><a href="' . My::manageUrl(['part' => 'page', 'id' => $this->rs->post_id]) . '">' .
            Html::escapeHTML($this->rs->post_title) . '</a></td>' .
            '<td class="nowrap">' . Date::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt) . '</td>' .
            '<td class="nowrap">' . $this->rs->user_id . '</td>' .
            '<td class="nowrap">' . $subtype . '</td>' .
            '<td class="nowrap status">' . $img_status . ' ' . $selected . ' ' . $protected . '</td>' .
            '</tr>';

        return $res;
    }
}
