<?php

/*
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

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Datetime;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Plugin\pages\Pages;
use Exception;

/**
 * @brief   The module backend manage page process.
 * @ingroup pages
 */
class ManagePage
{
    use TraitProcess;

    private static bool $page_is_file = true;

    private static string $file_name = '';

    /**
     * @var array<string, string>
     */
    private static array $files_list = ['-' => ''];

    private static string $page_title = '';

    private static string $next_link = '';

    private static string $prev_link = '';

    private static string $next_headlink = '';

    private static string $prev_headlink = '';

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

        // Variable data helpers
        $_Str = fn (mixed $var, string $default = ''): string => $var !== null && is_string($val = $var) ? $val : $default;

        self::$page_is_file = (!empty($_REQUEST['type']) && $_REQUEST['type'] === 'file');

        $params = [];
        App::backend()->page()->check(App::auth()->makePermissions([
            Pages::PERMISSION_PAGES,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';

        Date::setTZ($user_tz);

        App::backend()->post_id            = '';
        App::backend()->post_dt            = '';
        App::backend()->post_format        = App::auth()->prefs()->get('interface')->getStr('post_format');
        App::backend()->post_editor        = App::auth()->prefs()->get('interface')->get('editor');
        App::backend()->post_password      = '';
        App::backend()->post_url           = '';
        App::backend()->post_lang          = App::auth()->getInfo('user_lang');
        App::backend()->post_title         = '';
        App::backend()->post_excerpt       = '';
        App::backend()->post_excerpt_xhtml = '';
        App::backend()->post_content       = '';
        App::backend()->post_content_xhtml = '';
        App::backend()->post_notes         = '';
        App::backend()->post_status        = App::auth()->getInfo('user_post_status');
        App::backend()->post_position      = 0;
        App::backend()->post_open_comment  = false;
        App::backend()->post_open_tb       = false;
        App::backend()->post_selected      = false;

        App::backend()->post_media = [];

        self::$page_title = __('New included page');

        App::backend()->can_view_page = true;
        App::backend()->can_edit_page = App::auth()->check(App::auth()->makePermissions([
            Pages::PERMISSION_PAGES,
            App::auth()::PERMISSION_USAGE,
        ]), App::blog()->id());
        App::backend()->can_publish = App::auth()->check(App::auth()->makePermissions([
            Pages::PERMISSION_PAGES,
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id());
        App::backend()->can_delete = false;

        $post_headlink = '<link rel="%s" title="%s" href="' . My::manageUrl(['part' => 'page', 'id' => '%s'], parametric: true) . '">';

        App::backend()->post_link = '<a href="' . My::manageUrl(['part' => 'page', 'id' => '%s'], parametric: true) . '" class="%s" title="%s">%s</a>';

        // If user can't publish
        if (!App::backend()->can_publish) {
            App::backend()->post_status = App::status()->post()::PENDING;
        }

        // Validation flag
        App::backend()->bad_dt = false;

        self::$page_is_file = (!empty($_REQUEST['type']) && $_REQUEST['type'] === 'file');

        // Get page informations

        App::backend()->post = null;
        if (!empty($_REQUEST['id'])) {
            $params['post_type'] = 'related';
            $params['post_id']   = $_REQUEST['id'];

            App::backend()->post = App::blog()->getPosts($params);

            if (App::backend()->post->isEmpty()) {
                App::backend()->notices()->addErrorNotice(__('This page does not exist.'));
                My::redirect();
            } else {
                App::backend()->post_id            = (int) App::backend()->post->intField('post_id');
                App::backend()->post_dt            = date('Y-m-d H:i', (int) strtotime((string) App::backend()->post->strField('post_dt')));
                App::backend()->post_format        = App::backend()->post->post_format;
                App::backend()->post_password      = App::backend()->post->post_password;
                App::backend()->post_url           = App::backend()->post->post_url;
                App::backend()->post_lang          = App::backend()->post->post_lang;
                App::backend()->post_title         = App::backend()->post->post_title;
                App::backend()->post_excerpt       = App::backend()->post->post_excerpt;
                App::backend()->post_excerpt_xhtml = App::backend()->post->post_excerpt_xhtml;
                App::backend()->post_content       = App::backend()->post->post_content;
                App::backend()->post_content_xhtml = App::backend()->post->post_content_xhtml;
                App::backend()->post_notes         = App::backend()->post->post_notes;
                App::backend()->post_status        = App::backend()->post->post_status;
                App::backend()->post_position      = (int) App::backend()->post->intField('post_position');
                App::backend()->post_open_comment  = (bool) App::backend()->post->post_open_comment;
                App::backend()->post_open_tb       = (bool) App::backend()->post->post_open_tb;
                App::backend()->post_selected      = (bool) App::backend()->post->post_selected;
                App::backend()->post_meta          = App::backend()->post->post_meta;

                self::$page_title = __('Edit page');

                App::backend()->can_edit_page = App::backend()->post->isEditable();
                App::backend()->can_delete    = App::backend()->post->isDeletable();

                $next_rs = App::blog()->getNextPost(App::backend()->post, 1);
                $prev_rs = App::blog()->getNextPost(App::backend()->post, -1);

                if ($next_rs instanceof MetaRecord) {
                    self::$next_link = sprintf(
                        App::backend()->post_link,
                        $next_rs->intField('post_id'),
                        'next',
                        Html::escapeHTML(trim(Html::clean($next_rs->strField('post_title')))),
                        __('Next page') . '&nbsp;&#187;'
                    );
                    self::$next_headlink = sprintf(
                        $post_headlink,
                        'next',
                        Html::escapeHTML(trim(Html::clean($next_rs->strField('post_title')))),
                        $next_rs->intField('post_id')
                    );
                }

                if ($prev_rs instanceof MetaRecord) {
                    self::$prev_link = sprintf(
                        App::backend()->post_link,
                        $prev_rs->intField('post_id'),
                        'prev',
                        Html::escapeHTML(trim(Html::clean($prev_rs->strField('post_title')))),
                        '&#171;&nbsp;' . __('Previous page')
                    );
                    self::$prev_headlink = sprintf(
                        $post_headlink,
                        'previous',
                        Html::escapeHTML(trim(Html::clean($prev_rs->strField('post_title')))),
                        $prev_rs->intField('post_id')
                    );
                }

                try {
                    App::backend()->post_media = App::media()->getPostMedia(App::backend()->post_id);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        if ($_POST !== [] && App::backend()->can_edit_page) {
            // Format content

            App::backend()->post_format  = $_POST['post_format'];
            App::backend()->post_excerpt = $_POST['post_excerpt'];
            App::backend()->post_content = $_POST['post_content'];

            App::backend()->post_title = $_POST['post_title'];

            if (isset($_POST['post_status'])) {
                App::backend()->post_status = is_numeric($post_status = $_POST['post_status']) ? (int) $post_status : 0;
            }

            if (empty($_POST['post_dt'])) {
                App::backend()->post_dt = '';
            } else {
                try {
                    App::backend()->post_dt = strtotime(is_string($_POST['post_dt']) ? $_POST['post_dt'] : '');
                    if (!App::backend()->post_dt || App::backend()->post_dt === -1) {
                        App::backend()->bad_dt = true;

                        throw new Exception(__('Invalid publication date'));
                    }

                    App::backend()->post_dt = date('Y-m-d H:i', App::backend()->post_dt);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }

            App::backend()->post_open_comment = !empty($_POST['post_open_comment']);
            App::backend()->post_open_tb      = !empty($_POST['post_open_tb']);
            App::backend()->post_selected     = !empty($_POST['post_selected']);
            App::backend()->post_lang         = $_POST['post_lang'];
            App::backend()->post_password     = empty($_POST['post_password']) ? null : $_POST['post_password'];
            //App::backend()->post_position     = (int) $_POST['post_position'];

            App::backend()->post_notes = $_POST['post_notes'];

            if (isset($_POST['post_url'])) {
                App::backend()->post_url = $_POST['post_url'];
            }

            [
                $post_excerpt, $post_excerpt_xhtml, $post_content, $post_content_xhtml
            ] = [
                $_Str(App::backend()->post_excerpt),
                $_Str(App::backend()->post_excerpt_xhtml),
                $_Str(App::backend()->post_content),
                $_Str(App::backend()->post_content_xhtml),
            ];

            App::blog()->setPostContent(
                (int) App::backend()->post_id,
                is_string(App::backend()->post_format) ? App::backend()->post_format : 'xhtml',
                is_string(App::backend()->post_lang) ? App::backend()->post_lang : 'en',
                $post_excerpt,
                $post_excerpt_xhtml,
                $post_content,
                $post_content_xhtml
            );

            [
                App::backend()->post_excerpt,
                App::backend()->post_excerpt_xhtml,
                App::backend()->post_content,
                App::backend()->post_content_xhtml
            ] = [
                $post_excerpt, $post_excerpt_xhtml, $post_content, $post_content_xhtml,
            ];
        }

        if (!empty($_POST['delete']) && App::backend()->can_delete) {
            // Delete page

            try {
                # --BEHAVIOR-- adminBeforePageDelete -- int
                App::behavior()->callBehavior('adminBeforePageDelete', App::backend()->post_id);
                App::blog()->delPost((int) App::backend()->post_id);
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if ($_POST !== [] && !empty($_POST['save']) && App::backend()->can_edit_page && !App::backend()->bad_dt) {
            // Create or update page

            if (self::$page_is_file) {
                $files_path = trim((string) My::settings()->getStr('files_path', false));

                $dir          = @dir($files_path);
                $allowed_exts = ['php', 'html', 'xml', 'txt'];

                self::$files_list = ['-' => ''];

                if ($dir) {
                    while (($entry = $dir->read()) !== false) {
                        $entry_path = $dir->path . '/' . $entry;
                        if (in_array(Files::getExtension($entry), $allowed_exts) && is_file($entry_path) && is_readable($entry_path)) {
                            self::$files_list[$entry] = $entry;
                        }
                    }
                }

                /**
                 * @var null|array{name: string, type: string, size: int, tmp_name: string, error?: int, full_path: string}  $up_file
                 */
                $up_file     = $_FILES['up_file'] ?? null;
                $related_upl = null;
                if (!empty($up_file['name'])) {
                    $related_upl = true;
                } elseif (!empty($_POST['files_dir']) && in_array($_POST['files_dir'], self::$files_list)) {
                    $related_upl = false;
                }

                if (!is_null($related_upl)) {
                    try {
                        if ($related_upl) {
                            Files::uploadStatus($up_file);
                            $src_file = $up_file['tmp_name'];
                            $trg_file = $files_path . '/' . $up_file['name'];
                            if (move_uploaded_file($src_file, $trg_file)) {
                                self::$file_name = $up_file['name'];
                            }
                        } else {
                            self::$file_name = is_string($_POST['files_dir']) ? $_POST['files_dir'] : '';
                        }
                    } catch (Exception $e) {
                        App::backend()->notices()->addErrorNotice($e->getMessage());
                    }
                }
            }

            $cur = App::blog()->openPostCursor();

            // Magic tweak :)
            App::blog()->settings()->get('system')->set('post_url_format', '{t}');

            $cur->post_type          = 'related';
            $cur->post_dt            = App::backend()->post_dt ? date('Y-m-d H:i:00', (int) strtotime((string) App::backend()->post_dt)) : '';
            $cur->post_format        = App::backend()->post_format;
            $cur->post_password      = App::backend()->post_password;
            $cur->post_lang          = App::backend()->post_lang;
            $cur->post_title         = App::backend()->post_title;
            $cur->post_excerpt       = App::backend()->post_excerpt;
            $cur->post_excerpt_xhtml = App::backend()->post_excerpt_xhtml;
            $cur->post_content       = App::backend()->post_content;
            $cur->post_content_xhtml = App::backend()->post_content_xhtml;
            $cur->post_notes         = App::backend()->post_notes;
            $cur->post_status        = App::backend()->post_status;
            $cur->post_position      = App::backend()->post_position;
            $cur->post_open_comment  = (int) App::backend()->post_open_comment;
            $cur->post_open_tb       = (int) App::backend()->post_open_tb;
            $cur->post_selected      = (int) App::backend()->post_selected;

            if (isset($_POST['post_url'])) {
                $cur->post_url = App::backend()->post_url;
            }

            // Back to UTC in order to keep UTC datetime for creadt/upddt
            Date::setTZ('UTC');

            if (App::backend()->post_id) {
                // Update post

                try {
                    # --BEHAVIOR-- adminBeforePageUpdate -- Cursor, int
                    App::behavior()->callBehavior('adminBeforePageUpdate', $cur, App::backend()->post_id);

                    App::blog()->updPost(App::backend()->post_id, $cur);

                    App::db()->con()->begin();
                    if (self::$page_is_file) {
                        try {
                            App::meta()->delPostMeta(App::backend()->post_id, 'related_file');
                            App::meta()->setPostMeta(App::backend()->post_id, 'related_file', self::$file_name);
                        } catch (Exception $e) {
                            App::db()->con()->rollback();

                            throw $e;
                        }
                    }

                    App::db()->con()->commit();

                    # --BEHAVIOR-- adminAfterPageUpdate -- Cursor, int
                    App::behavior()->callBehavior('adminAfterPageUpdate', $cur, App::backend()->post_id);

                    App::backend()->notices()->addSuccessNotice(__('Page has been updated.'));

                    My::redirect(['part' => 'page', 'id' => App::backend()->post_id]);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            } else {
                $cur->user_id = App::auth()->userID();

                try {
                    # --BEHAVIOR-- adminBeforePageCreate -- Cursor
                    App::behavior()->callBehavior('adminBeforePageCreate', $cur);

                    $return_id = App::blog()->addPost($cur);

                    # --BEHAVIOR-- adminAfterPageCreate -- Cursor, int
                    App::behavior()->callBehavior('adminAfterPageCreate', $cur, $return_id);

                    App::db()->con()->begin();

                    try {
                        if (self::$file_name !== '') {
                            App::meta()->setPostMeta($return_id, 'related_file', self::$file_name);
                        }
                    } catch (Exception $e) {
                        App::db()->con()->rollback();

                        throw $e;
                    }

                    App::db()->con()->commit();

                    App::backend()->notices()->addSuccessNotice(__('Page has been created.'));

                    My::redirect(['part' => 'page', 'id' => $return_id, 'crea' => '1']);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        App::backend()->default_tab = 'edit-entry';
        if (!App::backend()->can_edit_page) {
            App::backend()->default_tab = '';
        }

        if (!empty($_GET['co'])) {
            App::backend()->default_tab = 'comments';
        }

        // 3rd party conversion
        if (!empty($_GET['convert']) && !empty($_GET['convert-format'])) {
            $params = new ArrayObject([
                'excerpt' => App::backend()->post_excerpt,
                'content' => App::backend()->post_content,
                'format'  => App::backend()->post_format,
            ]);

            $convert_format = is_string($convert_format = $_GET['convert-format']) ? Html::escapeHTML($convert_format) : '';

            # --BEHAVIOR-- adminConvertBeforePostEdit -- ArrayObject
            $msg = App::behavior()->callBehavior('adminConvertBeforePostEdit', $convert_format, $params);

            if ($msg !== '') {
                App::backend()->post_excerpt = $params['excerpt'];
                App::backend()->post_content = $params['content'];
                App::backend()->post_format  = $params['format'];

                App::backend()->notices()->addMessageNotice($msg);
            }
        }

        $post_id       = is_numeric($post_id = App::backend()->post_id) ? (int) $post_id : 0;
        $post_status   = is_numeric($post_status = App::backend()->post_status) ? (int) $post_status : 0;
        $post_title    = is_string($post_title = App::backend()->post_title) ? $post_title : '';
        $post_dt       = is_string($post_dt = App::backend()->post_dt) ? $post_dt : '';
        $post_lang     = is_string($post_lang = App::backend()->post_lang) ? $post_lang : '';
        $post_format   = is_string($post_format = App::backend()->post_format) ? $post_format : 'xhtml';
        $post_password = is_string($post_password = App::backend()->post_password) ? $post_password : '';
        $post_url      = is_string($post_url = App::backend()->post_url) ? $post_url : '';
        $post_meta     = is_string($post_meta = App::backend()->post_meta) ? $post_meta : '';
        $post_excerpt  = is_string($post_excerpt = App::backend()->post_excerpt) ? $post_excerpt : '';
        $post_content  = is_string($post_content = App::backend()->post_content) ? $post_content : '';
        $post_notes    = is_string($post_notes = App::backend()->post_notes) ? $post_notes : '';

        $edit_size = App::auth()->prefs()->get('interface')->getInt('edit_size', false);

        // Formaters combo
        $core_formaters    = App::formater()->getFormaters();
        $available_formats = ['' => ''];
        foreach ($core_formaters as $formats) {
            foreach ($formats as $format) {
                $available_formats[App::formater()->getFormaterName($format)] = $format;
            }
        }

        $admin_post_behavior = '';
        if (App::backend()->post_editor && is_array(App::backend()->post_editor)) {
            $p_edit = '';
            $c_edit = '';

            if (!empty(App::backend()->post_editor[$post_format])) {
                $p_edit = App::backend()->post_editor[$post_format];
            }

            if (!empty(App::backend()->post_editor['xhtml'])) {
                $c_edit = App::backend()->post_editor['xhtml'];
            }

            if ($p_edit === $c_edit) {
                # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'page',
                    ['#post_excerpt', '#post_content', '#comment_content'],
                    $post_format
                );
            } else {
                # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'page',
                    ['#post_excerpt', '#post_content'],
                    $post_format
                );
                # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $c_edit,
                    'comment',
                    ['#comment_content'],
                    'xhtml'
                );
            }
        }

        App::backend()->page()->openModule(
            self::$page_title . ' - ' . My::name(),
            App::backend()->page()->jsModal() .
            App::backend()->page()->jsJson('pages_page', ['confirm_delete_post' => __('Are you sure you want to delete this page?')]) .
            App::backend()->page()->jsLoad('js/_post.js') .
            My::jsLoad('page') .
            $admin_post_behavior .
            App::backend()->page()->jsConfirmClose('entry-form', 'comment-form') .
            # --BEHAVIOR-- adminPageHeaders --
            App::behavior()->callBehavior('adminPageHeaders') .
            App::backend()->page()->jsPageTabs(App::backend()->default_tab) .
            self::$next_headlink . "\n" . self::$prev_headlink
        );

        if ($post_id !== 0) {
            $img_status       = App::status()->post()->image($post_status)->render();
            $edit_entry_title = '&ldquo;' . Html::escapeHTML(trim(Html::clean($post_title))) . '&rdquo;' . ' ' . $img_status;
        } else {
            $img_status       = '';
            $edit_entry_title = self::$page_title;
        }

        // Languages combo
        $lang_combo = App::backend()->combos()->getLangsCombo(
            App::blog()->getLangs([
                'order_by' => 'nb_post',
                'order'    => 'desc',
            ]),
            true,
            true
        );

        echo App::backend()->page()->breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                My::name()                            => App::backend()->getPageURL(),
                $edit_entry_title                     => '',
            ]
        );

        echo App::backend()->notices()->getNotices();

        # HTML conversion
        if (!empty($_GET['xconv'])) {
            $post_excerpt = is_string($post_excerpt = App::backend()->post_excerpt_xhtml) ? $post_excerpt : '';
            $post_content = is_string($post_content = App::backend()->post_content_xhtml) ? $post_content : '';
            $post_format  = 'xhtml';

            App::backend()->notices()->message(__('Don\'t forget to validate your HTML conversion by saving your post.'));
        }

        if ($post_id && !App::status()->post()->isRestricted($post_status) && App::backend()->post instanceof MetaRecord) {
            $post_view_url = is_string($post_view_url = App::backend()->post->getURL()) ? $post_view_url : '';
            echo (new Para())
                ->items([
                    (new Link())
                        ->class(['onblog_link', 'outgoing'])
                        ->href($post_view_url)
                        ->title(Html::escapeHTML(trim(Html::clean($post_title))))
                        ->text(__('Go to this page on the site') . ' ' . (new Img('images/outgoing-link.svg'))->alt('')->render()),
                ])
            ->render();
        }

        if ($post_id !== 0) {
            $items = [];
            if (self::$prev_link !== '') {
                $items[] = new Text(null, self::$prev_link);
            }

            if (self::$next_link !== '') {
                $items[] = new Text(null, self::$next_link);
            }

            # --BEHAVIOR-- adminPageNavLinks -- MetaRecord|null
            $items[] = new Capture(App::behavior()->callBehavior(...), ['adminPageNavLinks', App::backend()->post ?? null]);

            echo (new Para())
                ->class('nav_prevnext')
                ->items($items)
            ->render();
        }

        # Exit if we cannot view page
        if (!App::backend()->can_view_page) {
            App::backend()->page()->closeModule();

            return;
        }

        /* Post form if we can edit page
        -------------------------------------------------------- */
        if (App::backend()->can_edit_page) {
            $sidebar_items = new ArrayObject([
                'status-box' => [
                    'title' => __('Status'),
                    'items' => [
                        'post_status' => (new Para())->class('entry-status')->items([
                            (new Select('post_status'))
                                ->items(App::status()->post()->combo())
                                ->default($post_status)
                                ->disabled(!App::backend()->can_publish)
                                ->label(new Label(__('Page status') . ' ' . $img_status, Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_dt' => (new Para())->items([
                            (new Datetime('post_dt'))
                                ->value(Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', strtotime($post_dt))))
                                ->class(App::backend()->bad_dt ? 'invalid' : [])
                                ->label(new Label(__('Publication date and hour'), Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_lang' => (new Para())->items([
                            (new Select('post_lang'))
                                ->items($lang_combo)
                                ->default($post_lang)
                                ->translate(false)
                                ->label(new Label(__('Page language'), Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_format' => (new Para())->items([
                            (new Select('post_format'))
                                ->items($available_formats)
                                ->default($post_format)
                                ->label((new Label(__('Text formatting'), Label::OUTSIDE_LABEL_BEFORE))->id('label_format')),
                            (new Span())
                                ->class(['format_control', 'control_no_xhtml'])
                                ->items([
                                    (new Link('convert-xhtml'))
                                        ->class(['button', $post_id !== 0 && $post_format === 'xhtml' ? 'hide' : ''])
                                        ->href(My::manageUrl(['part' => 'page', 'id' => $post_id, 'xconv' => '1']))
                                        ->text(__('Convert to HTML')),
                                ]),
                        ])
                        ->render(),
                    ],
                ],

                'metas-box' => [
                    'title' => __('Filing'),
                    'items' => [
                        'post_selected' => (new Para())->items([
                            (new Checkbox('post_selected', (bool) App::backend()->post_selected))
                                ->value(1)
                                ->label(new Label(__('In widget'), Label::INSIDE_TEXT_AFTER)),
                        ])
                        ->render(),
                    ],
                ],

                'options-box' => [
                    'title' => __('Options'),
                    'items' => [
                        'post_password' => (new Para())->items([
                            (new Password('post_password'))
                                ->autocomplete('new-password')
                                ->class('maximal')
                                ->value(Html::escapeHTML($post_password))
                                ->size(10)
                                ->maxlength(32)
                                ->label((new Label(__('Password'), Label::OUTSIDE_TEXT_BEFORE))),
                        ])
                        ->render(),

                        'post_url' => (new Div())->class('lockable')->items([
                            (new Para())->items([
                                (new Input('post_url'))
                                    ->class('maximal')
                                    ->value(Html::escapeHTML($post_url))
                                    ->size(10)
                                    ->maxlength(255)
                                    ->label((new Label(__('Edit basename'), Label::OUTSIDE_TEXT_BEFORE))),
                            ]),
                            (new Note())
                                ->class(['form-note', 'warn'])
                                ->text(__('Warning: If you set the URL manually, it may conflict with another page.')),
                        ])
                        ->render(),
                    ],
                ],
            ]);

            try {
                $post_metas = App::meta()->getMetaRecordset($post_meta, 'related_file');
                if (!$post_metas->isEmpty()) {
                    self::$file_name    = $post_metas->strField('meta_id');
                    self::$page_is_file = true;
                } elseif (empty($_REQUEST['id'])) {
                    self::$file_name    = '';
                    self::$page_is_file = (!empty($_REQUEST['type']) && $_REQUEST['type'] === 'file');
                } else {
                    self::$page_is_file = false;
                }
            } catch (Exception) {
            }

            if (!self::$page_is_file) {
                $main_items = new ArrayObject(
                    [
                        'post_title' => (new Para())->items([
                            (new Input('post_title'))
                                ->value(Html::escapeHTML($post_title))
                                ->size(20)
                                ->maxlength(255)
                                ->required(true)
                                ->class('maximal')
                                ->placeholder(__('Title'))
                                ->lang($post_lang)
                                ->spellcheck(true)
                                ->label(
                                    (new Label(
                                        (new Span('*'))->render() . __('Title:'),
                                        Label::OUTSIDE_TEXT_BEFORE
                                    ))
                                    ->class(['required', 'no-margin', 'bold'])
                                )
                                ->title(__('Required field')),
                        ])
                        ->render(),

                        'post_excerpt' => (new Para())->class('area')->id('excerpt-area')->items([
                            (new Textarea('post_excerpt'))
                                ->value(Html::escapeHTML($post_excerpt))
                                ->cols(50)
                                ->rows(5)
                                ->lang($post_lang)
                                ->spellcheck(true)
                                ->label(
                                    (new Label(
                                        __('Excerpt:') . ' ' . (new Span(__('Introduction to the page.')))->class('form-note')->render(),
                                        Label::OUTSIDE_TEXT_BEFORE
                                    ))
                                    ->class('bold')
                                ),
                        ])
                        ->render(),

                        'post_content' => (new Para())->class('area')->id('content-area')->items([
                            (new Textarea('post_content'))
                                ->value(Html::escapeHTML($post_content))
                                ->cols(50)
                                ->rows($edit_size)
                                ->required(true)
                                ->lang($post_lang)
                                ->spellcheck(true)
                                ->placeholder(__('Content'))
                                ->label(
                                    (new Label(
                                        (new Span('*'))->render() . __('Content:'),
                                        Label::OUTSIDE_TEXT_BEFORE
                                    ))
                                    ->class(['required', 'bold'])
                                ),
                        ])
                        ->render(),

                        'post_notes' => (new Para())->class('area')->id('notes-area')->items([
                            (new Textarea('post_notes'))
                                ->value(Html::escapeHTML($post_notes))
                                ->cols(50)
                                ->rows(5)
                                ->lang($post_lang)
                                ->spellcheck(true)
                                ->label(
                                    (new Label(
                                        __('Personal notes:') . ' ' . (new Span(__('Unpublished notes.')))->class('form-note')->render(),
                                        Label::OUTSIDE_TEXT_BEFORE
                                    ))
                                    ->class('bold')
                                ),
                        ])
                        ->render(),
                    ]
                );
            } else {
                $files_path   = App::blog()->settings()->get('related')->getStr('files_path', false);
                $dir          = @dir($files_path);
                $allowed_exts = ['php', 'html', 'xml', 'txt'];

                self::$files_list = ['-' => ''];

                if ($dir) {
                    while (($entry = $dir->read()) !== false) {
                        $entry_path = $dir->path . '/' . $entry;
                        if (in_array(Files::getExtension($entry), $allowed_exts) && is_file($entry_path) && is_readable($entry_path)) {
                            self::$files_list[$entry] = $entry;
                        }
                    }
                }

                $main_items = new ArrayObject(
                    [
                        'post_title' => (new Para())->items([
                            (new Input('post_title'))
                                ->value(Html::escapeHTML($post_title))
                                ->size(20)
                                ->maxlength(255)
                                ->required(true)
                                ->class('maximal')
                                ->placeholder(__('Title'))
                                ->lang($post_lang)
                                ->spellcheck(true)
                                ->label(
                                    (new Label(
                                        (new Span('*'))->render() . __('Title:'),
                                        Label::OUTSIDE_TEXT_BEFORE
                                    ))
                                    ->class(['required', 'no-margin', 'bold'])
                                )
                                ->title(__('Required field')),
                        ])
                        ->render(),

                        'post_excerpt' => (new Para())->class('area')->id('excerpt-area')->items([
                            (new Textarea('post_excerpt'))
                                ->value(Html::escapeHTML($post_excerpt))
                                ->cols(50)
                                ->rows(5)
                                ->lang($post_lang)
                                ->spellcheck(true)
                                ->label(
                                    (new Label(
                                        __('Excerpt:') . ' ' . (new Span(__('Introduction to the page.')))->class('form-note')->render(),
                                        Label::OUTSIDE_TEXT_BEFORE
                                    ))
                                    ->class('bold')
                                ),
                        ])
                        ->render(),

                        'post_content' => (new Para())->class('hidden')->id('content-area')->items([
                            (new Textarea('post_content'))
                                ->value('/** external content **/')
                                ->cols(50)
                                ->rows($edit_size)
                                ->required(true)
                                ->lang($post_lang)
                                ->spellcheck(true)
                                ->placeholder(__('Content'))
                                ->label(
                                    (new Label(
                                        (new Span('*'))->render() . __('Content:'),
                                        Label::OUTSIDE_TEXT_BEFORE
                                    ))
                                    ->class(['required', 'bold'])
                                ),
                        ])
                        ->render(),

                        'is_file' => (new Label(
                            (new Span('*'))->render() . __('Included file:'),
                            Label::OUTSIDE_TEXT_BEFORE
                        ))
                            ->class(['bold','required'])
                            ->render(),
                        (new Fieldset())->class(['area','related','no-margin'])->id('is_file-area')
                            ->items([
                                (new Div())->items([
                                    (new Para())->items([
                                        (new Select('files_dir'))
                                            ->items(self::$files_list)
                                            ->default(self::$file_name)
                                            ->label(new Label(__('Pick up a file in your repository:'), Label::OUTSIDE_LABEL_BEFORE)),
                                    ]),
                                ]),
                                (new Div())->class(['form-note', 'maximal'])->items([
                                    (new Para())->items([
                                        (new Strong(__('or'))),
                                    ]),
                                ]),
                                (new Div())->items([
                                    (new Para())->items([
                                        (new Input('up_file'))
                                            ->type('file')
                                            ->size(35)
                                            ->label(new Label(__('Pick up a local file:'), Label::OUTSIDE_LABEL_BEFORE)),
                                        (new Hidden(['part'], 'page')),
                                        (new Hidden(['type'], 'file')),
                                        (new Hidden(['id'], 'id')),
                                    ]),
                                ]),
                                (new Note())->class(['form-note'])->text(__('Allowed file extensions : *.php, *.html, *.txt, *.xml')),
                            ])

                        ->render(),

                        'post_notes' => (new Para())->class('area')->id('notes-area')->items([
                            (new Textarea('post_notes'))
                                ->value(Html::escapeHTML($post_notes))
                                ->cols(50)
                                ->rows(5)
                                ->lang($post_lang)
                                ->spellcheck(true)
                                ->label(
                                    (new Label(
                                        __('Personal notes:') . ' ' . (new Span(__('Unpublished notes.')))->class('form-note')->render(),
                                        Label::OUTSIDE_TEXT_BEFORE
                                    ))
                                    ->class('bold')
                                ),
                        ])
                        ->render(),
                    ]
                );
            }

            # --BEHAVIOR-- adminPostFormItems -- ArrayObject, ArrayObject, MetaRecord|null
            App::behavior()->callBehavior('adminPageFormItems', $main_items, $sidebar_items, App::backend()->post ?? null);

            // Prepare main and side parts
            $side_part_items = [];
            foreach ($sidebar_items as $id => $c) {
                $side_part_items[] = (new Div())
                    ->id($id)
                    ->class('sb-box')
                    ->items([
                        (new Text('h4', $c['title'])),
                        (new Text(null, implode('', $c['items']))),
                    ])
                    ->render();
            }

            $side_part = implode('', $side_part_items);
            $main_part = implode('', iterator_to_array($main_items));

            // Prepare buttons
            $buttons   = [];
            $buttons[] = (new Submit(['save'], __('Save') . ' (s)'))
                ->accesskey('s');
            if ($post_id !== 0) {
                $preview_url = App::blog()->url() .
                    App::url()->getURLFor(
                        'relatedpreview',
                        App::auth()->userID() . '/' .
                        Http::browserUID(App::config()->masterKey() . App::auth()->userID() . App::auth()->cryptLegacy((string) App::auth()->userID())) .
                        '/' . $post_url
                    );

                // Prevent browser caching on preview
                $preview_url .= (parse_url($preview_url, PHP_URL_QUERY) ? '&' : '?') . 'rand=' . md5((string) random_int(0, mt_getrandmax()));

                $blank_preview = App::auth()->prefs()->get('interface')->getBool('blank_preview');

                $preview_class  = $blank_preview ? '' : 'modal';
                $preview_target = $blank_preview ? 'target="_blank"' : '';

                $buttons[] = (new Link('post-preview'))
                    ->href($preview_url)
                    ->extra($preview_target)
                    ->class(['button', $preview_class])
                    ->accesskey('p')
                    ->text(__('Preview') . ' (p)');
                $buttons[] = (new Button(['back'], __('Back')))->class(['go-back','reset','hidden-if-no-js']);
            } else {
                $buttons[] = (new Link('post-cancel'))
                    ->href(My::manageUrl(['part' => 'list']))
                    ->class('button')
                    ->accesskey('c')
                    ->text(__('Cancel') . ' (c)');
            }

            if (App::backend()->can_delete) {
                $buttons[] = (new Submit(['delete'], __('Delete')))
                    ->class('delete');
            }

            if ($post_id !== 0) {
                $buttons[] = (new Hidden('id', (string) $post_id));
            }

            $title = $post_id !== 0 ? __('Edit post') : __('New post');

            // Everything is ready, time to display this form
            echo (new Div())
                ->class('multi-part')
                ->title($title)
                ->id('edit-entry')
                ->data([
                    'page-tabs-info'  => ' &rsaquo; ' . App::formater()->getFormaterName($post_format),
                    'page-tabs-class' => 'edit-format-' . $post_format,
                ])
                ->items([
                    (new Form('entry-form'))
                        ->method('post')
                        ->enctype('multipart/form-data')
                        ->action(My::manageUrl(['part' => 'page']))
                        ->fields([
                            (new Div())
                                ->id('entry-wrapper')
                                ->items([
                                    (new Div())
                                        ->id('entry-content')
                                        ->items([
                                            (new Div())
                                                ->class('constrained')
                                                ->items([
                                                    (new Text('h3', __('Edit page')))
                                                        ->class('out-of-screen-if-js'),
                                                    (new Note())
                                                        ->class('form-note')
                                                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                                                    (new Text(null, $main_part)),
                                                    (new Capture(App::behavior()->callBehavior(...), ['adminPageForm', App::backend()->post ?? null])),
                                                    (new Para())
                                                        ->class(['border-top', 'form-buttons'])
                                                        ->items([
                                                            ...My::hiddenFields([
                                                                'part' => 'page',
                                                                'id'   => '']),
                                                            ...$buttons,
                                                        ]),
                                                    (new Capture(App::behavior()->callBehavior(...), ['adminPageAfterButtons', App::backend()->post ?? null])),
                                                ]),
                                        ]),
                                ]),
                            (new Div())
                                ->id('entry-sidebar')
                                ->role('complementary')
                                ->items([
                                    (new Text(null, $side_part)),
                                    (new Capture(App::behavior()->callBehavior(...), ['adminPageFormSidebar', App::backend()->post ?? null])),
                                ]),
                        ]),
                    (new Capture(App::behavior()->callBehavior(...), ['adminPageAfterForm', App::backend()->post ?? null])),
                ])
            ->render();

            // Attachment removing form
            if ($post_id !== 0 && !empty(App::backend()->post_media)) {
                echo (new Form('attachment-remove-hide'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.post.media'))
                    ->fields([
                        App::nonce()->formNonce(),
                        (new Hidden(['post_id'], (string) $post_id)),
                        (new Hidden(['media_id'], '')),
                        (new Hidden(['remove'], '1')),
                    ])
                ->render();
            }
        }

        App::backend()->page()->helpBlock('related_pages_edit', 'core_wiki');

        App::backend()->page()->closeModule();
    }
}
