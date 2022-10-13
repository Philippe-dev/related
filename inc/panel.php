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

dcPage::check(dcCore::app()->auth->makePermissions([dcPages::PERMISSION_PAGES, dcAuth::PERMISSION_CONTENT_ADMIN]));

dcCore::app()->blog->settings->addNameSpace('related');
$related_active = dcCore::app()->blog->settings->related->active;
$related_was_actived = $related_active;

if (dcCore::app()->blog->settings->related->related_files_path) {
    $related_files_path = dcCore::app()->blog->settings->related->related_files_path;
    dcCore::app()->blog->settings->related->put('files_path', $related_files_path, 'string', 'Related files repository', false);
    dcCore::app()->blog->settings->related->drop('related_files_path');
} else {
    $related_files_path = dcCore::app()->blog->settings->related->files_path;
}
if (dcCore::app()->blog->settings->related->related_url_prefix) {
    $related_url_prefix = dcCore::app()->blog->settings->related->related_url_prefix;
    dcCore::app()->blog->settings->related->put('url_prefix', $related_url_prefix, 'string', 'Prefix used by the URLHandler', false);
    dcCore::app()->blog->settings->related->drop('related_url_prefix');
} else {
    $related_url_prefix = dcCore::app()->blog->settings->related->url_prefix;
}



$default_tab = 'pages_compose';
$process_successfull = false;

/**
 * Build "Manage Pages" tab
 */
$params = ['post_type' => 'related'];

