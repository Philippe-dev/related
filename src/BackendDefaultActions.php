<?php
/**
 * @brief related, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Pep, Nicolas Roudaire and contributors
 *
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\related;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Action\ActionsPostsDefault;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Schema\Extension\User;
use Exception;

/**
 * @brief   The module backend default pages actions.
 * @ingroup pages
 */
class BackendDefaultActions
{
    /**
     * Set pages actions.
     *
     * @param   BackendActions  $ap     Admin actions instance
     */
    public static function adminRelatedPagesActionsPage(BackendActions $ap): void
    {
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('Status') => App::status()->post()->action()],
                ActionsPostsDefault::doChangePostStatus(...)
            );
        }
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('First publication') => [
                    __('Never published')   => 'never',
                    __('Already published') => 'already',
                ]],
                ActionsPostsDefault::doChangePostFirstPub(...)
            );
        }
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('Change') => [
                    __('Change author') => 'author', ]],
                self::doChangePostAuthor(...)
            );
        }

        $ap->addAction(
            [__('Change') => [
                __('Change language') => 'lang',
            ]],
            self::doChangePostLang(...)
        );

        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('Delete') => [
                    __('Delete') => 'delete', ]],
                ActionsPostsDefault::doDeletePost(...)
            );
        }

        $ap->addAction(
            [__('Order') => [
                __('Save order') => 'reorder', ]],
            self::doReorderPages(...)
        );

        $ap->addAction(
            [__('Widget') => [
                __('Add to widget')      => 'selected',
                __('Remove from widget') => 'unselected',
            ]],
            self::doUpdateSelectedPost(...)
        );
    }

    /**
     * Does reorder pages.
     *
     * @param   BackendActions                  $ap     Admin actions instance
     * @param   ArrayObject<string, mixed>      $post   The post
     *
     * @throws  Exception   If user permission not granted
     */
    public static function doReorderPages(BackendActions $ap, ArrayObject $post): void
    {
        foreach ($post['order'] as $post_id => $value) {
            if (!App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_PUBLISH,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id())) {
                throw new Exception(__('You are not allowed to change this entry status'));
            }

            $cur                = App::blog()->openPostCursor();
            $cur->post_position = (int) $value - 1;
            $cur->post_upddt    = date('Y-m-d H:i:s');

            $sql = new UpdateStatement();
            $sql
                ->where('blog_id = ' . $sql->quote(App::blog()->id()))
                ->and('post_id ' . $sql->in($post_id));

            #If user can only publish, we need to check the post's owner
            if (!App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id())) {
                $sql->and('user_id = ' . $sql->quote((string) App::auth()->userID()));
            }

            $sql->update($cur);

            App::blog()->triggerBlog();
        }

        Notices::addSuccessNotice(__('Pages have been successfully reordered.'));
        $ap->redirect(false);
    }

    /**
     * Does a change post author.
     *
     * @param   ActionsPosts                    $ap     The ActionsPosts instance
     * @param   ArrayObject<string, mixed>      $post   The parameters ($_POST)
     *
     * @throws  Exception   If no entry selected
     */
    public static function doChangePostAuthor(BackendActions $ap, ArrayObject $post): void
    {
        if (isset($post['new_auth_id']) && App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]), App::blog()->id())) {
            $new_user_id = $post['new_auth_id'];
            $ids         = $ap->getIDs();
            if ($ids === []) {
                throw new Exception(__('No entry selected'));
            }
            if (App::users()->getUser($new_user_id)->isEmpty()) {
                throw new Exception(__('This user does not exist'));
            }

            $cur          = App::blog()->openPostCursor();
            $cur->user_id = $new_user_id;

            $sql = new UpdateStatement();
            $sql
                ->where('post_id ' . $sql->in($ids))
                ->update($cur);

            Notices::addSuccessNotice(
                sprintf(
                    __(
                        '%d entry has been successfully set to user "%s"',
                        '%d entries have been successfully set to user "%s"',
                        count($ids)
                    ),
                    count($ids),
                    Html::escapeHTML($new_user_id)
                )
            );

            $ap->redirect(true);
        } else {
            $usersList = [];
            if (App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_ADMIN,
            ]), App::blog()->id())) {
                $params = [
                    'limit' => 100,
                    'order' => 'nb_post DESC',
                ];
                $rs       = App::users()->getUsers($params);
                $rsStatic = $rs->toStatic();
                $rsStatic->extend(User::class);
                $rsStatic = $rsStatic->toExtStatic();
                $rsStatic->lexicalSort('user_id');
                while ($rsStatic->fetch()) {
                    $usersList[] = $rsStatic->user_id;
                }
            }
            $ap->beginPage(
                Page::breadcrumb(
                    [
                        Html::escapeHTML(App::blog()->name())  => '',
                        $ap->getCallerTitle()                  => $ap->getRedirection(true),
                        __('Change author for this selection') => '', ]
                ),
                Page::jsLoad('js/jquery/jquery.autocomplete.js') .
                Page::jsJson('users_list', $usersList)
            );

            echo (new Form('dochangepostauthor'))
                ->method('post')
                ->action($ap->getURI())
                ->fields([
                    $ap->checkboxes(),
                    (new Para())
                        ->items([
                            (new Label(__('New author (author ID):'), Label::OUTSIDE_LABEL_BEFORE))
                                ->for('new_auth_id'),
                            (new Input('new_auth_id'))
                                ->size(20)
                                ->maxlength(255)
                                ->value(''),
                        ]),
                    (new Para())
                        ->items([
                            App::nonce()->formNonce(),
                            ...$ap->hiddenFields(),
                            (new Hidden('action', 'author')),
                            (new Submit('save'))
                                ->value(__('Save')),

                        ]),
                ])
                ->render();

            $ap->endPage();
        }
    }

    /**
     * Does a change post language.
     *
     * @param   ActionsPosts                    $ap     The ActionsPosts instance
     * @param   ArrayObject<string, mixed>      $post   The parameters ($_POST)
     *
     * @throws  Exception   If no entry selected
     */
    public static function doChangePostLang(BackendActions $ap, ArrayObject $post): void
    {
        $ids = $ap->getIDs();
        if ($ids === []) {
            throw new Exception(__('No entry selected'));
        }
        if (isset($post['new_lang'])) {
            $new_lang       = $post['new_lang'];
            $cur            = App::blog()->openPostCursor();
            $cur->post_lang = $new_lang;

            $sql = new UpdateStatement();
            $sql
                ->where('post_id ' . $sql->in($ids))
                ->update($cur);

            Notices::addSuccessNotice(
                sprintf(
                    __(
                        '%d entry has been successfully set to language "%s"',
                        '%d entries have been successfully set to language "%s"',
                        count($ids)
                    ),
                    count($ids),
                    Html::escapeHTML(L10n::getLanguageName($new_lang))
                )
            );
            $ap->redirect(true);
        } else {
            $ap->beginPage(
                Page::breadcrumb(
                    [
                        Html::escapeHTML(App::blog()->name())    => '',
                        $ap->getCallerTitle()                    => $ap->getRedirection(true),
                        __('Change language for this selection') => '',
                    ]
                )
            );
            // Prepare languages combo
            $lang_combo = Combos::getLangsCombo(
                App::blog()->getLangs([
                    'order_by' => 'nb_post',
                    'order'    => 'desc',
                ]),
                true    // Also show never used languages
            );

            echo (new Form('dochangepostlang'))
                ->method('post')
                ->action($ap->getURI())
                ->fields([
                    $ap->checkboxes(),
                    (new Para())
                        ->items([
                            (new Label(__('Entry language:'), Label::OUTSIDE_LABEL_BEFORE))
                                ->for('new_lang'),
                            (new Select('new_lang'))
                                ->items($lang_combo)
                                ->default(''),
                        ]),
                    (new Para())
                        ->items([
                            App::nonce()->formNonce(),
                            ...$ap->hiddenFields(),
                            (new Hidden('action', 'lang')),
                            (new Submit('save'))
                                ->value(__('Save')),

                        ]),
                ])
                ->render();

            $ap->endPage();
        }
    }

    /**
     * Does an update selected post.
     *
     * @param   ActionsPosts    $ap     The ActionsPosts instance
     *
     * @throws  Exception
     */
    public static function doUpdateSelectedPost(BackendActions $ap, ArrayObject $post): void
    {
        $ids = $ap->getIDs();
        if ($ids === []) {
            throw new Exception(__('No entry selected'));
        }

        $action = $ap->getAction();
        App::blog()->updPostsSelected($ids, $action === 'selected');
        if ($action == 'selected') {
            Notices::addSuccessNotice(
                sprintf(
                    __(
                        '%d entry has been successfully added to the widget.',
                        '%d entries have been successfully added to the widget.',
                        count($ids)
                    ),
                    count($ids)
                )
            );
        } else {
            Notices::addSuccessNotice(
                sprintf(
                    __(
                        '%d entry has been successfully removed from the widget.',
                        '%d entries have been successfully removed from the widget.',
                        count($ids)
                    ),
                    count($ids)
                )
            );
        }
        $ap->redirect(true);
    }
}
