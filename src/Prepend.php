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

use Dotclear\Core\Process;
use Dotclear\App;

class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (My::settings()->active) {
            $url_prefix  = My::settings()->url_prefix;
            $url_prefix  = (empty($url_prefix)) ? 'static' : $url_prefix;
            $url_pattern = $url_prefix . '/(.+)$';

            App::url()->register('related', $url_prefix, $url_pattern, UrlHandler::related(...));
            App::url()->register('relatedpreview', 'relatedpreview', '^relatedpreview/(.+)$', UrlHandler::relatedpreview(...));
            unset($url_prefix, $url_pattern);

            // Registering new post_type
            App::postTypes()->setPostType('related', 'plugin.php?p=related&part=page&id=%d', App::url()->getBase('related') . '/%s');
        }

        return true;
    }
}
