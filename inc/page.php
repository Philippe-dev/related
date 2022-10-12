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

$post_id = '';
$cat_id = '';
$post_dt = '';
$post_type = 'related';
$post_format = dcCore::app()->auth->getOption('post_format');
$post_editor = dcCore::app()->auth->getOption('editor');
$post_password = '';
$post_url = '';
$post_lang = dcCore::app()->auth->getInfo('user_lang');
$post_title = '';
$post_excerpt = '';
$post_excerpt_xhtml = '';
$post_content = '';
$post_content_xhtml = '';
$post_notes = '';
$post_status = dcCore::app()->auth->getInfo('user_post_status');
$post_selected = true;
$post_open_comment = false;
$post_open_tb = false;

$page_title = __('New page');

$can_view_page = true;
$can_edit_post = dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcPages::PERMISSION_PAGES, dcAuth::PERMISSION_CONTENT_ADMIN]), dcCore::app()->blog->id);
$can_publish = dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_CONTENT_ADMIN]), dcCore::app()->blog->id);
$can_delete = false;
$preview = false;

// If user can't publish
if (!$can_publish) {
    $post_status = dcBlog::POST_PENDING;
}

// Status combo
foreach (dcCore::app()->blog->getAllPostStatus() as $k => $v) {
    $status_combo[$v] = (string) $k;
}
$img_status_pattern = '<img class="img_select_option" alt="%1$s" title="%1$s" src="images/%2$s" />';
$img_status = '';

// Languages combo
$rs = dcCore::app()->blog->getLangs(['order' => 'asc']);
$lang_combo = dcAdminCombos::getLangsCombo($rs, true);

// Formaters combo
if (version_compare(DC_VERSION, '2.7-dev', '>=')) {
    $core_formaters = dcCore::app()->getFormaters();
    $available_formats = ['' => ''];
    foreach ($core_formaters as $editor => $formats) {
        foreach ($formats as $format) {
            $available_formats[$format] = $format;
        }
    }
} else {
    foreach (dcCore::app()->getFormaters() as $v) {
        $available_formats[$v] = $v;
    }
}

// Validation flag
$bad_dt = false;

$page_isfile = (!empty($_REQUEST['st']) && $_REQUEST['st'] === 'file') ? true : false;
$page_relatedfile = '';

// Get entry informations
if (!empty($_REQUEST['id'])) {
    $params = [];
    $params['post_id'] = $_REQUEST['id'];
    $params['post_type'] = 'related';

    $post = dcCore::app()->blog->getPosts($params, false);
    $post->extend('rsRelated');

    if ($post->isEmpty()) {
        dcPage::addErrorNotice(__('This page does not exist.'));
        $can_view_page = false;
    } else {
        $post_id = $post->post_id;
        $cat_id = $post->cat_id;
        $post_dt = date('Y-m-d H:i', strtotime($post->post_dt));
        $post_format = $post->post_format;
        $post_password = $post->post_password;
        $post_url = $post->post_url;
        $post_lang = $post->post_lang;
        $post_title = $post->post_title;
        $post_excerpt = $post->post_excerpt;
        $post_excerpt_xhtml = $post->post_excerpt_xhtml;
        $post_content = $post->post_content;
        $post_content_xhtml = $post->post_content_xhtml;
        $post_notes = $post->post_notes;
        $post_status = $post->post_status;
        $post_selected = (boolean) $post->post_selected;
        $post_open_comment = false;
        $post_open_tb = false;

        $page_title = __('Edit page');

        $can_edit_post = $post->isEditable();
        $can_delete = $post->isDeletable();

        try {
            dcCore::app()->meta = new dcMeta(dcCore::app());
            $post_metas = dcCore::app()->meta->getMetaRecordset($post->post_meta, 'related_file');
            if (!$post_metas->isEmpty()) {
                $page_relatedfile = $post_metas->meta_id;
                $page_isfile = true;
            }
        } catch (Exception $e) {
        }
    }
}

if ($page_isfile) {
    $post_content = '/** external content **/';
    $post_content_xhtml = '/** external content **/';

    $related_pages_files = ['-' => ''];
    $dir = @dir(dcCore::app()->blog->settings->related->files_path);
    $allowed_exts = ['php', 'html', 'xml', 'txt'];

    if ($dir) {
        while (($entry = $dir->read()) !== false) {
            $entry_path = $dir->path . '/' . $entry;
            if (in_array(files::getExtension($entry), $allowed_exts)) {
                if (is_file($entry_path) && is_readable($entry_path)) {
                    $related_pages_files[$entry] = $entry;
                }
            }
        }
    }
}

