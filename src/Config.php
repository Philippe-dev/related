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

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Text;
use dcCore;

class Config extends Process
{
    private static $default_tab = 'settings';

    public static function init(): bool
    {
        dcCore::app()->admin->related_default_tab = self::$default_tab;

        return self::status(My::checkContext(My::CONFIG));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (empty($_POST['save'])) {
            return true;
        }

        $settings = My::settings();

        dcCore::app()->admin->related_active = (boolean) $settings->active;

        $already_active = dcCore::app()->admin->related_active;

        try {
            dcCore::app()->admin->related_active = isset($_POST['related_active']);
            $settings->put('active', dcCore::app()->admin->related_active, 'boolean');

            // change other settings only if they were in HTML page
            if ($already_active) {
                if (empty($_POST['related_files_path']) || trim($_POST['related_files_path']) === '') {
                    $tmp_files_path = dcCore::app()->blog->public_path . '/related';
                } else {
                    $tmp_files_path = trim($_POST['related_files_path']);
                }

                if (empty($_POST['related_url_prefix']) || trim($_POST['related_url_prefix']) === '') {
                    $related_url_prefix = 'static';
                } else {
                    $related_url_prefix = Text::str2URL(trim($_POST['related_url_prefix']));
                }

                $settings->put('url_prefix', $related_url_prefix);

                if (is_dir($tmp_files_path) && is_writable($tmp_files_path)) {
                    $settings->put('files_path', $tmp_files_path);
                } else {
                    throw new \Exception(sprintf(
                        __('Directory "%s" for related files repository needs to allow read and write access.'),
                        $tmp_files_path
                    ));
                }
            }

            Notices::addSuccessNotice(__('Configuration has been updated.'));
            dcCore::app()->blog->triggerBlog();

            dcCore::app()->admin->url->redirect('admin.plugins', [
                'module' => My::id(),
                'conf' => '1'
            ]);
        } catch (\Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

      return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $settings = My::settings();

        echo
        (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Plugin activation'))))->fields([
                (new Para())->items([
                    (new Checkbox('related_active', $settings->active))->value(1),
                    (new Label(__('Enable Related plugin'), Label::OUTSIDE_LABEL_AFTER))->for('related_active')->class('classic'),
                ]),
            ]),
        ])->render();

        if ($settings->active) {
            echo
            (new Div())->items([
                (new Fieldset())->class('fieldset')->legend((new Legend(__('General options'))))->fields([
                    (new Para())->items([
                        (new Label(__('Repository path:'), Label::OUTSIDE_LABEL_AFTER))->for('related_files_path')->class('classic'),
                        (new Input('related_files_path', $settings->files_path))->size(80)->maximal(255)->value($settings->files_path),
                    ]),
                ]),
            ])->render();

            echo
            (new Div())->items([
                (new Fieldset())->class('fieldset')->legend((new Legend(__('Advanced options'))))->fields([
                    (new Para())->items([
                        (new Label(__('URL prefix:'), Label::OUTSIDE_LABEL_AFTER))->for('related_url_prefix')->class('classic'),
                        (new Input('related_url_prefix', $settings->url_prefix))->size(80)->maximal(255)->value($settings->url_prefix),
                    ]),
                ]),
            ])->render();
        }
    }
}
