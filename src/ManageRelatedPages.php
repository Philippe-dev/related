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

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use dcCore;
use dcMeta;
use form;

class ManageRelatedPages extends Process
{
    private static string $default_tab = 'pages_compose';

    private static MetaRecord $pages;

    public static function init(): bool
    {
        if (My::checkContext(My::MANAGE)) {
            self::status(empty($_REQUEST['part']) || $_REQUEST['part'] === 'pages');
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->admin->related_filter = new FilterPages();

        $params = dcCore::app()->admin->related_filter->params();
        $params['post_type'] = 'related';
        $params['no_content'] = true;

        dcCore::app()->admin->related_list = null;
        try {
            self::$pages = dcCore::app()->blog->getPosts($params);
            self::$pages->extend(RsRelated::class);
            $counter = dcCore::app()->blog->getPosts($params, true);
            dcCore::app()->admin->related_list = new ListingRelatedPages(self::$pages, $counter->f(0));
        } catch (\Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        dcCore::app()->admin->related_actions = new ActionsRelatedPages(My::manageUrl(['part' => 'pages']));

        dcCore::app()->admin->related_actions_rendered = null;
        if (dcCore::app()->admin->related_actions->process()) {
            dcCore::app()->admin->related_actions_rendered = true;
        }

        if (isset($_POST['pages_upd'])) {
            $public_pages = PagesHelper::getPublicList(self::$pages);
            $visible = (!empty($_POST['p_visibles']) && is_array($_POST['p_visibles'])) ? $_POST['p_visibles'] : [];
            $order = (!empty($_POST['p_order'])) ? $_POST['p_order'] : [];

            try {
                $meta = new dcMeta();
                foreach ($public_pages as $c_page) {
                    $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . 'post');
                    $cur->post_upddt = date('Y-m-d H:i:s');
                    $cur->post_selected = (int)in_array($c_page['id'], $visible);
                    $cur->update('WHERE post_id = ' . $c_page['id']);


                    if (count($order) > 0) {
                        $pos = !empty($order[$c_page['id']]) ? $order[$c_page['id']] + 1 : 1;
                        $pos = (int) $pos + 1;
                        $meta->delPostMeta($c_page['id'], 'related_position');
                        $meta->setPostMeta($c_page['id'], 'related_position', (string) $pos);
                    }
                }
                dcCore::app()->blog->triggerBlog();
                Notices::addSuccessNotice(__('Pages list has been sorted.'));
                My::redirect([], '#pages_order');
            } catch (\Exception $e) {
                Notices::addErrorNotice($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        if (dcCore::app()->admin->related_actions_rendered) {
            dcCore::app()->admin->related_actions->render();

            return;
        }

        Page::openModule(
            __('Related pages'),
            Page::jsLoad('js/jquery/jquery-ui.custom.js') .
            Page::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
            My::jsLoad('_pages.js') .
            Page::jsLoad('js/_posts_list.js') .
            dcCore::app()->admin->related_filter->js(My::manageUrl()) .
            Page::jsPageTabs(self::$default_tab)
        );

        echo Page::breadcrumb([Html::escapeHTML(dcCore::app()->blog->name) => '',
            '<a href="' . My::manageUrl(['part' => 'pages']) . '">' . __('Related pages') . '</a>' => ''
        ]);

        echo Notices::getNotices();

        echo '<div class="multi-part" id="pages_compose" title="', __('Manage pages'), '">';

        if (!dcCore::app()->error->flag()) {
            echo '<p class="top-add">';
            echo '<a class="button add" href="', My::manageUrl(['part' => 'page', 'type' => 'post']), '">', __('New post as page'), '</a>&nbsp;';
            echo '<a class="button add" href="', My::manageUrl(['part' => 'page', 'type' => 'file']), '">', __('New included page'), '</a>';
            echo '</p>';

            dcCore::app()->admin->related_filter->display('admin.plugin.' . My::id());

            dcCore::app()->admin->related_list->display(
                dcCore::app()->admin->related_filter->page,
                dcCore::app()->admin->related_filter->nb,
                '<form action="' . My::manageUrl() . '" method="post" id="form-entries">' .
                '%s' .
                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .
                '<p class="col right">' .
                '<label for="action" class="classic">' . __('Selected entries action:') . '</label>' .
                form::combo('action', dcCore::app()->admin->related_actions->getCombo()) .
                '<input id="do-action" type="submit" value="' . __('ok') . '" />' .
                dcCore::app()->admin->url->getHiddenFormFields('admin.plugin.' . My::id(), dcCore::app()->admin->related_filter->values()) .
                dcCore::app()->formNonce() .
                '</p></div>' .
                '</form>'
            );
        }

        echo '</div>';

        echo '<div class="multi-part" id="pages_order" title="', __('Arrange public list'), '">';
        $public_pages = PagesHelper::getPublicList(self::$pages);

        if (count($public_pages) > 0) {
            echo '<form action="', My::manageUrl(), '" method="post" id="form-public-pages">';
            echo '<table class="dragable">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>', __('Order'), '</th>';
            echo '<th class="nowrap">', __('Visible page in widget'), '</th>';
            echo '<th class="nowrap maximal">',  __('Page title'), '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody id="pages-list">';
            $i = 1;
            foreach ($public_pages as $page) {
                echo '<tr class="line', $page['active']? '' : ' offline', '" id="p_', $page['id'], '">';
                echo '<td class="handle">';
                echo form::field(['p_order[' . $page['id'] . ']'], 2, 5, (string) $i, 'position');
                echo '</td>';
                echo '<td class="nowrap">';
                echo form::checkbox(['p_visibles[]'], $page['id'], $page['active']);
                echo '</td>';
                echo '<td class="nowrap">';
                echo $page['title'];
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo '<p>';
            echo form::hidden(['public_order'], '') . dcCore::app()->formNonce();
            echo '<input type="submit" name="pages_upd" value="', __('Save'), '" />';
            echo '</p>';
            echo '<p class="col checkboxes-helpers"></p>';
            echo '</form>';
        } else {
            echo '<p><strong>', __('No page'), '</strong></p>';
        }
        echo '</div>';


        Page::helpBlock(My::id());
        Page::closeModule();
    }
}
