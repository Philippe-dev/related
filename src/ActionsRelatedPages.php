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

use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\Action\ActionsPostsDefault;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Plugin\pages\BackendActions;
use Exception;

class ActionsRelatedPages extends ActionsPosts
{
    public const ADD_TO_WIDGET_ACTION      = 'selected';
    public const REMOVE_FROM_WIDGET_ACTION = 'unselected';
    protected bool $use_render             = true;

    public function __construct(?string $uri, array $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);

        $this->redirect_fields = ['p', 'part'];
        $this->caller_title    = __('Related pages');

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

    protected function loadDefaults(): void
    {
    }

    public function beginPage(string $breadcrumb = '', string $head = ''): void
    {
        Page::openModule(__('Related pages'), Page::jsLoad('js/_posts_actions.js') . $head);
        echo $breadcrumb, '<p><a class="back" href="' . $this->getRedirection(true) . '">' . __('Back to related page list') . '</a></p>';
    }

    public function endPage(): void
    {
        Page::closeModule();
    }

    public function process()
    {
        $this->from['post_type'] = 'related';

        return parent::process();
    }

    /**
     * Does a delete post.
     *
     * @param      BackendActions  $ap
     *
     * @throws     Exception
     */
    public static function doDeletePost(BackendActions $ap)
    {
        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No page selected'));
        }
        // Backward compatibility
        foreach ($ids as $id) {
            // --BEHAVIOR-- adminBeforePostDelete -- int
            App::behavior()->callBehavior('adminBeforePostDelete', (int) $id);
        }

        // --BEHAVIOR-- adminBeforePostsDelete -- array<int,string>
        App::behavior()->callBehavior('adminBeforePostsDelete', $ids);

        App::blog()->delPosts($ids);
        Notices::addSuccessNotice(
            sprintf(
                __(
                    '%d page has been successfully deleted',
                    '%d pages have been successfully deleted',
                    count($ids)
                ),
                count($ids)
            )
        );

        $ap->redirect(false);
    }
}
