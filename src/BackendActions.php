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

use Dotclear\App;
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\Action\ActionsPostsDefault;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   The module backend pages actions.
 * @ingroup pages
 */
class BackendActions extends ActionsPosts
{
    public const ADD_TO_WIDGET_ACTION      = 'selected';
    public const REMOVE_FROM_WIDGET_ACTION = 'unselected';
    protected bool $use_render             = true;
    
    public function __construct(?string $uri, array $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);

        $this->redirect_fields = ['p', 'part'];
        $this->caller_title    = __('Included pages');

        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $actions = [];
            foreach (App::status()->post()->dump(false) as $status) {
                $actions[__($status->name())] = $status->id();
            }
            $this->addAction(
                [__('Status') => $actions],
                ActionsPostsDefault::doChangePostStatus(...)
            );
        }

        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $this->addAction(
                [__('Delete') => [
                    __('Delete') => 'delete', ]],
                ActionsPostsDefault::doDeletePost(...)
            );
        }

        $this->addAction(
            [__('Widget') => [
                __('Add to widget')      => self::ADD_TO_WIDGET_ACTION,
                __('Remove from widget') => self::REMOVE_FROM_WIDGET_ACTION,
            ]],
            ActionsPostsDefault::doUpdateSelectedPost(...)
        );
    }

    public function error(Exception $e): void
    {
        App::error()->add($e->getMessage());
        $this->beginPage(
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    __('Pages')                           => $this->getRedirection(true),
                    __('Pages actions')                   => '',
                ]
            )
        );
        $this->endPage();
    }

    public function beginPage(string $breadcrumb = '', string $head = ''): void
    {
        Page::openModule(
            __('Included pages'),
            Page::jsLoad('js/_posts_actions.js') .
            $head
        );
        echo
        $breadcrumb;

        echo (new Para())
            ->items([
                (new Link())
                    ->class('back')
                    ->href($this->getRedirection(true))
                    ->text(__('Back to pages list')),
            ])
        ->render();
    }

    public function endPage(): void
    {
        Page::closeModule();
    }

    /**
     * Set pages actions.
     */
    public function loadDefaults(): void
    {
        // We could have added a behavior here, but we want default action to be setup first
        BackendDefaultActions::adminRelatedPagesActionsPage($this);
        # --BEHAVIOR-- adminPagesActions -- Actions
        App::behavior()->callBehavior('adminRelatedPagesActions', $this);
    }

    public function process()
    {
        // fake action for pages reordering
        if (!empty($this->from['reorder'])) {
            $this->from['action'] = 'reorder';
        }
        $this->from['post_type'] = 'related';

        return parent::process();
    }
}