// Format excerpt and content
if (!empty($_POST) && $can_edit_post) {
    $post_format = $_POST['post_format'];
    $post_excerpt = $_POST['post_excerpt'];
    if (!$page_isfile) {
        $post_content = $_POST['post_content'];
    }

    $post_title = $_POST['post_title'];

    if (isset($_POST['post_status'])) {
        $post_status = (integer) $_POST['post_status'];
    }

    if (empty($_POST['post_dt'])) {
        $post_dt = '';
    } else {
        $post_dt = strtotime($_POST['post_dt']);
        $post_dt = date('Y-m-d H:i', $post_dt);
    }

    $post_lang = $_POST['post_lang'];
    $post_password = !empty($_POST['post_password']) ? $_POST['post_password'] : null;

    $post_notes = $_POST['post_notes'];

    if (isset($_POST['post_url'])) {
        $post_url = $_POST['post_url'];
    }

    dcCore::app()->blog->setPostContent(
        $post_id,
        $post_format,
        $post_lang,
        $post_excerpt,
        $post_excerpt_xhtml,
        $post_content,
        $post_content_xhtml
    );

    $preview = !empty($_POST['preview']);

    if ($page_isfile) {
        $related_upl = null;
        if (!empty($_FILES['up_file']['name'])) {
            $related_upl = true;
        } elseif (!empty($_POST['repository_file']) && in_array($_POST['repository_file'], $related_pages_files)) {
            $related_upl = false;
        }

        if ($related_upl !== null) {
            try {
                if ($related_upl) {
                    files::uploadStatus($_FILES['up_file']);
                    $src_file = $_FILES['up_file']['tmp_name'];
                    $trg_file = dcCore::app()->blog->settings->related->files_path . '/' . $_FILES['up_file']['name'];
                    if (move_uploaded_file($src_file, $trg_file)) {
                        $page_relatedfile = $_FILES['up_file']['name'];
                    }
                } else {
                    $page_relatedfile = $_POST['repository_file'];
                }
            } catch (Exception $e) {
                dcPage::addErrorNotice($e->getMessage());
            }
        }
    }
}

