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
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsElement;
use Dotclear\Plugin\widgets\WidgetsStack;

class Widgets
{
    public static function initDefaultWidgets(WidgetsStack $w, array $d)
    {
        $d['extra']->append($w->related);
    }

    public static function init(WidgetsStack $w)
    {
        $w->create('related', __('Related pages'), self::pagesList(...));
        $w->related->setting('title', __('Title (optional)') . ' :', __('Related pages'));
        $w->related->setting('limit', __('Pages limit:'), 10);
        $w->related->setting(
            'homeonly',
            __('Display on:'),
            0,
            'combo',
            [__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2]
        );
        $w->related->setting('content_only', __('Content only'), 0, 'check');
        $w->related->setting('class', __('CSS class:'), '');
        $w->related->setting('offline', __('Offline'), 0, 'check');
    }

    public static function pagesList(WidgetsElement $w)
    {
        if ($w->offline) {
            return;
        }

        if (($w->homeonly == 1 && App::url()->getType() != 'default') || ($w->homeonly == 2 && App::url()->getType() == 'default')) {
            return;
        }

        $params['post_type']     = 'related';
        $params['limit']         = abs((int) $w->get('limit'));
        $params['no_content']    = true;
        $params['post_selected'] = true;
        $params['order']         = 'post_position ASC, post_title ASC';

        $rs = App::blog()->getPosts($params);

        if ($rs->isEmpty()) {
            return;
        }

        $res = ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) : '');

        $res .= '<ul>';
        while ($rs->fetch()) {
            $res .= '<li><a href="' . $rs->getURL() . '">' . Html::escapeHTML($rs->post_title) . '</a></li>';
        }
        $res .= '</ul>';

        return $w->renderDiv((bool) $w->content_only, 'related-pages-widget ' . $w->class, '', $res);
    }
}
