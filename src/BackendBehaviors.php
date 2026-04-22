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
use Dotclear\Core\Auth;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Helper\Stack\Filter;
use Dotclear\App;

class BackendBehaviors
{
    /*
     * @param      Favorites  $favs   The favs
     */
    public static function dashboardFavorites(Favorites $favorites): string
    {
        $favorites->register('related', [
            'title'       => __('Included pages'),
            'url'         => My::manageUrl(),
            'small-icon'  => My::icons(),
            'large-icon'  => My::icons(),
            'permissions' => App::auth()->makePermissions([
                Auth::PERMISSION_USAGE, Auth::PERMISSION_CONTENT_ADMIN,
            ]),
            'dashboard_cb' => function (ArrayObject $icon): void {
                /**
                 * @var        ArrayObject<string, mixed>
                 */
                $params              = new ArrayObject();
                $params['post_type'] = 'related';
                $page_count          = App::blog()->getPosts($params, true)->f(0);
                if ($page_count > 0) {
                    $str_pages     = ($page_count > 1) ? __('%d included pages') : __('%d included page');
                    $icon['title'] = sprintf($str_pages, $page_count);
                }
            }
        ]);

        return '';
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
