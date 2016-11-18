<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of Related, a plugin for DotClear2.
#
# Copyright(c) 2014-2016 Nicolas Roudaire <nikrou77@gmail.com> http://www.nikrou.net
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

                dcPage::addSuccessNotice(__('Configuration has been updated.'));
            } else {
                throw new Exception(sprintf(
                    __('Directory "%s" for related files repository needs to allow read and write access.'),
                    $tmp_repository
                ));
            }
        }
    } catch(Exception $e) {
        dcPage::addErrorNotice($e->getMessage());
    }
}

if ($related_active) {
    /* filters */
    $user_id = !empty($_GET['user_id']) ? $_GET['user_id'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $in_widget = isset($_GET['in_widget']) ? $_GET['in_widget'] : '';
    $month = !empty($_GET['month']) ? $_GET['month'] : '';
    $lang = !empty($_GET['lang']) ?	$_GET['lang'] : '';
    $sortby = !empty($_GET['sortby']) ?	$_GET['sortby'] : 'post_dt';
    $order = !empty($_GET['order']) ? $_GET['order'] : 'desc';

    $show_filters = false;
    $form_filter_title = __('Show filters and display options');

        # Creating filter combo boxes
    if (!$core->error->flag()) {
        # Getting authors
        try {
            $users = $core->blog->getPostsUsers();
        } catch (Exception $e) {
            dcPage::addErrorNotice($e->getMessage());
        }

        # Filter form we'll put in html_block
        $users_combo = array_merge(
            array('-' => ''),
            dcAdminCombos::getUsersCombo($users)
        );

        # Getting langs
        try {
            $langs = $core->blog->getLangs();
        } catch (Exception $e) {
            dcPage::addErrorNotice($e->getMessage());
        }
        $lang_combo = array_merge(
            array('-' => ''),
            dcAdminCombos::getLangsCombo($langs,false)
        );

        $status_combo = array_merge(
            array('-' => ''),
            dcAdminCombos::getPostStatusesCombo()
        );

        $in_widget_combo = array(
            '-' => '',
            __('yes') => 1,
            __('no') => 0
        );

        # Getting dates
        try {
            $dates = $core->blog->getDates(array('type'=>'month'));
        } catch (Exception $e) {
            dcPage::addErrorNotice($e->getMessage());
        }

        # Months array
        $dt_m_combo = array_merge(
            array('-' => ''),
            dcAdminCombos::getDatesCombo($dates)
        );

        $sortby_combo = array(
            __('Date') => 'post_dt',
            __('Title') => 'post_title',
            __('Author') => 'user_id',
            __('Status') => 'post_status',
            __('Visible pages in widget') => 'post_selected'
        );

        $order_combo = array(
            __('Descending') => 'desc',
            __('Ascending') => 'asc'
        );
    }

    $page = !empty($_GET['page']) ? $_GET['page'] : 1;
    $nb_per_page =  30;
    if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
        $nb_per_page = (integer) $_GET['nb'];
    }

    $params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
    $params['no_content'] = true;
    $params['order'] = 'post_position asc';

    # Get pages
    try {
        $pages = $core->blog->getPosts($params);
        $pages->extend("rsRelated");
        $counter = $core->blog->getPosts($params, true);
        $page_list = new adminPageList($core,$pages,$counter->f(0));
    } catch (Exception $e) {
        dcPage::addErrorNotice($e->getMessage());
    }

    # apply filters
    # - User filter
    if ($user_id !== '' && in_array($user_id,$users_combo)) {
        $params['user_id'] = $user_id;
        $show_filters = true;
    } else {
        $user_id='';
    }

    # - Status filter
    if ($status !== '' && in_array($status, $status_combo)) {
        $params['post_status'] = $status;
        $show_filters = true;
    } else {
        $status='';
    }

    # - in widget filter
    if ($in_widget !== '' && in_array($in_widget, $in_widget_combo)) {
        $params['post_selected'] = $in_widget;
        $show_filters = true;
    } else {
        $in_widget = '';
    }

    # - Month filter
    if ($month !== '' && in_array($month, $dt_m_combo)) {
        $params['post_month'] = substr($month, 4, 2);
        $params['post_year'] = substr($month, 0, 4);
        $show_filters = true;
    } else {
        $month='';
    }

    # - Lang filter
    if ($lang !== '' && in_array($lang, $lang_combo)) {
        $params['post_lang'] = $lang;
        $show_filters = true;
    } else {
        $lang='';
    }

    # - Sortby and order filter
    if ($sortby !== '' && in_array($sortby, $sortby_combo)) {
        if ($order !== '' && in_array($order, $order_combo)) {
            $params['order'] = $sortby.' '.$order;
        } else {
            $order='desc';
        }

        if ($sortby != 'post_dt' || $order != 'desc') {
            $show_filters = true;
        }
    } else {
        $sortby = 'post_dt';
        $order = 'desc';
    }

    # Get pages
    try {
        $pages = $core->blog->getPosts($params);
        $pages->extend('rsRelated');
        $counter = $core->blog->getPosts($params, true);
        $page_list = new adminPageList($core, $pages, $counter->f(0));
    } catch (Exception $e) {
        dcPage::addErrorNotice($e->getMessage());
    }

    # Actions combo box
    $combo_action = array();
    if ($core->auth->check('publish,contentadmin', $core->blog->id)) {
        $combo_action[__('publish')] = 'publish';
        $combo_action[__('unpublish')] = 'unpublish';
        $combo_action[__('mark as pending')] = 'pending';
    }
    if ($core->auth->check('admin', $core->blog->id)) {
        $combo_action[__('change author')] = 'author';
    }
    if ($core->auth->check('delete,contentadmin', $core->blog->id)) {
        $combo_action[__('delete')] = 'delete';
    }
    $combo_action[__('Widget')] = array(
        __('Add to widget') => 'selected',
        __('Remove from widget') => 'unselected'
    );

    # --BEHAVIOR-- adminPagesActionsCombo
    $core->callBehavior('adminPagesActionsCombo', array(&$combo_action));

    $pages_actions_page = new relatedPagesActionsPage($core, 'plugin.php', array('p' => 'related'));
    if (!$pages_actions_page->process()) {
        $process_successfull = false;
    } else {
        $process_successfull = true;
    }

    /**
     * Manage public list if requested
     */
    $all_pages = $core->blog->getPosts(array('post_type' => 'related'));
    $all_pages->extend('rsRelated');

    if (isset($_POST['pages_upd'])) {
        $default_tab = 'pages_order';

        $public_pages = relatedHelpers::getPublicList($pages);
        $visible = (!empty($_POST['p_visibles']) && is_array($_POST['p_visibles']))?$_POST['p_visibles']:array();
        $order = (!empty($_POST['p_order']))?explode(',',$_POST['p_order']):array();

        try {
            $i = 1;
            $meta = new dcMeta($core);
            foreach ($public_pages as $c_page) {
                $cur = $core->con->openCursor($core->prefix.'post');
                $cur->post_upddt = date('Y-m-d H:i:s');
                $cur->post_selected = (integer)in_array($c_page['id'], $visible);
                $cur->update('WHERE post_id = '.$c_page['id']);

                if (!empty($order)) {
                    $pos = array_search($c_page['id'], $order);
                    $pos = (integer)$pos + 1;
                    $meta->delPostMeta($c_page['id'], 'related_position');
                    $meta->setPostMeta($c_page['id'], 'related_position', $pos);
                }
            }
            $core->blog->triggerBlog();
            http::redirect($p_url.'&reord=1');
        } catch (Exception $e) {
            dcPage::addErrorNotice($e->getMessage());
        }
    }

    if (!empty($_GET['reord'])) {
        dcPage::addSuccessNotice(__('Pages list has been sorted.'));
        $default_tab = 'pages_order';
    }
}

if (!$process_successfull) {
    include_once(dirname(__FILE__).'/../tpl/index.tpl');
}
