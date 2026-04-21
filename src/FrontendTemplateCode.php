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

class FrontendTemplateCode
{
    /**
     * PHP code for tpl:EntryContent value
     *
     * @param      array<int|string, mixed>     $_params_  The parameters
     */
    public static function EntryContent(
        array $_params_,
        string $_tag_
    ): void {
        $urls = '0';
        if (!empty($attr['absolute_urls'])) {
            $urls = '1';
        }

        if (!empty($attr['full'])) {
            $content = App::frontend()->context()->posts->getExcerpt($urls) . App::frontend()->context()->posts->getContent($urls);
        } else {
            $content = App::frontend()->context()->posts->getContent($urls);
        }

        if (($related_file = App::frontend()->context()->posts->getRelatedFilename()) !== false) {
            if (\Dotclear\Helper\File\Files::getExtension($related_file) === 'php') {
                echo App::frontend()->context()::global_filters(
                    '',
                    $_params_,
                    $_tag_
                );

                include $related_file;
            } else {
                echo App::frontend()->context()::global_filters(
                    '',
                    $_params_,
                    $_tag_
                );

                $previous_tpl_path = App::frontend()->template()->getPath();
                App::frontend()->template()->setPath(\Dotclear\Plugin\related\My::settings()->files_path);

                echo App::frontend()->template()->getData(basename($related_file));

                App::frontend()->template()->setPath($previous_tpl_path);
                unset($previous_tpl_path);
            }
            unset($related_file);
        } else {
            echo App::frontend()->context()::global_filters(
                $content,
                $_params_,
                $_tag_
            );
        }
    }
}
