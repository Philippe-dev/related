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
use Dotclear\Database\MetaRecord;

class RelatedExtensions
{
    /**
     * Get related file name
     */
    public static function getRelatedFilename(MetaRecord $rs): string
    {
        $files_path = App::blog()->settings()->get('related')->getStr('files_path', false);
        if ($files_path === '') {
            return '';
        }

        $meta_rs = App::meta()->getMetaRecordset($rs->strField('post_meta'), 'related_file');
        if (!$meta_rs->isEmpty()) {
            $filename = $files_path . '/' . $meta_rs->strField('meta_id');
            if (is_readable($filename)) {
                return $filename;
            }
        }

        return '';
    }
}
