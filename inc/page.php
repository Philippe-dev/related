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

$post_id = '';
$cat_id = '';
$post_dt = '';
$post_type = 'related';
$post_format = $core->auth->getOption('post_format');
$post_editor = $core->auth->getOption('editor');
$post_password = '';
$post_url = '';
$post_lang = $core->auth->getInfo('user_lang');
$post_title = '';
$post_excerpt = '';
$post_excerpt_xhtml = '';
$post_content = '';
$post_content_xhtml = '';
$post_notes = '';
$post_status = $core->auth->getInfo('user_post_status');
$post_selected = true;
$post_open_comment = false;
$post_open_tb = false;

$page_title = __('New page');

$can_view_page = true;
$can_edit_post = $core->auth->check('contentadmin,pages',$core->blog->id);
$can_publish = $core->auth->check('contentadmin',$core->blog->id);
$can_delete = false;
$preview = false;

# If user can't publish
if (!$can_publish) {
	$post_status = -2;
}

# Status combo
foreach ($core->blog->getAllPostStatus() as $k => $v) {
	$status_combo[$v] = (string) $k;
}
$img_status_pattern = '<img class="img_select_option" alt="%1$s" title="%1$s" src="images/%2$s" />';
$img_status = '';

# Languages combo
$rs = $core->blog->getLangs(array('order'=>'asc'));
$lang_combo = dcAdminCombos::getLangsCombo($rs,true);

# Formaters combo
if (version_compare(DC_VERSION, '2.7-dev', '>=')) {
    $core_formaters = $core->getFormaters();
    $available_formats = array('' => '');
    foreach ($core_formaters as $editor => $formats) {
        foreach ($formats as $format) {
            $available_formats[$format] = $format;
        }
    }
} else {
    foreach ($core->getFormaters() as $v) {
        $available_formats[$v] = $v;
    }
}

# Validation flag
$bad_dt = false;

$page_isfile = (!empty($_REQUEST['st']) && $_REQUEST['st'] == 'file')?true:false;
$page_relatedfile = '';

# Get entry informations
if (!empty($_REQUEST['id']))
{
	$params = array();
	$params['post_id'] = $_REQUEST['id'];
	$params['post_type'] = 'related';

	$post = $core->blog->getPosts($params, false);
	$post->extend("rsRelated");

	if ($post->isEmpty())
	{
		$core->error->add(__('This page does not exist.'));
		$can_view_page = false;
	}
	else
	{
		$post_id = $post->post_id;
		$cat_id = $post->cat_id;
		$post_dt = date('Y-m-d H:i',strtotime($post->post_dt));
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
			$core->meta = new dcMeta($core);
			$post_metas = $core->meta->getMetaRecordset($post->post_meta, 'related_file');
			if (!$post_metas->isEmpty()) {
				$page_relatedfile = $post_metas->meta_id;
				$page_isfile = true;
			}
		} catch (Exception $e) {}

	}
}

if ($page_isfile) {
	$post_content = '/** external content **/';
	$post_content_xhtml = '/** external content **/';

	$related_pages_files = array('-' => '');
	$dir = @dir($core->blog->settings->related->files_path);
	$allowed_exts = array('php','html','xml','txt');

	if ($dir)
	{
		while (($entry = $dir->read()) !== false) {
			$entry_path = $dir->path.'/'.$entry;
			if (in_array(files::getExtension($entry),$allowed_exts)) {
				if (is_file($entry_path) && is_readable($entry_path)) {
					$related_pages_files[$entry] = $entry;
				}
			}
		}
	}
}

# Format excerpt and content
if (!empty($_POST) && $can_edit_post)
{
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
		$post_dt = date('Y-m-d H:i',$post_dt);
	}

	$post_lang = $_POST['post_lang'];
	$post_password = !empty($_POST['post_password']) ? $_POST['post_password'] : null;

	$post_notes = $_POST['post_notes'];

	if (isset($_POST['post_url'])) {
		$post_url = $_POST['post_url'];
	}

	$core->blog->setPostContent(
		$post_id,$post_format,$post_lang,
		$post_excerpt,$post_excerpt_xhtml,$post_content,$post_content_xhtml
	);

	$preview = !empty($_POST['preview']);

	if ($page_isfile)
	{
		$related_upl = null;
		if (!empty($_FILES['up_file']['name'])) {
			$related_upl = true;
		} elseif (!empty($_POST['repository_file']) && in_array($_POST['repository_file'],$related_pages_files)) {
			$related_upl = false;
		}

		if ($related_upl !== null) {
			try
			{
				if ($related_upl) {
					files::uploadStatus($_FILES['up_file']);
					$src_file = $_FILES['up_file']['tmp_name'];
					$trg_file = $core->blog->settings->related->files_path.'/'.$_FILES['up_file']['name'];
					if (move_uploaded_file($src_file,$trg_file)) {
						$page_relatedfile = $_FILES['up_file']['name'];
					}
				} else {
					$page_relatedfile = $_POST['repository_file'];
				}
			}
			catch (Exception $e)
			{
				$core->error->add($e->getMessage());
			}
		}
	}
}

