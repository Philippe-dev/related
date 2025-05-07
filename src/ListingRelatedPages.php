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
use Dotclear\Core\Blog;
use Dotclear\Core\Backend\Listing\Listing;
use Dotclear\Helper\Html\Html;
use Dotclear\Core\Backend\Listing\Pager;
use Dotclear\Helper\Date;
use form;

class ListingRelatedPages extends Listing
{
    public function display(int $page, ?int $nb_per_page, string $enclose_block = '')
    {
        if ($this->rs->isEmpty()) {
            echo '<p><strong>' . __('No page') . '</strong></p>';
        } else {
            $pager           = new Pager($page, $this->rs_count, $nb_per_page, 10);
            $pager->var_page = 'page';

            $columns = [
                '<th colspan="2">' . __('Title') . '</th>',
                '<th>' . __('Date') . '</th>',
                '<th>' . __('Author') . '</th>',
                '<th>' . __('Type') . '</th>' .
                '<th>' . __('Status') . '</th>',
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

            $fmt = fn ($title, $image, $class) => sprintf('<img alt="%1$s" src="images/%2$s" class="mark mark-%3$s"> %1$s', $title, $image, $class);
            echo '<p class="info">' . __('Legend: ') .
                $fmt(__('Published'), 'published.svg', 'published') . ' - ' .
                $fmt(__('Unpublished'), 'unpublished.svg', 'unpublished') . ' - ' .
                $fmt(__('Scheduled'), 'scheduled.svg', 'scheduled') . ' - ' .
                $fmt(__('Pending'), 'pending.svg', 'pending') . ' - ' .
                $fmt(__('Protected'), 'locker.svg', 'locked') . ' - ' .
                $fmt(__('In widget'), 'selected.svg', 'selected') .
                '</p>';

            echo $pager->getLinks();
        }
    }

    private function pageLine($count)
    {
        $img        = '<img alt="%1$s" title="%1$s" src="images/%2$s" class="mark mark-%3$s">';
        $img_status = '';
        $sts_class  = '';
        switch ($this->rs->post_status) {
            case App::blog()::POST_PUBLISHED:
                $img_status = sprintf($img, __('Published'), 'check-on.svg', 'published');
                $sts_class  = 'sts-online';

                break;
            case App::blog()::POST_UNPUBLISHED:
                $img_status = sprintf($img, __('Unpublished'), 'check-off.svg', 'unpublished');
                $sts_class  = 'sts-offline';

                break;
            case App::blog()::POST_SCHEDULED:
                $img_status = sprintf($img, __('Scheduled'), 'scheduled.svg', 'scheduled');
                $sts_class  = 'sts-scheduled';

                break;
            case App::blog()::POST_PENDING:
                $img_status = sprintf($img, __('Pending'), 'check-wrn.svg', 'pending');
                $sts_class  = 'sts-pending';

                break;
        }

        $protected = '';
        if ($this->rs->post_password) {
            $protected = sprintf($img, __('Protected'), 'locker.svg', 'locked');
        }

        $selected = '';
        if ($this->rs->post_selected) {
            $selected = sprintf($img, __('In widget'), 'selected.svg', 'selected');
        }

        $subtype = '(N/A)';
        $meta_rs = App::meta()->getMetaRecordset($this->rs->post_meta, 'related_file');
        $subtype = (!$meta_rs->isEmpty()) ? __('included page') : __('post as page');

        $res = '<tr class="line' . ($this->rs->post_status != Blog::POST_PUBLISHED ? ' offline' : '') . '"' .
            ' id="p' . $this->rs->post_id . '">';

        $res .= '<td class="nowrap minimal">' .
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
