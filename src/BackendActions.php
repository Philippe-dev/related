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
    /**
     * Use render method.
     */
    protected bool $use_render = true;

    /**
     * Constructs a new instance.
     *
     * @param      null|string              $uri            The uri
     * @param      array<string, mixed>     $redirect_args  The redirect arguments
     */
    public function __construct(?string $uri, array $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);

        $this->redirect_fields = ['p', 'part', 'user_id'];
        $this->caller_title    = __('Included pages');
    }

    public function error(Exception $e): void
    {
        App::error()->add($e->getMessage());
        $this->beginPage(
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    __('Included pages')                   => $this->getRedirection(true),
                    __('Included pages actions')           => '',
                ]
            )
        );
        $this->endPage();
    }

    public function beginPage(string $breadcrumb = '', string $head = ''): void
    {
        if ($this->in_plugin) {
            Page::openModule(
                __('Included pages'),
                Page::jsLoad('js/_posts_actions.js') .
                $head
            );
            echo $breadcrumb;
        } else {
            Page::open(
                __('Included pages'),
                Page::jsLoad('js/_posts_actions.js') .
                $head,
                $breadcrumb
            );
        }

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
        if ($this->in_plugin) {
            Page::closeModule();
        } else {
            Page::close();
        }
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
