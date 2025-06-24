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
