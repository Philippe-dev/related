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

use Exception;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Text;
use Dotclear\App;

class Config
{
    use TraitProcess;
    
    private static string $default_tab = 'settings';

    public static function init(): bool
    {
        App::backend()->related_default_tab = self::$default_tab;

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

        App::backend()->related_active = (bool) $settings->active;

        $already_active = App::backend()->related_active;

        try {
            App::backend()->related_active = isset($_POST['related_active']);
            $settings->put('active', App::backend()->related_active, 'boolean');

            // change other settings only if they were in HTML page
            if ($already_active) {
                if (empty($_POST['related_files_path']) || trim($_POST['related_files_path']) === '') {
                    $tmp_files_path = App::blog()->publicPath() . '/related';
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
                    throw new Exception(sprintf(
                        __('Directory "%s" for related files repository needs to allow read and write access.'),
                        $tmp_files_path
                    ));
                }
            }

            Notices::addSuccessNotice(__('Configuration has been updated.'));
            App::blog()->triggerBlog();

            App::backend()->url()->redirect('admin.plugins', [
                'module' => My::id(),
                'conf'   => '1',
            ]);
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
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
                        (new Label(__('Included files repository path:'), Label::OUTSIDE_LABEL_AFTER))->for('related_files_path')->class('classic'),
                        (new Span('&nbsp;')),
                        (new Input('related_files_path', (string) $settings->files_path))->size(50)->max(255)->value($settings->files_path),
                    ]),
                ]),
            ])->render();

            echo
            (new Div())->items([
                (new Fieldset())->class('fieldset')->legend((new Legend(__('Advanced options'))))->fields([
                    (new Para())->items([
                        (new Label(__('URL prefix:'), Label::OUTSIDE_LABEL_AFTER))->for('related_url_prefix')->class('classic'),
                        (new Span('&nbsp;')),
                        (new Input('related_url_prefix', (string) $settings->url_prefix))->size(20)->max(255)->value($settings->url_prefix),
                        (new Note())
                            ->class(['form-note', 'warning'])
                            ->text(__('This prefix will be used to generate the pages URLs. Do not use any existing prefix such as post, category or page.')),
                    ]),
                ]),
            ])->render();
        }

        Page::helpBlock('related_pages_config');
    }
}