if (!empty($_POST['saveconfig'])) {
    try {
        $default_tab = 'settings';

        $related_active = (empty($_POST['related_active'])) ? false : true;
        dcCore::app()->blog->settings->related->put('active', $related_active, 'boolean', 'Related plugin activated?');

        // change other settings only if they were in html page
        if ($related_was_actived) {
            if (empty($_POST['repository']) || trim($_POST['repository']) == '') {
                $tmp_repository = dcCore::app()->blog->public_path . '/related';
            } else {
                $tmp_repository = trim($_POST['repository']);
            }

            if (empty($_POST['url_prefix']) || trim($_POST['url_prefix']) == '') {
                $related_url_prefix = 'static';
            } else {
                $related_url_prefix = text::str2URL(trim($_POST['url_prefix']));
            }

            dcCore::app()->blog->settings->related->put('url_prefix', $related_url_prefix);

            if (is_dir($tmp_repository) && is_writable($tmp_repository)) {
                dcCore::app()->blog->settings->related->put('files_path', $tmp_repository);
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
    // filters
    $user_id = !empty($_GET['user_id']) ? $_GET['user_id'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $in_widget = isset($_GET['in_widget']) ? $_GET['in_widget'] : '';
    $month = !empty($_GET['month']) ? $_GET['month'] : '';
    $lang = !empty($_GET['lang']) ? $_GET['lang'] : '';
    $sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : 'post_dt';
    $order = !empty($_GET['order']) ? $_GET['order'] : 'desc';

    $show_filters = false;
    $form_filter_title = __('Show filters and display options');

    // Creating filter combo boxes
    $users_combo = [];
    $status_combo = [];
    $in_widget_combo = [];
    $dt_m_combo = [];
    $lang_combo = [];
    $sortby_combo = [];
    $order_combo = [];
    if (!dcCore::app()->error->flag()) {
        // Getting authors
        try {
            $users = dcCore::app()->blog->getPostsUsers();
        } catch (Exception $e) {
            $users = null;
            dcPage::addErrorNotice($e->getMessage());
        }

        // Filter form we'll put in html_block
        $users_combo = array_merge(
            ['-' => ''],
            dcAdminCombos::getUsersCombo($users)
        );

        // Getting langs
        try {
            $langs = dcCore::app()->blog->getLangs();
        } catch (Exception $e) {
            $langs = null;
            dcPage::addErrorNotice($e->getMessage());
        }
        $lang_combo = array_merge(
            ['-' => ''],
            dcAdminCombos::getLangsCombo($langs, false)
        );

        $status_combo = array_merge(
            ['-' => ''],
            dcAdminCombos::getPostStatusesCombo()
        );

        $in_widget_combo = [
            '-' => '',
            __('yes') => 1,
            __('no') => 0
        ];

        // Getting dates
        try {
            $dates = dcCore::app()->blog->getDates(['type' => 'month']);
        } catch (Exception $e) {
            $dates = null;
            dcPage::addErrorNotice($e->getMessage());
        }

        // Months array
        $dt_m_combo = array_merge(
            ['-' => ''],
            dcAdminCombos::getDatesCombo($dates)
        );

        $sortby_combo = [
            __('Date') => 'post_dt',
            __('Title') => 'post_title',
            __('Author') => 'user_id',
            __('Status') => 'post_status',
            __('Visible pages in widget') => 'post_selected'
        ];

        $order_combo = [
            __('Descending') => 'desc',
            __('Ascending') => 'asc'
        ];
    }

    $page = !empty($_GET['page']) ? $_GET['page'] : 1;
    $nb_per_page = 30;
    if (!empty($_GET['nb']) && (int) $_GET['nb'] > 0) {
        $nb_per_page = (int) $_GET['nb'];
    }

    $params['limit'] = [(($page - 1) * $nb_per_page), $nb_per_page];
    $params['no_content'] = true;
    $params['order'] = 'post_position asc';

    // Get pages
    try {
        $pages = dcCore::app()->blog->getPosts($params);
        $pages->extend("rsRelated");
        $counter = dcCore::app()->blog->getPosts($params, true);
        $page_list = new adminPageList(dcCore::app(), $pages, $counter->f(0));
    } catch (Exception $e) {
        $pages = null;
        dcPage::addErrorNotice($e->getMessage());
    }

    // apply filters
    // - User filter
    if ($user_id !== '' && in_array($user_id, $users_combo)) {
        $params['user_id'] = $user_id;
        $show_filters = true;
    } else {
        $user_id = '';
    }

    // - Status filter
    if ($status !== '' && in_array($status, $status_combo)) {
        $params['post_status'] = $status;
        $show_filters = true;
    } else {
        $status = '';
    }

    // - in widget filter
    if ($in_widget !== '' && in_array($in_widget, $in_widget_combo)) {
        $params['post_selected'] = $in_widget;
        $show_filters = true;
    } else {
        $in_widget = '';
    }

    // - Month filter
    if ($month !== '' && in_array($month, $dt_m_combo)) {
        $params['post_month'] = substr($month, 4, 2);
        $params['post_year'] = substr($month, 0, 4);
        $show_filters = true;
    } else {
        $month = '';
    }

    // - Lang filter
    if ($lang !== '' && in_array($lang, $lang_combo)) {
        $params['post_lang'] = $lang;
        $show_filters = true;
    } else {
        $lang = '';
    }

    // - Sortby and order filter
    if ($sortby !== '' && in_array($sortby, $sortby_combo)) {
        if ($order !== '' && in_array($order, $order_combo)) {
            $params['order'] = $sortby . ' ' . $order;
        } else {
            $order = 'desc';
        }

        if ($sortby != 'post_dt' || $order != 'desc') {
            $show_filters = true;
        }
    } else {
        $sortby = 'post_dt';
        $order = 'desc';
    }

    // Get pages
    try {
        $pages = dcCore::app()->blog->getPosts($params);
        $pages->extend('rsRelated');
        $counter = dcCore::app()->blog->getPosts($params, true);
        $page_list = new adminPageList(dcCore::app(), $pages, $counter->f(0));
    } catch (Exception $e) {
        dcPage::addErrorNotice($e->getMessage());
    }

    // Actions combo box
    $combo_action = [];
    if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_PUBLISH, dcAuth::PERMISSION_CONTENT_ADMIN]), dcCore::app()->blog->id)) {
        $combo_action[__('publish')] = 'publish';
        $combo_action[__('unpublish')] = 'unpublish';
        $combo_action[__('mark as pending')] = 'pending';
    }
    if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_ADMIN]), dcCore::app()->blog->id)) {
        $combo_action[__('change author')] = 'author';
    }
    if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_DELETE, dcAuth::PERMISSION_CONTENT_ADMIN]), dcCore::app()->blog->id)) {
        $combo_action[__('delete')] = 'delete';
    }
    $combo_action[__('Widget')] = [
        __('Add to widget') => 'selected',
        __('Remove from widget') => 'unselected'
    ];

    // --BEHAVIOR-- adminPagesActionsCombo
    dcCore::app()->callBehavior('adminPagesActionsCombo', [&$combo_action]);

    $pages_actions_page = new relatedPagesActionsPage(dcCore::app(), 'plugin.php', ['p' => 'related']);
    if (!$pages_actions_page->process()) {
        $process_successfull = false;
    } else {
        $process_successfull = true;
    }

    /**
     * Manage public list if requested
     */
    $all_pages = dcCore::app()->blog->getPosts(['post_type' => 'related']);
    $all_pages->extend('rsRelated');

    if (isset($_POST['pages_upd'])) {
        $default_tab = 'pages_order';

        $public_pages = relatedHelpers::getPublicList($pages);
        $visible = (!empty($_POST['p_visibles']) && is_array($_POST['p_visibles'])) ? $_POST['p_visibles'] : [];
        $order = (!empty($_POST['p_order'])) ? $_POST['p_order'] : [];

        try {
            $i = 1;
            $meta = new dcMeta();
            foreach ($public_pages as $c_page) {
                $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . 'post');
                $cur->post_upddt = date('Y-m-d H:i:s');
                $cur->post_selected = (int)in_array($c_page['id'], $visible);
                $cur->update('WHERE post_id = ' . $c_page['id']);


                if (count($order) > 0) {
                    $pos = !empty($order[$c_page['id']]) ? $order[$c_page['id']] + 1 : 1;
                    $pos = (int)$pos + 1;
                    $meta->delPostMeta($c_page['id'], 'related_position');
                    $meta->setPostMeta($c_page['id'], 'related_position', $pos);
                }
            }
            dcCore::app()->blog->triggerBlog();
            http::redirect(dcCore::app()->admin->getPageURL() . '&reord=1');
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
    include_once(__DIR__ . '/../tpl/index.tpl');
}
