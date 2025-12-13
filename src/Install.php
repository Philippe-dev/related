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

use Dotclear\Helper\Process\TraitProcess;
use Dotclear\App;
use Dotclear\Helper\File\Files;

class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!My::settings()->files_path) {
            $public_path = App::blog()->public_path;
            $files_path  = $public_path . '/related';

            if (is_dir($files_path)) {
                if (!is_readable($files_path) || !is_writable($files_path)) {
                    throw new Exception(__('Directory for related files repository needs to allow read and write access.'));
                }
            } else {
                try {
                    Files::makeDir($files_path);
                } catch (Exception $e) {
                    throw $e;
                }
            }

            if (!is_file($files_path . '/.htaccess')) {
                try {
                    file_put_contents($files_path . '/.htaccess', "Deny from all\n");
                } catch (Exception $e) {
                }
            }
        } else {
            $files_path = My::settings()->files_path;
        }

        My::settings()->put('active', false, 'boolean', 'Enable plugin', false, true);
        My::settings()->put('url_prefix', 'static', 'string', 'Prefix used by the URLHandler', false, true);
        My::settings()->put('files_path', $files_path, 'string', 'Related files repository', false, true);

        return true;
    }
}
