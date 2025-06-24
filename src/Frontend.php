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

        
        App::behavior()->addBehavior('publicBreadcrumb', FrontendBehaviors::publicBreadcrumb(...));

        App::behavior()->addBehavior('coreBlogGetPosts', FrontendBehaviors::coreBlogGetPosts(...));
        App::behavior()->addBehavior('publicBeforeDocument', FrontendBehaviors::publicBeforeDocument(...));
        App::behavior()->addBehavior('templateBeforeBlock', FrontendBehaviors::templateBeforeBlock(...));

        App::frontend()->template()->addValue('EntryContent', FrontendTemplates::PageContent(...));

        App::behavior()->addBehavior('initWidgets', Widgets::init(...));
        App::behavior()->addBehavior('initDefaultWidgets', Widgets::initDefaultWidgets(...));

        return true;
    }
}