// Create or update post
if (!empty($_POST) && !empty($_POST['save']) && $can_edit_post) {
    $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . 'post');

    $cur->post_title = $post_title;
    $cur->cat_id = null;
    $cur->post_dt = $post_dt ? date('Y-m-d H:i:00', strtotime($post_dt)) : '';
    $cur->post_type = $post_type;
    $cur->post_format = $post_format;
    $cur->post_password = $post_password;
    $cur->post_lang = $post_lang;
    $cur->post_type = $post_type;
    $cur->post_excerpt = $post_excerpt;
    $cur->post_excerpt_xhtml = $post_excerpt_xhtml;
    $cur->post_content = $post_content;
    $cur->post_content_xhtml = $post_content_xhtml;
    $cur->post_notes = $post_notes;
    $cur->post_status = $post_status;
    $cur->post_selected = (integer)$post_selected;
    $cur->post_open_comment = 0;
    $cur->post_open_tb = 0;

    if (isset($_POST['post_url'])) {
        $cur->post_url = $post_url;
    }

    // Update post
    if ($post_id) {
        switch ($post_status) {
            case dcBlog::POST_PUBLISHED:
                $img_status = sprintf($img_status_pattern, __('Published'), 'check-on.png');
                break;
            case dcBlog::POST_UNPUBLISHED:
                $img_status = sprintf($img_status_pattern, __('Unpublished'), 'check-off.png');
                break;
            case dcBlog::POST_SCHEDULED:
                $img_status = sprintf($img_status_pattern, __('Scheduled'), 'scheduled.png');
                break;
            case dcBlog::POST_PENDING:
                $img_status = sprintf($img_status_pattern, __('Pending'), 'check-wrn.png');
                break;
            default:
                $img_status = '';
        }

        try {
            if ($page_isfile && empty($page_relatedfile)) {
                throw new Exception(__('Missing file.'));
            }

            // --BEHAVIOR-- adminBeforePostUpdate
            dcCore::app()->callBehavior('adminBeforePostUpdate', $cur, $post_id);
            // --BEHAVIOR-- adminBeforePageUpdate
            dcCore::app()->callBehavior('adminBeforePageUpdate', $cur, $post_id);

            dcCore::app()->con->begin();
            dcCore::app()->blog->updPost($post_id, $cur);
            if ($page_isfile) {
                try {
                    if (dcCore::app()->meta === null) {
                        dcCore::app()->meta = new dcMeta(dcCore::app());
                    }
                    dcCore::app()->meta->delPostMeta($post_id, 'related_file');
                    dcCore::app()->meta->setPostMeta($post_id, 'related_file', $page_relatedfile);
                } catch (Exception $e) {
                    dcCore::app()->con->rollback();
                    throw $e;
                }
            }
            dcCore::app()->con->commit();

            // --BEHAVIOR-- adminAfterPostUpdate
            dcCore::app()->callBehavior('adminAfterPostUpdate', $cur, $post_id);
            // --BEHAVIOR-- adminAfterPageUpdate
            dcCore::app()->callBehavior('adminAfterPageUpdate', $cur, $post_id);

            dcPage::addSuccessNotice(__('Page has been updated.'));
            http::redirect('plugin.php?p=related&do=edit&id=' . $post_id);
        } catch (Exception $e) {
            dcPage::addErrorNotice($e->getMessage());
        }
    } else {
        $cur->user_id = dcCore::app()->auth->userID();
        if (!isset($_POST['post_url'])) {
            $cur->post_url = text::str2URL($post_title);
        }

        try {
            if ($page_isfile && empty($page_relatedfile)) {
                throw new Exception(__('Missing file.'));
            }

            // --BEHAVIOR-- adminBeforePostCreate
            dcCore::app()->callBehavior('adminBeforePostCreate', $cur);
            // --BEHAVIOR-- adminBeforePageCreate
            dcCore::app()->callBehavior('adminBeforePageCreate', $cur);

            dcCore::app()->con->begin();
            $return_id = dcCore::app()->blog->addPost($cur);
            if ($page_isfile) {
                try {
                    if (dcCore::app()->meta === null) {
                        dcCore::app()->meta = new dcMeta(dcCore::app());
                    }
                    dcCore::app()->meta->setPostMeta($return_id, 'related_file', $page_relatedfile);
                } catch (Exception $e) {
                    dcCore::app()->con->rollback();
                    throw $e;
                }
            }
            dcCore::app()->con->commit();

            // --BEHAVIOR-- adminAfterPostCreate
            dcCore::app()->callBehavior('adminAfterPostCreate', $cur, $return_id);
            // --BEHAVIOR-- adminAfterPageCreate
            dcCore::app()->callBehavior('adminAfterPageCreate', $cur, $return_id);

            dcPage::addSuccessNotice(__('Page has been created.'));
            http::redirect('plugin.php?p=related&do=edit&id=' . $return_id);
        } catch (Exception $e) {
            dcPage::addErrorNotice($e->getMessage());
        }
    }
}

if (!empty($_POST['delete']) && $can_delete) {
    try {
        dcCore::app()->blog->delPost($post_id);
        http::redirect(dcCore::app()->admin->getPageURL());
    } catch (Exception $e) {
        dcPage::addErrorNotice($e->getMessage());
    }
}

/* DISPLAY
-------------------------------------------------------- */
$default_tab = 'edit-entry';
if (!$can_edit_post || !empty($_POST['preview'])) {
    $default_tab = 'preview-entry';
}
$admin_post_behavior = '';
if ($post_editor && !empty($post_editor[$post_format])) {
    $admin_post_behavior = dcCore::app()->callBehavior(
        'adminPostEditor',
        $post_editor[$post_format],
        'related',
        ['#post_content', '#post_excerpt']
    );
}

if ($post_id) {
    switch ($post_status) {
        case dcBlog::POST_PUBLISHED:
            $img_status = sprintf($img_status_pattern, __('Published'), 'check-on.png');
            break;
        case dcBlog::POST_UNPUBLISHED:
            $img_status = sprintf($img_status_pattern, __('Unpublished'), 'check-off.png');
            break;
        case dcBlog::POST_SCHEDULED:
            $img_status = sprintf($img_status_pattern, __('Scheduled'), 'scheduled.png');
            break;
        case dcBlog::POST_PENDING:
            $img_status = sprintf($img_status_pattern, __('Pending'), 'check-wrn.png');
            break;
        default:
            $img_status = '';
    }
    $edit_entry_str = __('&ldquo;%s&rdquo;');
    $page_title_edit = sprintf($edit_entry_str, html::escapeHTML($post_title)) . ' ' . $img_status;
} else {
    $img_status = '';
}
?>
<html>
<head>
	<title><?php echo __('Related pages');?></title>
