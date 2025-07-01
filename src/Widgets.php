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
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsElement;
use Dotclear\Plugin\widgets\WidgetsStack;

class Widgets
{
    /**
     * Initializes the pages widget.
     *
     * @param   WidgetsStack    $widgets    The widgets
     */
    public static function initWidgets(WidgetsStack $widgets): void
    {

        $widgets->create(
            'related',
            __('Included pages'),
            self::pagesList(...),
            null,
            __('Serve pages & scripts')
        );

        $widgets->related->setting('title', __('Title (optional)') . ' :', __('Included pages'));
        $widgets->related->setting('limit', __('Pages limit:'), 10);
        $widgets->related->setting(
            'homeonly',
            __('Display on:'),
            0,
            'combo',
            [__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2]
        );
        $widgets->related->setting('content_only', __('Content only'), 0, 'check');
        $widgets->related->setting('class', __('CSS class:'), '');
        $widgets->related->setting('offline', __('Offline'), 0, 'check');
    }

    /*
     * Widget public rendering helper.
     *
     * @param   WidgetsElement  $widgets     The widget
     */
    public static function pagesList(WidgetsElement $widgets)
    {
        if ($widgets->offline) {
            return;
        }

        if (($widgets->homeonly == 1 && App::url()->getType() != 'default') || ($widgets->homeonly == 2 && App::url()->getType() == 'default')) {
            return;
        }

        $params['post_type']     = 'related';
        $params['limit']         = abs((int) $widgets->get('limit'));
        $params['no_content']    = true;
        $params['post_selected'] = true;
        $params['order']         = 'post_position ASC, post_title ASC';

        $rs = App::blog()->getPosts($params);

        if ($rs->isEmpty()) {
            return;
        }

        $res = ($widgets->title ? $widgets->renderTitle(Html::escapeHTML($widgets->title)) : '');

        $res .= '<ul>';
        while ($rs->fetch()) {
            $res .= '<li><a href="' . $rs->getURL() . '">' . Html::escapeHTML($rs->post_title) . '</a></li>';
        }
        $res .= '</ul>';

        return $widgets->renderDiv((bool) $widgets->content_only, 'related-pages-widget ' . $widgets->class, '', $res);
    }
}
