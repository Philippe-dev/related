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

declare(strict_types=1);

namespace Dotclear\Plugin\related;

use Dotclear\Plugin\pages\Pages;
use Dotclear\Core\Auth;
use Dotclear\Core\Blog;
use Exception;
use ArrayObject;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Text;
use Dotclear\Plugin\related\Entity\Post;
use Dotclear\App;
use form;

class ManagePage extends Process
{
    private const POST_TYPE = 'related';

    private static string $page_title = '';
    private static string $page_title_edit = '';
    private static string $post_url = '';
    private static bool $bad_dt = false;

    private static Post $post;
    private static array $post_editor = [];

    private static array $permissions = [
        'can_view_page' => true,
        'can_edit_post' => false,
        'can_publish' => false,
        'can_delete' => false,
    ];

    private static bool $pageIsFile = true;
    private static string $page_related_file = '';
    private static array $related_pages_files = ['-' => ''];

    public static function init(): bool
    {
        if (My::checkContext(My::MANAGE)) {
            self::status(empty($_REQUEST['part']) || $_REQUEST['part'] === 'page');
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        self::$post = new Post();
        self::$post->setPostFormat(App::auth()->getOption('post_format'));
        if ($user_lang = App::auth()->getInfo('user_lang')) {
            self::$post->setPostLang($user_lang);
        }

        if ($user_post_status = App::auth()->getInfo('user_post_status')) {
            self::$post->setPostStatus((int) $user_post_status);
        }

        self::$page_title = __('New page');
        self::$post_editor = App::auth()->getOption('editor');

        Page::check(App::auth()->makePermissions([Pages::PERMISSION_PAGES, Auth::PERMISSION_CONTENT_ADMIN]));
        self::$permissions['can_edit_post'] = App::auth()->check(App::auth()->makePermissions([Pages::PERMISSION_PAGES, Auth::PERMISSION_CONTENT_ADMIN]), App::blog()->id());
        self::$permissions['can_publish'] = App::auth()->check(App::auth()->makePermissions([Auth::PERMISSION_CONTENT_ADMIN]), App::blog()->id());

        if (!self::$permissions['can_publish']) {
            self::$post->setPostStatus(Blog::POST_PENDING);
        }

        self::$pageIsFile = (!empty($_REQUEST['type']) && $_REQUEST['type'] === 'file');

        $post_id = '';
        if (!empty($_REQUEST['id'])) {
            $params = [];
            $params['post_id'] = $_REQUEST['id'];
            $params['post_type'] = self::POST_TYPE;

            $dcPost = App::blog()->getPosts($params, false);
            $dcPost->extend('rsRelated');

            if ($dcPost->isEmpty()) {
                Notices::addErrorNotice(__('This page does not exist.'));
                self::$permissions['can_view_page'] = false;
            } else {
                $post_id = $dcPost->post_id;
                self::$post_url = $dcPost->getURL();
                self::$page_title = __('Edit page');

                self::$permissions['can_edit_post'] = $dcPost->isEditable();
                self::$permissions['can_delete'] = $dcPost->isDeletable();

                self::$post->fromMetaRecord($dcPost);

                try {
                    $post_metas = App::meta()->getMetaRecordset($dcPost->post_meta, 'related_file');
                    if (!$post_metas->isEmpty()) {
                        self::$page_related_file = $post_metas->meta_id;
                        self::$pageIsFile = true;
                    }
                } catch (Exception) {
                }
            }
        }

        if (self::$pageIsFile) {
            self::$post->setPostContent('/** external content **/');
            self::$post->setPostContentXhtml('/** external content **/');

            $dir = @dir((string) App::blog()->settings()->related->files_path);
            $allowed_exts = ['php', 'html', 'xml', 'txt'];

            if ($dir) {
                while (($entry = $dir->read()) !== false) {
                    $entry_path = $dir->path . '/' . $entry;
                    if (in_array(Files::getExtension($entry), $allowed_exts)) {
                        if (is_file($entry_path) && is_readable($entry_path)) {
                            self::$related_pages_files[$entry] = $entry;
                        }
                    }
                }
            }
        }

        if (!empty($_POST) && !empty($_POST['save']) && self::$permissions['can_edit_post']) {
            $post_content = self::$post->getPostContent();
            $post_content_xhtml = self::$post->getPostContentXhtml();
            $post_excerpt = self::$post->getPostExcerpt();
            $post_excerpt_xhtml = self::$post->getPostExcerptXhtml();

            self::$post->setPostFormat($_POST['post_format']);
            $post_excerpt = $_POST['post_excerpt'];

            self::$post->setPostLang($_POST['post_lang']);

            if (!self::$pageIsFile) {
                $post_content = $_POST['post_content'];
            }

            App::blog()->setPostContent($post_id, self::$post->getPostFormat(), self::$post->getPostLang(), $post_excerpt, $post_excerpt_xhtml, $post_content, $post_content_xhtml);
            self::$post->setPostExcerpt($post_excerpt);
            self::$post->setPostExcerptXhtml($post_excerpt_xhtml);
            self::$post->setPostContent($post_content);
            self::$post->setPostContentXhtml($post_content_xhtml);

            self::$post->setPostTitle($_POST['post_title']);

            if (isset($_POST['post_status'])) {
                self::$post->setPostStatus((int) $_POST['post_status']);
            }

            if (empty($_POST['post_dt'])) {
                self::$post->setPostDate('');
            } else {
                try {
                    $post_dt = strtotime($_POST['post_dt']);
                    if (!$post_dt || $post_dt == -1) {
                        self::$bad_dt = true;

                        throw new Exception(__('Invalid publication date'));
                    }
                    self::$post->setPostDate(date('Y-m-d H:i', $post_dt));
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }

            if (!empty($_POST['post_password'])) {
                self::$post->setPostPassword($_POST['post_password']);
            }

            self::$post->setPostNotes($_POST['post_notes']);

            if (isset($_POST['post_url'])) {
                self::$post->setPostUrl($_POST['post_url']);
            }

            self::$post->setPostSelected(isset($_POST['post_selected']));

            if (self::$pageIsFile) {
                $related_upl = null;
                if (!empty($_FILES['up_file']['name'])) {
                    $related_upl = true;
                } elseif (!empty($_POST['repository_file']) && in_array($_POST['repository_file'], self::$related_pages_files)) {
                    $related_upl = false;
                }

                if (!is_null($related_upl)) {
                    try {
                        if ($related_upl) {
                            Files::uploadStatus($_FILES['up_file']);
                            $src_file = $_FILES['up_file']['tmp_name'];
                            $trg_file = App::blog()->settings()->related->files_path . '/' . $_FILES['up_file']['name'];
                            if (move_uploaded_file($src_file, $trg_file)) {
                                self::$page_related_file = $_FILES['up_file']['name'];
                            }
                        } else {
                            self::$page_related_file = $_POST['repository_file'];
                        }
                    } catch (Exception $e) {
                        Notices::addErrorNotice($e->getMessage());
                    }
                }
            }

            $cur = App::con()->openCursor(App::con()->prefix() . 'post');
            self::$post->setCursor($cur);

            if ($post_id) {
                try {
                    if (self::$pageIsFile && empty(self::$page_related_file)) {
                        throw new Exception(__('Missing file.'));
                    }

                    // --BEHAVIOR-- adminBeforePostUpdate
                    App::behavior()->callBehavior('adminBeforePostUpdate', $cur, $post_id);
                    // --BEHAVIOR-- adminBeforePageUpdate
                    App::behavior()->callBehavior('adminBeforePageUpdate', $cur, $post_id);

                    App::con()->begin();
                    App::blog()->updPost($post_id, $cur);
                    if (self::$pageIsFile) {
                        try {
                            App::meta()->delPostMeta($post_id, 'related_file');
                            App::meta()->setPostMeta($post_id, 'related_file', self::$page_related_file);
                        } catch (Exception $e) {
                            App::con()->rollback();
                            throw $e;
                        }
                    }
                    App::con()->commit();

                    // --BEHAVIOR-- adminAfterPostUpdate
                    App::behavior()->callBehavior('adminAfterPostUpdate', $cur, $post_id);
                    // --BEHAVIOR-- adminAfterPageUpdate
                    App::behavior()->callBehavior('adminAfterPageUpdate', $cur, $post_id);

                    Notices::addSuccessNotice(__('Page has been updated.'));
                    My::redirect(['part' => 'page', 'id' => $post_id]);
                } catch (Exception $e) {
                    Notices::addErrorNotice($e->getMessage());
                }
            } else {
                $cur->user_id = App::auth()->userID();
                if (!isset($_POST['post_url'])) {
                    $cur->post_url = Text::str2URL(self::$post->getPostTitle());
                }

                try {
                    if (self::$pageIsFile && empty(self::$page_related_file)) {
                        throw new Exception(__('Missing file.'));
                    }

                    // --BEHAVIOR-- adminBeforePostCreate
                    App::behavior()->callBehavior('adminBeforePostCreate', $cur);
                    // --BEHAVIOR-- adminBeforePageCreate
                    App::behavior()->callBehavior('adminBeforePageCreate', $cur);

                    App::con()->begin();
                    $return_id = App::blog()->addPost($cur);
                    if (self::$pageIsFile) {
                        try {
                            App::meta()->setPostMeta($return_id, 'related_file', self::$page_related_file);
                        } catch (Exception $e) {
                            App::con()->rollback();
                            throw $e;
                        }
                    }
                    App::con()->commit();

                    // --BEHAVIOR-- adminAfterPostCreate
                    App::behavior()->callBehavior('adminAfterPostCreate', $cur, $return_id);
                    // --BEHAVIOR-- adminAfterPageCreate
                    App::behavior()->callBehavior('adminAfterPageCreate', $cur, $return_id);

                    Notices::addSuccessNotice(__('Page has been created.'));
                    My::redirect(['part' => 'page', 'id' => $return_id]);
                } catch (Exception $e) {
                    Notices::addErrorNotice($e->getMessage());
                }
            }
        }

        if (!empty($_POST['delete']) && self::$permissions['can_delete']) {
            try {
                Notices::addSuccessNotice(__('Page has been deleted.'));
                My::redirect(['part' => 'pages']);
            } catch (Exception $e) {
                Notices::addErrorNotice($e->getMessage());
            }
        }
        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $default_tab = 'edit-entry';
        if (!self::$permissions['can_edit_post'] || !empty($_POST['preview'])) {
            $default_tab = 'preview-entry';
        }

        if (self::$post->getPostId()) {
            self::$page_title_edit = sprintf(__('&ldquo;%s&rdquo;'), Html::escapeHTML(self::$post->getPostTitle())) . ' ' . self::getImageStatus();
        }

        $admin_post_behavior = '';
        if (self::$post_editor && !empty(self::$post_editor[self::$post->getPostFormat()])) {
            $admin_post_behavior = App::behavior()->callBehavior(
                'adminPostEditor',
                self::$post_editor[self::$post->getPostFormat()],
                self::POST_TYPE,
                ['#post_content', '#post_excerpt']
            );
        }

        Page::openModule(
            My::name(),
            Page::jsModal() . $admin_post_behavior .
	        Page::jsLoad('js/_post.js') .
	        Page::jsConfirmClose('entry-form') .
	        // --BEHAVIOR-- adminRelatedHeaders
	        App::behavior()->callBehavior('adminRelatedHeaders') .
            Page::jsPageTabs($default_tab)
        );

        echo Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                __('Related pages') => My::manageUrl(),
                (self::$post->getPostId() ? self::$page_title_edit : self::$page_title) => ''
            ]
        );

        if (!empty($_GET['xconv'])) {
            self::$post->setPostExcerpt(self::$post->getPostExcerptXhtml());
            self::$post->setPostContent(self::$post->getPostContentXhtml());
            self::$post->setPostFormat('xhtml');

            Notices::message(__('Don\'t forget to validate your XHTML conversion by saving your post.'));
        }

        Notices::GetNotices();

        $status_combo = Combos::getPostStatusesCombo();
        $lang_combo = Combos::getLangsCombo(App::blog()->getLangs(['order' => 'asc']), true);

        $available_formats = ['' => ''];
        foreach (App::formater()->getFormaters() as $formats) {
            foreach ($formats as $format) {
                $available_formats[App::formater()->getFormaterName($format)] = $format;
            }
        }

        if (self::$permissions['can_edit_post']) {
            $sidebar_items = new ArrayObject([
                'status-box' => [
                    'title' => __('Status'),
                    'items' => [
                        'post_status' => '<p class="entry-status"><label for="post_status">' . __('Page status') . ' ' . self::getImageStatus() . '</label>' .
                        form::combo('post_status', $status_combo, self::$post->getPostStatus(), 'maximal', '', !self::$permissions['can_publish']) .
                        '</p>',
                        'post_dt' => '<p><label for="post_dt">' . __('Publication date and hour') . '</label>' .
                        form::field('post_dt', 16, 16, self::$post->getPostDate(), (self::$bad_dt ? 'invalid' : '')) .
                        '</p>',
                        'post_lang' => '<p><label for="post_lang">' . __('Entry language') . '</label>' .
                        form::combo('post_lang', $lang_combo, self::$post->getPostLang()) .
                        '</p>',
                        'post_format' => '<div>' .
                        '<h5 id="label_format"><label for="post_format" class="classic">' . __('Text formatting') . '</label></h5>' .
                        '<p>' . form::combo('post_format', $available_formats, self::$post->getPostFormat(), 'maximal') . '</p>' .
                        '<p class="format_control control_no_xhtml">' .
                        '<a id="convert-xhtml" class="button' . (self::$post->getPostId() && self::$post->getPostFormat() !== 'wiki' ? ' hide' : '') . '" href="' .
                        My::manageUrl(['part' => 'page', 'id' => self::$post->getPostId(), 'xconv' => '1']) .
                        '">' .
                        __('Convert to XHTML') . '</a></p></div>']],
                'metas-box' => [
                    'title' => __('Filing'),
                    'items' => [
                        'post_selected' => '<p><label for="post_selected" class="classic">' .
                        form::checkbox('post_selected', 1, self::$post->getPostSelected()) . ' ' .
                        __('In widget') . '</label></p>'
                    ],
                ],

                'options-box' => [
                    'title' => __('Options'),
                    'items' => [
                        'post_password' => '<p><label for="post_password">' . __('Password') . '</label>' .
                        form::field('post_password', 10, 32, html::escapeHTML(self::$post->getPostPassword()), 'maximal') .
                        '</p>',
                        'post_url' => '<div class="lockable">' .
                        '<p><label for="post_url">' . __('Edit basename') . '</label>' .
                        form::field('post_url', 10, 255, html::escapeHTML(self::$post->getPostUrl()), 'maximal') .
                        '</p>' .
                        '<p class="form-note warn">' .
                        __('Warning: If you set the URL manually, it may conflict with another entry.') .
                        '</p></div>'
                    ]]]);

            $main_items = new ArrayObject([
                "post_title" => '<p class="col">' .
                '<label class="required no-margin bold" for="post_title"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label>' .
                form::field('post_title', 20, 255, html::escapeHTML(self::$post->getPostTitle()), 'maximal') .
                '</p>',

                "post_excerpt" => '<p class="area" id="excerpt-area"><label for="post_excerpt" class="bold">' . __('Excerpt:') . ' <span class="form-note">' .
                __('Introduction to the post.') . '</span></label> ' .
                form::textarea('post_excerpt', 50, 5, html::escapeHTML(self::$post->getPostExcerpt())) .
                '</p>'
            ]);

            if (!self::$pageIsFile) {
                $main_items['post_content'] = '<p class="area" id="content-area"><label class="required bold" ' .
                    'for="post_content"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Content:') . '</label> ' .
                    form::textarea('post_content', 50, App::auth()->getOption('edit_size'), html::escapeHTML(self::$post->getPostContent())) .
                    '</p>';
            } else {
                Page::helpBlock('post', 'page_relatedfile');

                $main_items['post_content'] = '<div style="display:none">' .
                    form::textarea('post_content', 1, 1, html::escapeHTML(self::$post->getPostContent())) .
                    '</div>';
                $main_items['is_file'] = '<p class="col"><label class="required" title="' . __('Required field') . '" ' .
                    'for="repository_file">' . __('Included file:') . '</label></p>' .
                    '<div class="fieldset">' .
                    '<p><label>' . __('Pick up a local file in your related pages repository') . ' ' .
                    form::combo('repository_file', self::$related_pages_files, self::$page_related_file) .
                    '</label></p>' .
                    form::hidden(['MAX_FILE_SIZE'], DC_MAX_UPLOAD_SIZE) .
                    '<p><label>' . __('or upload a new file') . ' ' .
                    '<input type="file" page_relid="up_file" name="up_file" />' .
                    '</label></p>' .
                    '</div>' .
                    form::hidden('part', 'page') .
                    App::nonce()->getFormNonce() .
                    form::hidden('type', 'file');
            }

            $main_items["post_notes"] = '<p class="area" id="notes-area">' .
                '<label for="post_notes" class="bold">' . __('Personal notes:') . ' <span class="form-note">' .
                __('Unpublished notes.') . '</span></label>' .
                form::textarea('post_notes', 50, 5, html::escapeHTML(self::$post->getPostNotes())) .
                '</p>';


            if (self::$post->getPostId() && self::$post->getPostStatus() === Blog::POST_PUBLISHED) {
                echo '<p><a class="onblog_link outgoing" href="', self::$post_url, '" title="' . Html::escapeHTML(trim(Html::clean(self::$post->getPostTitle()))), '">';
                echo __('Go to this related page on the site'), ' <img src="images/outgoing-link.svg" alt="" /></a></p>';
            }

            echo '<div class="multi-part" title="' . (self::$post->getPostId() ? __('Edit page') : __('New page')) . '" id="edit-entry">';
            echo '<form action="', My::manageUrl(), '" method="post" id="entry-form" enctype="multipart/form-data">';
            echo '<div id="entry-wrapper">';
            echo '<div id="entry-content"><div class="constrained">';

            echo '<h3 class="out-of-screen-if-js">' . __('Edit post') . '</h3>';

            foreach ($main_items as $id => $item) {
                echo $item;
            }

            // --BEHAVIOR-- adminPostForm (may be deprecated)
            App::behavior()->callBehavior('adminPostForm', self::$post->toMetaRecord() ?? null);

            echo
            '<p class="border-top">' .
            (self::$post->getPostId() ? form::hidden('id', self::$post->getPostId()) : '') .
            form::hidden('part', 'page') .
            App::nonce()->getFormNonce() .
            '<input type="submit" value="' . __('Save') . ' (s)" accesskey="s" name="save" /> ';

            if (self::$post->getPostId()) {
                $preview_url =
                App::blog()->url() . App::url()->getURLFor('relatedPreview', App::auth()->userID() . '/' .
                Http::browserUID(DC_MASTER_KEY . App::auth()->userID() . App::auth()->getInfo('user_pwd')) .
                '/' . self::$post->getPostUrl());
                echo '<a id="post-preview" href="' . $preview_url . '" class="button modal" accesskey="p">' . __('Preview') . ' (p)' . '</a> ';
            } else {
                echo
                '<a id="post-cancel" href="' . App::backend()->url()->get("admin.home") . '" class="button" accesskey="c">' . __('Cancel') . ' (c)</a>';
            }

            echo
                (self::$permissions['can_delete'] ? '<input type="submit" class="delete" value="' . __('Delete') . '" name="delete" />' : '') .
                '</p>';

            echo '</div></div>';		// End #entry-content
            echo '</div>';		// End #entry-wrapper

            echo '<div id="entry-sidebar" role="complementary">';

            foreach ($sidebar_items as $id => $c) {
                echo '<div id="' . $id . '" class="sb-box">';
                if (!empty($c['title'])) {
                    echo '<h4>' . $c['title'] . '</h4>';
                }
                foreach ($c['items'] as $e_content) {
                    echo $e_content;
                }
                echo '</div>';
            }


            // --BEHAVIOR-- adminPostFormSidebar (may be deprecated)
            App::behavior()->callBehavior('adminPostFormSidebar', self::$post ?? null);
            echo '</div>';		// End #entry-sidebar

            echo '</form>';

            // --BEHAVIOR-- adminPostForm
            App::behavior()->callBehavior('adminPostAfterForm', self::$post->getPostId() ? self::$post->toMetaRecord() : null);

            echo '</div>';
        }

        Page::helpBlock('related_pages_edit', 'core_wiki');
        Page::closeModule();
    }

    protected static function getImageStatus(): string
    {
        if (!self::$post->getPostId()) {
            return '';
        }

        $img_status = '';
        $img_status_pattern = '<img class="img_select_option mark mark-%3$s" alt="%1$s" title="%1$s" src="images/%2$s" />';

        $img_status = match (self::$post->getPostStatus()) {
            Blog::POST_PUBLISHED => sprintf($img_status_pattern, __('Published'), 'check-on.svg', 'published'),
            Blog::POST_UNPUBLISHED => sprintf($img_status_pattern, __('Unpublished'), 'check-off.svg', 'unpublished'),
            Blog::POST_SCHEDULED => sprintf($img_status_pattern, __('Scheduled'), 'scheduled.svg', 'scheduled'),
            Blog::POST_PENDING => sprintf($img_status_pattern, __('Pending'), 'check-wrn.svg', 'pending'),
            default => '',
        };

        return $img_status;
    }
}