# Create or update post
if (!empty($_POST) && !empty($_POST['save']) && $can_edit_post)
{
	$cur = $core->con->openCursor($core->prefix.'post');

	$cur->post_title = $post_title;
	$cur->cat_id = null;
	$cur->post_dt = $post_dt ? date('Y-m-d H:i:00',strtotime($post_dt)) : '';
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

	# Update post
	if ($post_id) {
        switch ($post_status) {
		case 1:
			$img_status = sprintf($img_status_pattern,__('Published'),'check-on.png');
			break;
		case 0:
			$img_status = sprintf($img_status_pattern,__('Unpublished'),'check-off.png');
			break;
		case -1:
			$img_status = sprintf($img_status_pattern,__('Scheduled'),'scheduled.png');
			break;
		case -2:
			$img_status = sprintf($img_status_pattern,__('Pending'),'check-wrn.png');
			break;
		default:
			$img_status = '';
        }

		try
		{
			if ($page_isfile && empty($page_relatedfile)) {
				throw new Exception(__('Missing file.'));
			}

			# --BEHAVIOR-- adminBeforePostUpdate
			$core->callBehavior('adminBeforePostUpdate',$cur,$post_id);
			# --BEHAVIOR-- adminBeforePageUpdate
			$core->callBehavior('adminBeforePageUpdate',$cur,$post_id);

			$core->con->begin();
			$core->blog->updPost($post_id,$cur);
			if ($page_isfile) {
				try {
					if ($core->meta === null) {
						$core->meta = new dcMeta($core);
					}
					$core->meta->delPostMeta($post_id, 'related_file');
					$core->meta->setPostMeta($post_id, 'related_file', $page_relatedfile);
				}
				catch (Exception $e) {
					$core->con->rollback();
					throw $e;
				}
			}
			$core->con->commit();

			# --BEHAVIOR-- adminAfterPostUpdate
			$core->callBehavior('adminAfterPostUpdate',$cur,$post_id);
			# --BEHAVIOR-- adminAfterPageUpdate
			$core->callBehavior('adminAfterPageUpdate',$cur,$post_id);

			http::redirect('plugin.php?p=related&do=edit&id='.$post_id.'&upd=1');
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	else
	{
		$cur->user_id = $core->auth->userID();
		if (!isset($_POST['post_url'])) {
			$cur->post_url = text::str2URL($post_title);
		}

		try
		{
			if ($page_isfile && empty($page_relatedfile)) {
				throw new Exception(__('Missing file.'));
			}

			# --BEHAVIOR-- adminBeforePostCreate
			$core->callBehavior('adminBeforePostCreate',$cur);
			# --BEHAVIOR-- adminBeforePageCreate
			$core->callBehavior('adminBeforePageCreate',$cur);

			$core->con->begin();
			$return_id = $core->blog->addPost($cur);
			if ($page_isfile) {
				try {
					if ($core->meta === null) {
						$core->meta = new dcMeta($core);
					}
					$core->meta->setPostMeta($return_id, 'related_file', $page_relatedfile);
				}
				catch (Exception $e) {
					$core->con->rollback();
					throw $e;
				}
			}
			$core->con->commit();

			# --BEHAVIOR-- adminAfterPostCreate
			$core->callBehavior('adminAfterPostCreate',$cur,$return_id);
			# --BEHAVIOR-- adminAfterPageCreate
			$core->callBehavior('adminAfterPageCreate',$cur,$return_id);

			http::redirect('plugin.php?p=related&do=edit&id='.$return_id.'&crea=1');
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
}

if (!empty($_POST['delete']) && $can_delete)
{
	try {
		$core->blog->delPost($post_id);
		http::redirect($p_url);
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
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
	$admin_post_behavior = $core->callBehavior('adminPostEditor', $post_editor[$post_format],
                                               'related', array('#post_content', '#post_excerpt')
    );
}

if ($post_id) {
	switch ($post_status) {
		case 1:
			$img_status = sprintf($img_status_pattern,__('Published'),'check-on.png');
			break;
		case 0:
			$img_status = sprintf($img_status_pattern,__('Unpublished'),'check-off.png');
			break;
		case -1:
			$img_status = sprintf($img_status_pattern,__('Scheduled'),'scheduled.png');
			break;
		case -2:
			$img_status = sprintf($img_status_pattern,__('Pending'),'check-wrn.png');
			break;
		default:
			$img_status = '';
	}
	$edit_entry_str = __('&ldquo;%s&rdquo;');
	$page_title_edit = sprintf($edit_entry_str, html::escapeHTML($post_title)).' '.$img_status;
} else {
	$img_status = '';
}

?>
<html>
<head>
	<title><?php echo __('Related pages'); ?></title>
<?php
echo dcPage::jsDatePicker().
	dcPage::jsToolBar().
  	dcPage::jsModal().
    $admin_post_behavior.
	dcPage::jsLoad('js/_post.js').
	dcPage::jsConfirmClose('entry-form').
	# --BEHAVIOR-- adminRelatedHeaders
	$core->callBehavior('adminRelatedHeaders').
	dcPage::jsPageTabs($default_tab);
?>
</head>

<body>
<?php
if (!empty($_GET['upd'])) {
		echo '<p class="message">'.__('Page has been updated.').'</p>';
}
elseif (!empty($_GET['crea'])) {
		echo '<p class="message">'.__('Page has been created.').'</p>';
}

echo dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			__('Related pages') => $p_url,
			($post_id ? $page_title_edit : $page_title) => ''
		));

# Exit if we cannot view page
if (!$can_view_page) {
	exit;
}

/* Page form if we can edit page
-------------------------------------------------------- */
if ($can_edit_post) {
	$sidebar_items = new ArrayObject(array(
		'status-box' => array(
			'title' => __('Status'),
			'items' => array(
				'post_status' =>
					'<p class="entry-status"><label for="post_status">'.__('Page status').' '.$img_status.'</label>'.
					form::combo('post_status',$status_combo,$post_status,'maximal','',!$can_publish).
					'</p>',
				'post_dt' =>
					'<p><label for="post_dt">'.__('Publication date and hour').'</label>'.
					form::field('post_dt',16,16,$post_dt,($bad_dt ? 'invalid' : '')).
					'</p>',
				'post_lang' =>
					'<p><label for="post_lang">'.__('Entry language').'</label>'.
					form::combo('post_lang',$lang_combo,$post_lang).
					'</p>',
				'post_format' =>
					'<div>'.
					'<h5 id="label_format"><label for="post_format" class="classic">'.__('Text formatting').'</label></h5>'.
					'<p>'.form::combo('post_format',$available_formats,$post_format,'maximal').'</p>'.
					'<p class="format_control control_no_xhtml">'.
					'<a id="convert-xhtml" class="button'.($post_id && $post_format != 'wiki' ? ' hide' : '').'" href="'.
					$core->adminurl->get('admin.post',array('id'=> $post_id,'xconv'=> '1')).
					'">'.
					__('Convert to XHTML').'</a></p></div>')),
		'options-box' => array(
			'title' => __('Options'),
			'items' => array(
				'post_password' =>
					'<p><label for="post_password">'.__('Password').'</label>'.
					form::field('post_password',10,32,html::escapeHTML($post_password),'maximal').
					'</p>',
				'post_url' =>
					'<div class="lockable">'.
					'<p><label for="post_url">'.__('Edit basename').'</label>'.
					form::field('post_url',10,255,html::escapeHTML($post_url),'maximal').
					'</p>'.
					'<p class="form-note warn">'.
					__('Warning: If you set the URL manually, it may conflict with another entry.').
					'</p></div>'
	))));

	$main_items = new ArrayObject(array(
		"post_title" =>
        '<p class="col">'.
        '<label class="required no-margin bold" for="post_title"><abbr title="'.__('Required field').'">*</abbr> '.__('Title:').'</label>'.
        form::field('post_title',20,255,html::escapeHTML($post_title),'maximal').
        '</p>',

		"post_excerpt" =>
        '<p class="area" id="excerpt-area"><label for="post_excerpt" class="bold">'.__('Excerpt:').' <span class="form-note">'.
        __('Introduction to the post.').'</span></label> '.
        form::textarea('post_excerpt',50,5,html::escapeHTML($post_excerpt)).
        '</p>'
    ));

    if (!$page_isfile) {
        $main_items['post_content'] = '<p class="area" id="content-area"><label class="required bold" '.
			'for="post_content"><abbr title="'.__('Required field').'">*</abbr> '.__('Content:').'</label> '.
			form::textarea('post_content',50,$core->auth->getOption('edit_size'),html::escapeHTML($post_content)).
			'</p>';
	} else {
        $main_items['is_file'] = '<p class="col"><label class="required" title="'.__('Required field').'" '.
            'for="page_relatedfile">'.__('Included file:').
            dcPage::help('post','page_relatedfile').'</label></p>'.
            '<div class="fieldset">'.
            '<p><label>'.__('Pick up a local file in your related pages repository').' '.
            form::combo('repository_file',$related_pages_files,$page_relatedfile).
            '</label></p>'.
            form::hidden(array('MAX_FILE_SIZE'),DC_MAX_UPLOAD_SIZE).
            '<p><label>'.__('or upload a new file').' '.
            '<input type="file" id="up_file" name="up_file" size="20" />'.
            '</label></p>'.
            '</div>'.
            form::hidden('st','file');
	}

    $main_items["post_notes"] = '<p class="area" id="notes-area">'.
        '<label for="post_notes" class="bold">'.__('Personal notes:').' <span class="form-note">'.
        __('Unpublished notes.').'</span></label>'.
        form::textarea('post_notes',50,5,html::escapeHTML($post_notes)).
        '</p>';

    echo '<div class="multi-part" title="'.($post_id ? __('Edit page') : __('New page')).'" id="edit-entry">';
    echo '<form action="plugin.php?p=related&amp;do=edit" method="post" id="entry-form" enctype="multipart/form-data">';
	echo '<div id="entry-wrapper">';
	echo '<div id="entry-content"><div class="constrained">';

	echo '<h3 class="out-of-screen-if-js">'.__('Edit post').'</h3>';

	foreach ($main_items as $id => $item) {
		echo $item;
	}

    # --BEHAVIOR-- adminPostForm (may be deprecated)
	$core->callBehavior('adminPostForm',isset($post) ? $post : null);

    	echo
	'<p class="border-top">'.
	($post_id ? form::hidden('id',$post_id) : '').
	'<input type="submit" value="'.__('Save').' (s)" '.
	'accesskey="s" name="save" /> ';
	if ($post_id) {
		$preview_url =
		$core->blog->url.$core->url->getURLFor('preview',$core->auth->userID().'/'.
		http::browserUID(DC_MASTER_KEY.$core->auth->userID().$core->auth->getInfo('user_pwd')).
		'/'.$post->post_url);
		echo '<a id="post-preview" href="'.$preview_url.'" class="button modal" accesskey="p">'.__('Preview').' (p)'.'</a> ';
	} else {
		echo
		'<a id="post-cancel" href="'.$core->adminurl->get("admin.home").'" class="button" accesskey="c">'.__('Cancel').' (c)</a>';
	}

	echo
	($can_delete ? '<input type="submit" class="delete" value="'.__('Delete').'" name="delete" />' : '').
	$core->formNonce().
	'</p>';

	echo '</div></div>';		// End #entry-content
	echo '</div>';		// End #entry-wrapper

	echo '<div id="entry-sidebar" role="complementary">';

	foreach ($sidebar_items as $id => $c) {
		echo '<div id="'.$id.'" class="sb-box">';
        if (!empty($c['title'])) {
            echo '<h4>'.$c['title'].'</h4>';
        }
		foreach ($c['items'] as $e_name => $e_content) {
			echo $e_content;
		}
		echo '</div>';
	}


	# --BEHAVIOR-- adminPostFormSidebar (may be deprecated)
	$core->callBehavior('adminPostFormSidebar',isset($post) ? $post : null);
	echo '</div>';		// End #entry-sidebar

	echo '</form>';

	# --BEHAVIOR-- adminPostForm
	$core->callBehavior('adminPostAfterForm',isset($post) ? $post : null);

	echo '</div>';
}
dcPage::helpBlock('related_pages_edit','core_wiki');

?>
	</body>
</html>