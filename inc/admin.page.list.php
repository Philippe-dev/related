<?php

# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of Related, a plugin for DotClear2.
#
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) {
    return;
}

class adminPageList extends adminGenericList
{
    public function display(int $page, int $nb_per_page, string $enclose_block = ''): void
    {
        if ($this->rs->isEmpty()) {
            echo '<p><strong>'.__('No page').'</strong></p>';
        } else {
            $pager = new dcPager($page, $this->rs_count, $nb_per_page, 10);
            $pager->var_page = 'page';

            $html_block = '<div class="table-outer">'.
                '<table class="maximal"><thead><tr>'.
                '<th colspan="2">'.__('Title').'</th>'.
                '<th>'.__('Date').'</th>'.
                '<th>'.__('Author').'</th>'.
                '<th>'.__('Type').'</th>'.
                '<th>'.__('Status').'</th>'.
                '</tr></thead><tbody id="pageslist">%s</tbody></table></div>';

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
            echo $pager->getLinks();
        }
    }

    private function pageLine($count)
    {
        $img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
        switch ($this->rs->post_status) {
            case  1: $img_status = sprintf($img, __('published'), 'check-on.png');
            break;
            case  0: $img_status = sprintf($img, __('unpublished'), 'check-off.png');
            break;
            case -2: $img_status = sprintf($img, __('pending'), 'check-wrn.png');
            break;
        }

        $protected = '';
        if ($this->rs->post_password) {
            $protected = sprintf($img, __('protected'), 'locker.png');
        }

        $subtype = '(N/A)';
        $meta = new dcMeta();
        $meta_rs = $meta->getMetaRecordset($this->rs->post_meta, 'related_file');
        $subtype = (!$meta_rs->isEmpty()) ? __('included page') : __('post as page');

        $res = '<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
            ' id="p'.$this->rs->post_id.'">';

        $res .=
            '<td class="nowrap minimal">'.
            form::checkbox(array('entries[]'), $this->rs->post_id, '', '', '', !$this->rs->isEditable()).'</td>'.
            '<td class="maximal"><a href="plugin.php?p=related&amp;do=edit&amp;id='.$this->rs->post_id.'">'.
            html::escapeHTML($this->rs->post_title).'</a></td>'.
            '<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt).'</td>'.
            '<td class="nowrap">'.$this->rs->user_id.'</td>'.
            '<td class="nowrap">'.$subtype.'</td>'.
            '<td class="nowrap status">'.$img_status.' '.$protected.'</td>'.
            '</tr>';

        return $res;
    }
}
