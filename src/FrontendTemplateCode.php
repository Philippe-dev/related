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
        bool $_absolute_urls_,
        bool $_full_,
        array $_params_,
        string $_tag_
    ): void {
        if (App::frontend()->context()->posts instanceof \Dotclear\Database\MetaRecord) {
            $content = App::frontend()->context()->posts->getContent($_absolute_urls_);
            if ($_full_) {
                $excerpt = App::frontend()->context()->posts->getExcerpt($_absolute_urls_);
                $content = $excerpt . $content;
                unset($excerpt);
            }

            $related_file = App::frontend()->context()->posts->getRelatedFilename();
            if ($related_file !== '') {
                if (\Dotclear\Helper\File\Files::getExtension($related_file) === 'php') {
                    include $related_file;
                } else {
                    $previous_tpl_path = App::frontend()->template()->getPath();
                    App::frontend()->template()->setPath(\Dotclear\Plugin\related\My::settings()->getStr('files_path', false));

                    echo App::frontend()->template()->getData(basename($related_file));

                    App::frontend()->template()->setPath($previous_tpl_path);
                    unset($previous_tpl_path);
                }
            } else {
                echo App::frontend()->context()::global_filters(
                    $content,
                    $_params_,
                    $_tag_
                );
            }

            unset($content, $related_file);
        }
    }
}
