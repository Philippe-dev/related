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

$this_version = dcCore::app()->plugins->moduleInfo('related', 'version');
$installed_version = dcCore::app()->getVersion('related');
if (version_compare($installed_version, $this_version, '>=')) {
    return;
}

dcCore::app()->blog->settings->addNamespace('related');
if (!dcCore::app()->blog->settings->related->active) {
    if (version_compare($installed_version, '1.1', '<')) {
        dcCore::app()->blog->settings->related->put('active', true, 'boolean', 'Related plugin activated?', true);
    } else {
        dcCore::app()->blog->settings->related->put('active', false, 'boolean', 'Related plugin activated?', true);
    }
}
$related_files_path = $related_url_prefix = null;

if (dcCore::app()->blog->settings->related->related_files_path) {
    $related_files_path = dcCore::app()->blog->settings->related->related_files_path;
    dcCore::app()->blog->settings->related->put('files_path', $related_files_path, 'string', 'Related files repository', false);
    dcCore::app()->blog->settings->related->drop('related_files_path');
}
if (dcCore::app()->blog->settings->related->related_url_prefix) {
    $related_url_prefix = dcCore::app()->blog->settings->related->related_url_prefix;
    dcCore::app()->blog->settings->related->put('url_prefix', $related_url_prefix, 'string', 'Prefix used by the URLHandler', false);
    dcCore::app()->blog->settings->related->drop('related_url_prefix');
}

if (!dcCore::app()->blog->settings->related->files_path && !$related_files_path) {
    $public_path = dcCore::app()->blog->public_path;
    $related_files_path = $public_path . '/related';

    if (is_dir($related_files_path)) {
        if (!is_readable($related_files_path) || !is_writable($related_files_path)) {
            throw new Exception(sprintf(
                __('Directory "%s" for related files repository needs to allow read and write access.'),
                $related_files_path
            ));
        }
    } else {
        try {
            files::makeDir($related_files_path);
        } catch (Exception $e) {
            throw $e;
        }
    }

    if (!is_file($related_files_path . '/.htaccess')) {
        try {
            file_put_contents($related_files_path . '/.htaccess', "Deny from all\n");
        } catch (Exception $e) {
        }
    }

    dcCore::app()->blog->settings->related->put('files_path', $related_files_path, 'string', 'Related files repository', false);
}

if (!dcCore::app()->blog->settings->related->url_prefix && !$related_url_prefix) {
    dcCore::app()->blog->settings->related->put('url_prefix', 'static', 'string', 'Prefix used by the URLHandler', false);
}
dcCore::app()->setVersion('related', $this_version);
return true;
