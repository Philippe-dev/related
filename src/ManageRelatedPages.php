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

use Exception;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Dotclear\App;
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

        App::backend()->related_filter = new FilterPages();

        $params               = App::backend()->related_filter->params();
        $params['post_type']  = 'related';
        $params['no_content'] = true;

        App::backend()->related_list = null;

        try {
            self::$pages = App::blog()->getPosts($params);
            self::$pages->extend(RsRelated::class);
            $counter                     = App::blog()->getPosts($params, true);
            App::backend()->related_list = new ListingRelatedPages(self::$pages, $counter->f(0));
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        App::backend()->related_actions = new ActionsRelatedPages(My::manageUrl(['part' => 'pages']));

        App::backend()->related_actions_rendered = null;
        if (App::backend()->related_actions->process()) {
            App::backend()->related_actions_rendered = true;
        }

        if (isset($_POST['pages_upd'])) {
            $public_pages = PagesHelper::getPublicList(self::$pages);
            $visible      = (!empty($_POST['p_visibles']) && is_array($_POST['p_visibles'])) ? $_POST['p_visibles'] : [];
            $order        = (!empty($_POST['p_order'])) ? $_POST['p_order'] : [];

            try {
                foreach ($public_pages as $c_page) {
                    $cur                = App::con()->openCursor(App::con()->prefix() . 'post');
                    $cur->post_upddt    = date('Y-m-d H:i:s');
                    $cur->post_selected = (int) in_array($c_page['id'], $visible);
                    $cur->update('WHERE post_id = ' . $c_page['id']);

                    if (count($order) > 0) {
                        $pos = !empty($order[$c_page['id']]) ? $order[$c_page['id']] + 1 : 1;
                        $pos = (int) $pos                                            + 1;
                        App::meta()->delPostMeta($c_page['id'], 'related_position');
                        App::meta()->setPostMeta($c_page['id'], 'related_position', (string) $pos);
                    }
                }
                App::blog()->triggerBlog();
                Notices::addSuccessNotice(__('Pages list has been sorted.'));
                My::redirect([], '#pages_order');
            } catch (Exception $e) {
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

        if (App::backend()->related_actions_rendered) {
            App::backend()->related_actions->render();

            return;
        }

        Page::openModule(
            __('Related pages'),
            Page::jsLoad('js/jquery/jquery-ui.custom.js') .
            Page::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
            My::jsLoad('_pages.js') .
            Page::jsLoad('js/_posts_list.js') .
            App::backend()->related_filter->js(App::backend()->url()->get('admin.plugin') . '&p=' . My::id()) .
            Page::jsPageTabs(self::$default_tab)
        );

        echo Page::breadcrumb([Html::escapeHTML(App::blog()->name())                               => '',
            '<a href="' . My::manageUrl(['part' => 'pages']) . '">' . __('Related pages') . '</a>' => '',
        ]);

        echo Notices::getNotices();

        echo '<div class="multi-part" id="pages_compose" title="', __('Manage pages'), '">';

        if (!App::error()->flag()) {
            echo '<p class="top-add">';
            echo '<a class="button add" href="', My::manageUrl(['part' => 'page', 'type' => 'post']), '">', __('New post as page'), '</a>&nbsp;';
            echo '<a class="button add" href="', My::manageUrl(['part' => 'page', 'type' => 'file']), '">', __('New included page'), '</a>';
            echo '</p>';

            App::backend()->related_filter->display('admin.plugin.' . My::id());

            App::backend()->related_list->display(
                App::backend()->related_filter->page,
                App::backend()->related_filter->nb,
                '<form action="' . My::manageUrl() . '" method="post" id="form-entries">' .
                '%s' .
                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .
                '<p class="col right">' .
                '<label for="action" class="classic">' . __('Selected entries action:') . '</label>' .
                form::combo('action', App::backend()->related_actions->getCombo()) .
                '<input id="do-action" type="submit" value="' . __('ok') . '">' .
                App::backend()->url()->getHiddenFormFields('admin.plugin.' . My::id(), App::backend()->related_filter->values()) .
                App::nonce()->getFormNonce() .
                '</p></div>' .
                '</form>',
                App::backend()->related_filter->show()
            );
        }

        echo '</div>';

        echo '<div class="multi-part" id="pages_order" title="', __('Arrange public list'), '">';
        $public_pages = PagesHelper::getPublicList(self::$pages);

        if (count((array) $public_pages) > 0) {
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
                echo '<tr class="line', $page['active'] ? '' : ' offline', '" id="p_', $page['id'], '">';
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
            echo form::hidden(['public_order'], '');
            echo '<input type="submit" name="pages_upd" value="', __('Save'), '">' .
            App::nonce()->getFormNonce() ;
            echo '</p>';
            echo '<p class="col checkboxes-helpers"></p>';
            echo '</form>';
        } else {
            echo '<p><strong>', __('No page'), '</strong></p>';
        }
        echo '</div>';

        Page::helpBlock('related_pages');
        Page::closeModule();
    }
}
