<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of Related, a plugin for DotClear2.
#
# Copyright(c) 2014-2015 Nicolas Roudaire <nikrou77@gmail.com> http://www.nikrou.net
#
# Copyright (c) 2006-2010 Pep and contributors.
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) return;

dcPage::check('pages,contentadmin');

$core->blog->settings->addNameSpace('related');
$related_active = $core->blog->settings->related->active;
$related_was_actived = $related_active;

if ($core->blog->settings->related->related_files_path) {
    $related_files_path = $core->blog->settings->related->related_files_path;
    $core->blog->settings->related->put('files_path', $related_files_path, 'string', 'Related files repository', false);
    $core->blog->settings->related->drop('related_files_path');
} else {
    $related_files_path = $core->blog->settings->related->files_path;
}
if ($core->blog->settings->related->related_url_prefix) {
    $related_url_prefix = $core->blog->settings->related->related_url_prefix;
    $core->blog->settings->related->put('url_prefix', $related_url_prefix, 'string', 'Prefix used by the URLHandler', false);
    $core->blog->settings->related->drop('related_url_prefix');
} else {
    $related_url_prefix = $core->blog->settings->related->url_prefix;
}



$default_tab = 'pages_compose';

/**
 * Build "Manage Pages" tab
 */
$params = array('post_type' => 'related');

if (!empty($_POST['saveconfig'])) {
    try {
        $default_tab = 'settings';

        $related_active = (empty($_POST['related_active']))?false:true;
        $core->blog->settings->related->put('active', $related_active, 'boolean', 'Related plugin activated?');

        // change other settings only if they were in html page
        if ($related_was_actived) {
            if (empty($_POST['repository']) || trim($_POST['repository']) == '') {
                $tmp_repository = $core->blog->public_path.'/related';
            } else {
                $tmp_repository = trim($_POST['repository']);
            }

            if (empty($_POST['url_prefix']) || trim($_POST['url_prefix']) == '') {
                $related_url_prefix = 'static';
            } else {
                $related_url_prefix = text::str2URL(trim($_POST['url_prefix']));
            }

            $core->blog->settings->related->put('url_prefix', $related_url_prefix);

            if (is_dir($tmp_repository) && is_writable($tmp_repository)) {
                $core->blog->settings->related->put('files_path', $tmp_repository);
                $repository = $tmp_repository;
                $message = __('Configuration updated.');
            } else {
                $core->error->add(__('Directory for related files repository needs to allow read and write access.'));
            }
        }

        $message = __('The configuration has been updated.');
    } catch(Exception $e) {
        $core->error->add($e->getMessage());
    }
}

if ($related_active) {
    $page = !empty($_GET['page']) ? $_GET['page'] : 1;
    $nb_per_page =  30;
    if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
        $nb_per_page = (integer) $_GET['nb'];
    }

    $params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
    $params['no_content'] = true;

    # Get pages
    try {
        $pages = $core->blog->getPosts($params);
        $pages->extend("rsRelated");
        $counter = $core->blog->getPosts($params,true);
        $page_list = new adminPageList($core,$pages,$counter->f(0));
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }

    # Actions combo box
    $combo_action = array();
    if ($core->auth->check('publish,contentadmin',$core->blog->id)) {
        $combo_action[__('publish')] = 'publish';
        $combo_action[__('unpublish')] = 'unpublish';
        $combo_action[__('mark as pending')] = 'pending';
    }
    if ($core->auth->check('admin',$core->blog->id)) {
        $combo_action[__('change author')] = 'author';
    }
    if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
        $combo_action[__('delete')] = 'delete';
    }

    # --BEHAVIOR-- adminPagesActionsCombo
    $core->callBehavior('adminPagesActionsCombo',array(&$combo_action));

    /**
     * Manage public list if requested
     */
    if (isset($_POST['pages_upd'])) {
        $default_tab = 'pages_order';

        $public_pages = relatedHelpers::getPublicList($pages);
        $visible = (!empty($_POST['p_visibles']) && is_array($_POST['p_visibles']))?$_POST['p_visibles']:array();
        $order = (!empty($_POST['public_order']))?explode(',',$_POST['public_order']):array();

        try {
            $i = 1;
            $meta = new dcMeta($core);
            foreach ($public_pages as $c_page) {
                $cur = $core->con->openCursor($core->prefix.'post');
                $cur->post_upddt = date('Y-m-d H:i:s');
                $cur->post_selected = (integer)in_array($c_page['id'],$visible);
                $cur->update('WHERE post_id = '.$c_page['id']);

                if (!empty($order)) {
                    $pos = array_search($c_page['id'],$order);
                    $pos = (integer)$pos + 1;
                    $meta->delPostMeta($c_page['id'],'related_position');
                    $meta->setPostMeta($c_page['id'],'related_position',$pos);
                }
            }
            $core->blog->triggerBlog();
            http::redirect($p_url.'&reord=1');
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }

    if (!empty($_GET['reord'])) {
        $message = __('Pages list has been sorted.');
        $default_tab = 'pages_order';
    }
}

include_once(dirname(__FILE__).'/../tpl/index.tpl');