<?php
echo dcPage::jsModal() .
    $admin_post_behavior .
	dcPage::jsLoad('js/_post.js') .
	dcPage::jsConfirmClose('entry-form') .
	// --BEHAVIOR-- adminRelatedHeaders
	dcCore::app()->callBehavior('adminRelatedHeaders') .
	dcPage::jsPageTabs($default_tab);

if (!empty($_GET['xconv'])) {
    $post_excerpt = $post_excerpt_xhtml;
    $post_content = $post_content_xhtml;
    $post_format = 'xhtml';

    dcPage::addSuccessNotice(__('Don\'t forget to validate your XHTML conversion by saving your post.'));
}
?>
</head>
<body>
<?php
echo dcPage::breadcrumb(
    [
        html::escapeHTML(dcCore::app()->blog->name) => '',
        __('Related pages') => dcCore::app()->admin->getPageURL(),
        ($post_id ? $page_title_edit : $page_title) => ''
    ]
);

echo dcPage::notices();

// Exit if we cannot view page
if (!$can_view_page) {
    exit;
}

/* Page form if we can edit page
-------------------------------------------------------- */
if ($can_edit_post) {
    $sidebar_items = new ArrayObject([
        'status-box' => [
            'title' => __('Status'),
            'items' => [
                'post_status' => '<p class="entry-status"><label for="post_status">' . __('Page status') . ' ' . $img_status . '</label>' .
                form::combo('post_status', $status_combo, $post_status, 'maximal', '', !$can_publish) .
                '</p>',
                'post_dt' => '<p><label for="post_dt">' . __('Publication date and hour') . '</label>' .
                form::field('post_dt', 16, 16, $post_dt, ($bad_dt ? 'invalid' : '')) .
                '</p>',
                'post_lang' => '<p><label for="post_lang">' . __('Entry language') . '</label>' .
                form::combo('post_lang', $lang_combo, $post_lang) .
                '</p>',
                'post_format' => '<div>' .
                '<h5 id="label_format"><label for="post_format" class="classic">' . __('Text formatting') . '</label></h5>' .
                '<p>' . form::combo('post_format', $available_formats, $post_format, 'maximal') . '</p>' .
                '<p class="format_control control_no_xhtml">' .
                '<a id="convert-xhtml" class="button' . ($post_id && $post_format != 'wiki' ? ' hide' : '') . '" href="' .
                dcCore::app()->adminurl->get('admin.plugin.related', ['do' => 'edit', 'id' => $post_id, 'xconv' => '1']) .
                '">' .
                __('Convert to XHTML') . '</a></p></div>']],
        'options-box' => [
            'title' => __('Options'),
            'items' => [
                'post_password' => '<p><label for="post_password">' . __('Password') . '</label>' .
                form::field('post_password', 10, 32, html::escapeHTML($post_password), 'maximal') .
                '</p>',
                'post_url' => '<div class="lockable">' .
                '<p><label for="post_url">' . __('Edit basename') . '</label>' .
                form::field('post_url', 10, 255, html::escapeHTML($post_url), 'maximal') .
                '</p>' .
                '<p class="form-note warn">' .
                __('Warning: If you set the URL manually, it may conflict with another entry.') .
                '</p></div>'
            ]]]);

    $main_items = new ArrayObject([
        "post_title" => '<p class="col">' .
        '<label class="required no-margin bold" for="post_title"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label>' .
        form::field('post_title', 20, 255, html::escapeHTML($post_title), 'maximal') .
        '</p>',

        "post_excerpt" => '<p class="area" id="excerpt-area"><label for="post_excerpt" class="bold">' . __('Excerpt:') . ' <span class="form-note">' .
        __('Introduction to the post.') . '</span></label> ' .
        form::textarea('post_excerpt', 50, 5, html::escapeHTML($post_excerpt)) .
        '</p>'
    ]);

    if (!$page_isfile) {
        $main_items['post_content'] = '<p class="area" id="content-area"><label class="required bold" ' .
			'for="post_content"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Content:') . '</label> ' .
			form::textarea('post_content', 50, dcCore::app()->auth->getOption('edit_size'), html::escapeHTML($post_content)) .
			'</p>';
    } else {
        $main_items['post_content'] = '<div style="display:none">' .
			form::textarea('post_content', 1, 1, html::escapeHTML($post_content)) .
			'</div>';
        $main_items['is_file'] = '<p class="col"><label class="required" title="' . __('Required field') . '" ' .
            'for="repository_file">' . __('Included file:') .
            dcPage::helpBlock('post', 'page_relatedfile') . '</label></p>' .
            '<div class="fieldset">' .
            '<p><label>' . __('Pick up a local file in your related pages repository') . ' ' .
            form::combo('repository_file', $related_pages_files, $page_relatedfile) .
            '</label></p>' .
            form::hidden(['MAX_FILE_SIZE'], DC_MAX_UPLOAD_SIZE) .
            '<p><label>' . __('or upload a new file') . ' ' .
            '<input type="file" id="up_file" name="up_file" />' .
            '</label></p>' .
            '</div>' .
            form::hidden('st', 'file');
    }

    $main_items["post_notes"] = '<p class="area" id="notes-area">' .
        '<label for="post_notes" class="bold">' . __('Personal notes:') . ' <span class="form-note">' .
        __('Unpublished notes.') . '</span></label>' .
        form::textarea('post_notes', 50, 5, html::escapeHTML($post_notes)) .
        '</p>';

    if ($post_id && $post->post_status === dcBlog::POST_PUBLISHED) {
        echo '<p><a class="onblog_link outgoing" href="' . $post->getURL() . '" title="' . $post_title . '">' . __('Go to this related page on the site') . ' <img src="images/outgoing-blue.png" alt="" /></a></p>';
    }

    echo '<div class="multi-part" title="' . ($post_id ? __('Edit page') : __('New page')) . '" id="edit-entry">';
    echo '<form action="plugin.php?p=related&amp;do=edit" method="post" id="entry-form" enctype="multipart/form-data">';
    echo '<div id="entry-wrapper">';
    echo '<div id="entry-content"><div class="constrained">';

    echo '<h3 class="out-of-screen-if-js">' . __('Edit post') . '</h3>';

    foreach ($main_items as $id => $item) {
        echo $item;
    }

    // --BEHAVIOR-- adminPostForm (may be deprecated)
    dcCore::app()->callBehavior('adminPostForm', isset($post) ? $post : null);

    echo
	'<p class="border-top">' .
	($post_id ? form::hidden('id', $post_id) : '') .
	'<input type="submit" value="' . __('Save') . ' (s)" ' .
	'accesskey="s" name="save" /> ';
    if ($post_id) {
        $preview_url =
        dcCore::app()->blog->url . dcCore::app()->url->getURLFor('relatedpreview', dcCore::app()->auth->userID() . '/' .
        http::browserUID(DC_MASTER_KEY . dcCore::app()->auth->userID() . dcCore::app()->auth->getInfo('user_pwd')) .
        '/' . $post->post_url);
        echo '<a id="post-preview" href="' . $preview_url . '" class="button modal" accesskey="p">' . __('Preview') . ' (p)' . '</a> ';
    } else {
        echo
        '<a id="post-cancel" href="' . dcCore::app()->adminurl->get("admin.home") . '" class="button" accesskey="c">' . __('Cancel') . ' (c)</a>';
    }

    echo
        ($can_delete ? '<input type="submit" class="delete" value="' . __('Delete') . '" name="delete" />' : '') .
        dcCore::app()->formNonce() .
        '</p>';

    echo '</div></div>';		// End #entry-content
    echo '</div>';		// End #entry-wrapper

    echo '<div id="entry-sidebar" role="complementary">';

    foreach ($sidebar_items as $id => $c) {
        echo '<div id="' . $id . '" class="sb-box">';
        if (!empty($c['title'])) {
            echo '<h4>' . $c['title'] . '</h4>';
        }
        foreach ($c['items'] as $e_name => $e_content) {
            echo $e_content;
        }
        echo '</div>';
    }


    // --BEHAVIOR-- adminPostFormSidebar (may be deprecated)
    dcCore::app()->callBehavior('adminPostFormSidebar', isset($post) ? $post : null);
    echo '</div>';		// End #entry-sidebar

    echo '</form>';

    // --BEHAVIOR-- adminPostForm
    dcCore::app()->callBehavior('adminPostAfterForm', isset($post) ? $post : null);

    echo '</div>';
}
dcPage::helpBlock('related_pages_edit', 'core_wiki');
?>
	</body>
</html>
