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
            __('Serve HTML templates and PHP scripts')
        )
        ->setting('title', __('Title (optional)') . ' :', __('Included pages'))
        ->setting('limit', __('Pages limit:'), 10)
        ->setting(
            'homeonly',
            __('Display on:'),
            0,
            'combo',
            [__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2]
        )
        ->setting('content_only', __('Content only'), 0, 'check')
        ->setting('class', __('CSS class:'), '')
        ->setting('offline', __('Offline'), 0, 'check');
    }

    /*
     * Widget public rendering helper.
     *
     * @param   WidgetsElement  $widgets     The widget
     */
    public static function pagesList(WidgetsElement $widget): string
    {
        if ($widget->offline) {
            return '';
        }

        if (($widget->homeonly === 1 && App::url()->getType() !== 'default')
            || ($widget->homeonly === 2 && App::url()->getType() === 'default')
        ) {
            return '';
        }

        $params['post_type']     = 'related';
        $params['no_content']    = true;
        $params['post_selected'] = true;
        $params['order']         = 'post_position ASC, post_title ASC';

        $limit = is_numeric($limit = $widget->get('limit')) ? abs((int) $limit) : 0;
        if ($limit > 0) {
            $params['limit'] = $limit;
        }

        $rs = App::blog()->getPosts($params);

        if ($rs->isEmpty()) {
            return '';
        }

        $res = $widget->title !== '' ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '';

        $res .= '<ul>';

        while ($rs->fetch()) {
            $url = $rs->getURL();
            $res .= '<li><a href="' . $url . '">' . Html::escapeHTML($rs->strField('post_title')) . '</a></li>';
        }

        $res .= '</ul>';

        return $widget->renderDiv((bool) $widget->content_only, 'related-pages-widget ' . $widget->class, '', $res);
    }
}
