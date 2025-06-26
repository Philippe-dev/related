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
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
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
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\pages\Pages;
use Exception;

/**
 * @brief   The module backend manage page process.
 * @ingroup pages
 */
class ManagePage extends Process
{
    private static bool $pageIsFile           = true;
    private static string $page_related_file  = '';
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

        $pageIsFile = (!empty($_REQUEST['type']) && $_REQUEST['type'] === 'file');

        $params = [];
        Page::check(App::auth()->makePermissions([
            Pages::PERMISSION_PAGES,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        Date::setTZ(App::auth()->getInfo('user_tz') ?? 'UTC');

        App::backend()->post_id            = '';
        App::backend()->post_dt            = '';
        App::backend()->post_format        = App::auth()->getOption('post_format');
        App::backend()->post_editor        = App::auth()->getOption('editor');
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

        App::backend()->page_title = __('New related page');

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

        App::backend()->next_link = App::backend()->prev_link = App::backend()->next_headlink = App::backend()->prev_headlink = null;

        // If user can't publish
        if (!App::backend()->can_publish) {
            App::backend()->post_status = App::status()->post()::PENDING;
        }

        // Status combo
        App::backend()->status_combo = App::status()->post()->combo();

        // Formaters combo
        $core_formaters    = App::formater()->getFormaters();
        $available_formats = ['' => ''];
        foreach ($core_formaters as $formats) {
            foreach ($formats as $format) {
                $available_formats[App::formater()->getFormaterName($format)] = $format;
            }
        }
        App::backend()->available_formats = $available_formats;

        // Languages combo
        App::backend()->lang_combo = Combos::getLangsCombo(
            App::blog()->getLangs([
                'order_by' => 'nb_post',
                'order'    => 'desc',
            ]),
            true
        );

        // Validation flag
        App::backend()->bad_dt = false;

        $pageIsFile = (!empty($_REQUEST['type']) && $_REQUEST['type'] === 'file');

        // Get page informations

        App::backend()->post = null;
        if (!empty($_REQUEST['id'])) {
            $params['post_type'] = 'related';
            $params['post_id']   = $_REQUEST['id'];

            App::backend()->post = App::blog()->getPosts($params);

            if (App::backend()->post->isEmpty()) {
                Notices::addErrorNotice(__('This page does not exist.'));
                My::redirect();
            } else {
                App::backend()->post_id            = (int) App::backend()->post->post_id;
                App::backend()->post_dt            = date('Y-m-d H:i', (int) strtotime(App::backend()->post->post_dt));
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
                App::backend()->post_position      = (int) App::backend()->post->post_position;
                App::backend()->post_open_comment  = (bool) App::backend()->post->post_open_comment;
                App::backend()->post_open_tb       = (bool) App::backend()->post->post_open_tb;
                App::backend()->post_selected      = (bool) App::backend()->post->post_selected;
                App::backend()->post_meta          = App::backend()->post->post_meta;

                App::backend()->page_title = __('Edit related page');

                App::backend()->can_edit_page = App::backend()->post->isEditable();
                App::backend()->can_delete    = App::backend()->post->isDeletable();

                $next_rs = App::blog()->getNextPost(App::backend()->post, 1);
                $prev_rs = App::blog()->getNextPost(App::backend()->post, -1);

                if ($next_rs instanceof MetaRecord) {
                    App::backend()->next_link = sprintf(
                        App::backend()->post_link,
                        $next_rs->post_id,
                        'next',
                        Html::escapeHTML(trim(Html::clean($next_rs->post_title))),
                        __('Next page') . '&nbsp;&#187;'
                    );
                    App::backend()->next_headlink = sprintf(
                        $post_headlink,
                        'next',
                        Html::escapeHTML(trim(Html::clean($next_rs->post_title))),
                        $next_rs->post_id
                    );
                }

                if ($prev_rs instanceof MetaRecord) {
                    App::backend()->prev_link = sprintf(
                        App::backend()->post_link,
                        $prev_rs->post_id,
                        'prev',
                        Html::escapeHTML(trim(Html::clean($prev_rs->post_title))),
                        '&#171;&nbsp;' . __('Previous page')
                    );
                    App::backend()->prev_headlink = sprintf(
                        $post_headlink,
                        'previous',
                        Html::escapeHTML(trim(Html::clean($prev_rs->post_title))),
                        $prev_rs->post_id
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
                App::backend()->post_status = (int) $_POST['post_status'];
            }

            if (empty($_POST['post_dt'])) {
                App::backend()->post_dt = '';
            } else {
                try {
                    App::backend()->post_dt = strtotime((string) $_POST['post_dt']);
                    if (!App::backend()->post_dt || App::backend()->post_dt == -1) {
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
                App::backend()->post_excerpt,
                App::backend()->post_excerpt_xhtml,
                App::backend()->post_content,
                App::backend()->post_content_xhtml,
            ];

            App::blog()->setPostContent(
                (int) App::backend()->post_id,
                App::backend()->post_format,
                App::backend()->post_lang,
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

            if ($pageIsFile) {
                $dir          = @dir((string) App::blog()->settings()->related->files_path);
                $allowed_exts = ['php', 'html', 'xml', 'txt'];

                if ($dir) {
                    while (($entry = $dir->read()) !== false) {
                        $entry_path = $dir->path . '/' . $entry;
                        if (in_array(Files::getExtension($entry), $allowed_exts)) {
                            if (is_file($entry_path) && is_readable($entry_path)) {
                                $related_pages_files[$entry] = $entry;
                            }
                        }
                    }
                }

                $related_upl = null;
                if (!empty($_FILES['up_file']['name'])) {
                    $related_upl = true;
                } elseif (!empty($_POST['repository_file']) && in_array($_POST['repository_file'], $related_pages_files)) {
                    $related_upl = false;
                }

                if (!is_null($related_upl)) {
                    try {
                        if ($related_upl) {
                            Files::uploadStatus($_FILES['up_file']);
                            $src_file = $_FILES['up_file']['tmp_name'];
                            $trg_file = App::blog()->settings()->related->files_path . '/' . $_FILES['up_file']['name'];
                            if (move_uploaded_file($src_file, $trg_file)) {
                                $page_related_file = $_FILES['up_file']['name'];
                            }
                        } else {
                            $page_related_file = $_POST['repository_file'];
                        }
                    } catch (Exception $e) {
                        Notices::addErrorNotice($e->getMessage());
                    }
                }
            }

            $cur = App::blog()->openPostCursor();

            // Magic tweak :)
            App::blog()->settings()->system->post_url_format = '{t}';

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

                    App::con()->begin();
                    if ($pageIsFile) {
                        try {
                            App::meta()->delPostMeta(App::backend()->post_id, 'related_file');
                            App::meta()->setPostMeta(App::backend()->post_id, 'related_file', $page_related_file);
                        } catch (Exception $e) {
                            App::con()->rollback();

                            throw $e;
                        }
                    }
                    App::con()->commit();

                    # --BEHAVIOR-- adminAfterPageUpdate -- Cursor, int
                    App::behavior()->callBehavior('adminAfterPageUpdate', $cur, App::backend()->post_id);

                    Notices::addSuccessNotice(__('Page has been updated.'));
                    My::redirect(['part' => 'page', 'id' => App::backend()->post_id]);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            } else {
                $cur->user_id = App::auth()->userID();

                try {
                    # --BEHAVIOR-- adminBeforePageCreate -- Cursor
                    App::behavior()->callBehavior('adminBeforePageCreate', $cur);

                    # --BEHAVIOR-- adminAfterPageCreate -- Cursor, int
                    App::behavior()->callBehavior('adminAfterPageCreate', $cur, $return_id);

                    $return_id = App::blog()->addPost($cur);

                    App::con()->begin();

                    try {
                        App::meta()->setPostMeta($return_id, 'related_file', $page_related_file);
                    } catch (Exception $e) {
                        App::con()->rollback();

                        throw $e;
                    }
                    App::con()->commit();

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

        if (App::backend()->comments_actions_page_rendered) {
            App::backend()->comments_actions_page->render();

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
            $convert = Html::escapeHTML($_GET['convert-format']);

            # --BEHAVIOR-- adminConvertBeforePostEdit -- ArrayObject
            $msg = App::behavior()->callBehavior('adminConvertBeforePostEdit', $convert, $params);
            if ($msg !== '') {
                App::backend()->post_excerpt = $params['excerpt'];
                App::backend()->post_content = $params['content'];
                App::backend()->post_format  = $params['format'];

                Notices::addMessageNotice($msg);
            }
        }

        $admin_post_behavior = '';
        if (App::backend()->post_editor) {
            $p_edit = $c_edit = '';
            if (!empty(App::backend()->post_editor[App::backend()->post_format])) {
                $p_edit = App::backend()->post_editor[App::backend()->post_format];
            }
            if (!empty(App::backend()->post_editor['xhtml'])) {
                $c_edit = App::backend()->post_editor['xhtml'];
            }
            if ($p_edit == $c_edit) {
                # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'page',
                    ['#post_excerpt', '#post_content', '#comment_content'],
                    App::backend()->post_format
                );
            } else {
                # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'page',
                    ['#post_excerpt', '#post_content'],
                    App::backend()->post_format
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

        Page::openModule(
            App::backend()->page_title . ' - ' . My::name(),
            Page::jsModal() .
            Page::jsJson('pages_page', ['confirm_delete_post' => __('Are you sure you want to delete this page?')]) .
            Page::jsLoad('js/_post.js') .
            My::jsLoad('page') .
            $admin_post_behavior .
            Page::jsConfirmClose('entry-form', 'comment-form') .
            # --BEHAVIOR-- adminPageHeaders --
            App::behavior()->callBehavior('adminPageHeaders') .
            Page::jsPageTabs(App::backend()->default_tab) .
            App::backend()->next_headlink . "\n" . App::backend()->prev_headlink
        );

        if (App::backend()->post_id) {
            $img_status       = App::status()->post()->image((int) App::backend()->post_status)->render();
            $edit_entry_title = '&ldquo;' . Html::escapeHTML(trim(Html::clean(App::backend()->post_title))) . '&rdquo;' . ' ' . $img_status;
        } else {
            $img_status       = '';
            $edit_entry_title = App::backend()->page_title;
        }

        echo Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                My::name()                            => App::backend()->getPageURL(),
                $edit_entry_title                     => '',
            ]
        );

        if (!empty($_GET['upd'])) {
            Notices::success(__('Page has been successfully updated.'));
        } elseif (!empty($_GET['crea'])) {
            Notices::success(__('Page has been successfully created.'));
        } elseif (!empty($_GET['attached'])) {
            Notices::success(__('File has been successfully attached.'));
        } elseif (!empty($_GET['rmattach'])) {
            Notices::success(__('Attachment has been successfully removed.'));
        }

        # HTML conversion
        if (!empty($_GET['xconv'])) {
            App::backend()->post_excerpt = App::backend()->post_excerpt_xhtml;
            App::backend()->post_content = App::backend()->post_content_xhtml;
            App::backend()->post_format  = 'xhtml';

            Notices::message(__('Don\'t forget to validate your HTML conversion by saving your post.'));
        }

        if (App::backend()->post_id && !App::status()->post()->isRestricted((int) App::backend()->post->post_status)) {
            echo (new Para())
                ->items([
                    (new Link())
                        ->class(['onblog_link', 'outgoing'])
                        ->href(App::backend()->post->getURL())
                        ->title(Html::escapeHTML(trim(Html::clean(App::backend()->post_title))))
                        ->text(__('Go to this page on the site') . ' ' . (new Img('images/outgoing-link.svg'))->render()),
                ])
            ->render();
        }

        if (App::backend()->post_id) {
            $items = [];
            if (App::backend()->prev_link) {
                $items[] = new Text(null, App::backend()->prev_link);
            }
            if (App::backend()->next_link) {
                $items[] = new Text(null, App::backend()->next_link);
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
            Page::closeModule();

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
                                ->items(App::backend()->status_combo)
                                ->default(App::backend()->post_status)
                                ->disabled(!App::backend()->can_publish)
                                ->label(new Label(__('Page status') . ' ' . $img_status, Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_dt' => (new Para())->items([
                            (new Datetime('post_dt'))
                                ->value(Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', strtotime(App::backend()->post_dt))))
                                ->class(App::backend()->bad_dt ? 'invalid' : [])
                                ->label(new Label(__('Publication date and hour'), Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_lang' => (new Para())->items([
                            (new Select('post_lang'))
                                ->items(App::backend()->lang_combo)
                                ->default(App::backend()->post_lang)
                                ->translate(false)
                                ->label(new Label(__('Page language'), Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_format' => (new Para())->items([
                            (new Select('post_format'))
                                ->items(App::backend()->available_formats)
                                ->default(App::backend()->post_format)
                                ->label((new Label(__('Text formatting'), Label::OUTSIDE_LABEL_BEFORE))->id('label_format')),
                            (new Span())
                                ->class(['format_control', 'control_no_xhtml'])
                                ->items([
                                    (new Link('convert-xhtml'))
                                        ->class(['button', App::backend()->post_id && App::backend()->post_format === 'xhtml' ? 'hide' : ''])
                                        ->href(My::manageUrl(['part' => 'page', 'id' => App::backend()->post_id, 'xconv' => '1']))
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
                            (new Checkbox('post_selected', App::backend()->post_selected))
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
                                ->value(Html::escapeHTML(App::backend()->post_password))
                                ->size(10)
                                ->maxlength(32)
                                ->label((new Label(__('Password'), Label::OUTSIDE_TEXT_BEFORE))),
                        ])
                        ->render(),

                        'post_url' => (new Div())->class('lockable')->items([
                            (new Para())->items([
                                (new Input('post_url'))
                                    ->class('maximal')
                                    ->value(Html::escapeHTML(App::backend()->post_url))
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
                $post_metas = App::meta()->getMetaRecordset(App::backend()->post_meta, 'related_file');
                if (!$post_metas->isEmpty()) {
                    $page_related_file = $post_metas->meta_id;
                    $pageIsFile        = true;
                } elseif (empty($_REQUEST['id'])) {
                    $page_related_file = '';
                    $pageIsFile        = true;
                } else {
                    $pageIsFile = false;
                }
            } catch (Exception) {
            }

            if (!$pageIsFile) {
                $main_items = new ArrayObject(
                    [
                        'post_title' => (new Para())->items([
                            (new Input('post_title'))
                                ->value(Html::escapeHTML(App::backend()->post_title))
                                ->size(20)
                                ->maxlength(255)
                                ->required(true)
                                ->class('maximal')
                                ->placeholder(__('Title'))
                                ->lang(App::backend()->post_lang)
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
                                ->value(Html::escapeHTML(App::backend()->post_excerpt))
                                ->cols(50)
                                ->rows(5)
                                ->lang(App::backend()->post_lang)
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
                                ->value(Html::escapeHTML(App::backend()->post_content))
                                ->cols(50)
                                ->rows(App::auth()->getOption('edit_size'))
                                ->required(true)
                                ->lang(App::backend()->post_lang)
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
                                ->value(Html::escapeHTML(App::backend()->post_notes))
                                ->cols(50)
                                ->rows(5)
                                ->lang(App::backend()->post_lang)
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
                $dir          = @dir((string) App::blog()->settings()->related->files_path);
                $allowed_exts = ['php', 'html', 'xml', 'txt'];

                if ($dir) {
                    while (($entry = $dir->read()) !== false) {
                        $entry_path = $dir->path . '/' . $entry;
                        if (in_array(Files::getExtension($entry), $allowed_exts)) {
                            if (is_file($entry_path) && is_readable($entry_path)) {
                                $related_pages_files[$entry] = $entry;
                            }
                        }
                    }
                }

                $main_items = new ArrayObject(
                    [
                        'post_title' => (new Para())->items([
                            (new Input('post_title'))
                                ->value(Html::escapeHTML(App::backend()->post_title))
                                ->size(20)
                                ->maxlength(255)
                                ->required(true)
                                ->class('maximal')
                                ->placeholder(__('Title'))
                                ->lang(App::backend()->post_lang)
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
                                ->value(Html::escapeHTML(App::backend()->post_excerpt))
                                ->cols(50)
                                ->rows(5)
                                ->lang(App::backend()->post_lang)
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
                                ->rows(App::auth()->getOption('edit_size'))
                                ->required(true)
                                ->lang(App::backend()->post_lang)
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

                        'is_file' => (new Label(__('Content:'), Label::OUTSIDE_TEXT_BEFORE))
                            ->class(['bold'])->render(),
                        (new Fieldset())->class('area')->id('is_file-area')
                            ->items([
                                (new Para())->items([
                                    (new Select('repository_file'))
                                        ->items($related_pages_files)
                                        ->default($page_related_file)
                                        ->label(new Label(__('Pick up a local file in your related pages repository'), Label::OUTSIDE_LABEL_BEFORE)),
                                ]),
                                (new Para())->items([
                                    (new Input('up_file'))
                                        ->type('file')
                                        ->size(35)
                                        ->label(new Label(__('Choose a file:') . ' (' . sprintf(__('Maximum size %s'), Files::size(App::config()->maxUploadSize())) . ')', Label::IL_TF)),
                                    (new Hidden(['part'], 'page')),
                                    (new Hidden(['type'], 'file')),
                                    (new Hidden(['id'], 'id')),
                                ]),
                            ])

                        ->render(),

                        'post_notes' => (new Para())->class('area')->id('notes-area')->items([
                            (new Textarea('post_notes'))
                                ->value(Html::escapeHTML(App::backend()->post_notes))
                                ->cols(50)
                                ->rows(5)
                                ->lang(App::backend()->post_lang)
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
            if (App::backend()->post_id) {
                $preview_url = App::blog()->url() .
                    App::url()->getURLFor(
                        'relatedpreview',
                        App::auth()->userID() . '/' .
                        Http::browserUID(App::config()->masterKey() . App::auth()->userID() . App::auth()->cryptLegacy((string) App::auth()->userID())) .
                        '/' . App::backend()->post->post_url
                    );

                // Prevent browser caching on preview
                $preview_url .= (parse_url($preview_url, PHP_URL_QUERY) ? '&' : '?') . 'rand=' . md5((string) random_int(0, mt_getrandmax()));

                $blank_preview = App::auth()->prefs()->interface->blank_preview;

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
            if (App::backend()->post_id) {
                $buttons[] = (new Hidden('id', (string) App::backend()->post_id));
            }

            $format = (new Span(' &rsaquo; ' . App::formater()->getFormaterName(App::backend()->post_format)));
            $title  = (App::backend()->post_id ? __('Edit page') : __('New page')) . $format->render();

            // Everything is ready, time to display this form
            echo (new Div())
                ->class('multi-part')
                ->title($title)
                ->id('edit-entry')
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
            if (App::backend()->post_id && !empty(App::backend()->post_media)) {
                echo (new Form('attachment-remove-hide'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.post.media'))
                    ->fields([
                        App::nonce()->formNonce(),
                        (new Hidden(['post_id'], (string) App::backend()->post_id)),
                        (new Hidden(['media_id'], '')),
                        (new Hidden(['remove'], '1')),
                    ])
                ->render();
            }
        }

        Page::helpBlock('related_pages_edit', 'core_wiki');

        Page::closeModule();
    }
}
