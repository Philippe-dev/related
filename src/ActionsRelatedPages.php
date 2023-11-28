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

use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\Action\ActionsPostsDefault;
use Dotclear\Core\Backend\Page;
use Dotclear\App;

class ActionsRelatedPages extends ActionsPosts
{
    public const ADD_TO_WIDGET_ACTION = 'selected';
    public const REMOVE_FROM_WIDGET_ACTION = 'unselected';

    protected bool $use_render = true;

    public function __construct(?string $uri, array $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);

        $this->redirect_fields = ['p', 'part'];
        $this->caller_title = __('Related pages');

        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $this->addAction(
                [__('Status') => [
                    __('Publish') => 'publish',
                    __('Unpublish') => 'unpublish',
                    __('Schedule') => 'schedule',
                    __('Mark as pending') => 'pending',
                ]],
                [ActionsPostsDefault::class, 'doChangePostStatus']
            );
        }

        $this->addAction(
            [__('Widget') => [
                __('Add to widget') => self::ADD_TO_WIDGET_ACTION,
                __('Remove from widget') => self::REMOVE_FROM_WIDGET_ACTION,
            ]],
            [ActionsPostsDefault::class, 'doUpdateSelectedPost']
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
}
