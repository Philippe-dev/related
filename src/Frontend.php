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
use Dotclear\Helper\Process\TraitProcess;

class Frontend
{
    use TraitProcess;

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
        App::behavior()->addBehavior('publicBeforeDocumentV2', FrontendBehaviors::publicBeforeDocument(...));
        App::behavior()->addBehavior('templateBeforeBlockV2', FrontendBehaviors::templateBeforeBlock(...));

        App::frontend()->template()->addValue('EntryContent', FrontendTemplates::PageContent(...));

        App::behavior()->addBehavior('initWidgets', Widgets::initWidgets(...));

        return true;
    }
}
