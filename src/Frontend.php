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
use Dotclear\Core\Process;

class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehavior('coreBlogGetPosts', PublicBehaviors::coreBlogGetPosts(...));
        App::behavior()->addBehavior('publicBeforeDocument', PublicBehaviors::publicBeforeDocument(...));
        App::behavior()->addBehavior('templateBeforeBlock', PublicBehaviors::templateBeforeBlock(...));

        App::frontend()->template()->addValue('EntryContent', Templates::PageContent(...));

        App::behavior()->addBehavior('initWidgets', Widgets::init(...));
        App::behavior()->addBehavior('initDefaultWidgets', Widgets::initDefaultWidgets(...));

        return true;
    }
}
