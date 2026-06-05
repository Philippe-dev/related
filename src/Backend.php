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
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\Menus;
use Dotclear\Core\PostType;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Stack\Filter;

class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $icon  = $icon_dark = '';
        $icons = My::icons('np');
        if ($icons !== []) {
            $icon      = $icons[0];
            $icon_dark = $icons[1] ?? $icons[0];
        }

        App::postTypes()->set(new PostType(
            'related',
            urldecode(My::manageUrl(['p' => 'related', 'part' => 'page', 'id' => '%d'], '&')),
            App::url()->getURLFor('related', '%s'),
            'Included pages',
            urldecode(My::manageUrl(['p' => 'related', 'part' => 'list'], '&')),   // Admin URL for list of pages
            $icon,
            $icon_dark,
        ));

        My::addBackendMenuItem(Menus::MENU_BLOG);

        App::behavior()->addBehaviors([

            'adminColumnsListsV2' => function (ArrayObject $cols): string {
                $cols['pages'] = [My::name(), [
                    'date'       => [true, __('Date')],
                    'author'     => [true, __('Author')],
                    'comments'   => [true, __('Comments')],
                    'trackbacks' => [true, __('Trackbacks')],
                ]];

                return '';
            },
            'adminFiltersListsV2' => function (ArrayObject $sorts): string {
                $sorts['related'] = [
                    My::name(),
                    null,
                    null,
                    null,
                    [__('pages per page'), 30],
                ];

                return '';
            },
            'adminDashboardFavoritesV2' => function (Favorites $favs): string {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => App::auth()->makePermissions([
                        App::auth()::PERMISSION_CONTENT_ADMIN,
                    ]),
                    'dashboard_cb' => function (ArrayObject $icon): void {
                        /**                    ]),

                         * @var        ArrayObject<string, mixed>
                         */
                        $params              = new ArrayObject();
                        $params['post_type'] = 'related';
                        $page_count          = App::blog()->getPosts($params, true)->cardinal();
                        if ($page_count > 0) {
                            $str_pages     = ($page_count > 1) ? __('%d included pages') : __('%d included page');
                            $icon['title'] = sprintf($str_pages, $page_count);
                        }
                    },
                    'active_cb' => fn (string $request, array $params): bool => isset($params['p']) && $params['p'] === My::id() && !isset($params['part']),
                ]);

                return '';
            },
            'adminUsersActionsHeaders' => fn (): string => My::jsLoad('_users_actions'),
            'initWidgets'              => Widgets::initWidgets(...),
            'adminPostFilterV2'        => self::adminPostFilter(...),
        ]);

        return true;
    }

    /**
     * @param      ArrayObject<int, mixed>  $filters  The filters
     */
    public static function adminPostFilter(ArrayObject $filters): string
    {
        if (App::backend()->getPageURL() === App::backend()->url()->get('admin.plugin.' . My::id())) {
            $filters->append((new Filter('comment'))
                ->param());

            $filters->append((new Filter('trackback'))
                ->param());

            $filters->append((new Filter('cat_id'))
                ->param());

            $filters->append((new Filter('selected'))
                ->param('post_selected')
                ->title(__('In widget:'))
                ->options([
                    '-'       => '',
                    __('yes') => '1',
                    __('no')  => '0',
                ]));
        }

        return '';
    }
}
