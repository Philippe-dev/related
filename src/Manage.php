<?php
/*
 *  -- BEGIN LICENSE BLOCK ----------------------------------
 *
 *  This file is part of Related, a plugin for DotClear2.
 *
 *  Licensed under the GPL version 2.0 license.
 *  See LICENSE file or
 *  http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 *  -- END LICENSE BLOCK ------------------------------------
 */

declare(strict_types=1);

namespace Dotclear\Plugin\related;

use dcCore;
use Dotclear\Core\Process;

class Manage extends Process
{
    private static $active_part = 'pages';

    public static function init(): bool
    {
        if (My::checkContext(My::MANAGE)) {
            $default_part = My::settings()->active ? 'pages' : 'order';
            self::$active_part = $_REQUEST['part'] ?? $default_part;
            dcCore::app()->admin->related_default_tab = self::$active_part;

            if (self::$active_part === 'pages') {
                self::status(ManageRelatedPages::init());
            } elseif (self::$active_part === 'page') {
                self::status(ManagePage::init());
            } else {
                self::status(true);
            }
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (self::$active_part === 'pages') {
            self::status(ManageRelatedPages::process());
        } elseif (self::$active_part === 'page') {
            self::status(ManagePage::process());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        if (self::$active_part === 'pages') {
            ManageRelatedPages::render();
        } elseif (self::$active_part === 'page') {
            ManagePage::render();
        }
    }
}
