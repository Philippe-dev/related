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
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

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

        if (isset($_POST['reorder'])) {
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

        $head = '';
        if (!App::auth()->prefs()->accessibility->nodragdrop) {
            $head = Page::jsLoad('js/jquery/jquery-ui.custom.js') .
            Page::jsLoad('js/jquery/jquery.ui.touch-punch.js');
        }

        Page::openModule(
            __('Pages'),
            $head .
            Page::jsJson('pages_list', ['confirm_delete_posts' => __('Are you sure you want to delete selected pages?')]) .
            My::jsLoad('list')
        );

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                My::name()                            => '',
            ]
        ) .
        Notices::getNotices();

        if (!empty($_GET['upd'])) {
            Notices::success(__('Selected pages have been successfully updated.'));
        } elseif (!empty($_GET['del'])) {
            Notices::success(__('Selected pages have been successfully deleted.'));
        } elseif (!empty($_GET['reo'])) {
            Notices::success(__('Selected pages have been successfully reordered.'));
        }

        echo (new Para())
            ->class('new-stuff')
            ->items([
                (new Link())
                    ->class(['button', 'add'])
                    ->href(My::manageUrl(['part' => 'page', 'type' => 'file']))
                    ->text(__('New page')),
            ])
        ->render();

        if (!App::error()->flag() && App::backend()->related_list) {
            
            // Show pages
            App::backend()->related_list->display(
                App::backend()->related_filter->page,
                App::backend()->related_filter->nb,
                (new Form('form-entries'))
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->fields([
                        (new Text(null, '%s')), // List of pages
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Para())->class(['col', 'checkboxes-helpers']),
                                (new Para())
                                    ->class(['col', 'right', 'form-buttons'])
                                    ->items([
                                        (new Select('action'))
                                            ->items(App::backend()->related_actions->getCombo())
                                            ->label((new Label(__('Selected pages action:'), Label::OUTSIDE_TEXT_BEFORE))->class('classic')),
                                        (new Submit('do-action', __('ok'))),
                                    ]),
                            ]),
                        (new Note())
                            ->class(['form-note', 'hidden-if-js', 'clear'])
                            ->text(__('To rearrange pages order, change number at the begining of the line, then click on “Save pages order” button.')),
                        (new Note())
                            ->class(['form-note', 'hidden-if-no-js', 'clear'])
                            ->text(__('To rearrange pages order, move items by drag and drop, then click on “Save pages order” button.')),
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                ...My::hiddenFields(),
                                (new Hidden(['post_type'], 'related')),
                                (new Hidden(['public_order'], '')),
                                (new Submit(['reorder'], __('Save pages order'))),
                                (new Button(['back'], __('Back')))->class(['go-back','reset','hidden-if-no-js']),
                            ]),
                    ])
                ->render()
            );
        }

        Page::helpBlock('related_pages');
        Page::closeModule();
    }
}
