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

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Listing\Listing;
use Dotclear\Core\Backend\Listing\Pager;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Caption;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Component;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Timestamp;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;

/**
 * @brief   The module backend pages listing.
 * @ingroup pages
 */
class BackendList extends Listing
{
    /**
     * Display a list of pages.
     *
     * @param   int     $page           The page
     * @param   int     $nb_per_page    The number of per page
     * @param   string  $enclose_block  The enclose block
     * @param   bool    $include_type   Include the post type column
     */
    public function display(int $page, int $nb_per_page, string $enclose_block = '', bool $filter = false, bool $include_type = false): void
    {
        if ($this->rs->isEmpty()) {
            echo (new Para())
                ->items([
                    (new Text('strong', $filter ? __('No entry matches the filter') : __('No entry'))),
                ])
            ->render();

            return;
        }

        $pager   = (new Pager($page, $this->rs_count, $nb_per_page, 10))->getLinks();
        $entries = [];
        if (isset($_REQUEST['entries']) && is_array($_REQUEST['entries'])) {
            foreach ($_REQUEST['entries'] as $v) {
                if (is_numeric($v)) {
                    $entries[(int) $v] = true;
                }
            }
        }

        $cols = [
            'title' => (new Th())
                ->scope('col')
                ->colspan(3)
                ->class('first')
                ->text(__('Title')),
            'date' => (new Th())
                ->scope('col')
                ->text(__('Date')),
            'author' => (new Th())
                ->scope('col')
                ->text(__('Author')),
            'status' => (new Th())
                ->scope('col')
                ->text(__('Status')),
        ];

        if ($include_type) {
            $cols = array_merge($cols, [
                'type' => (new Th())
                    ->scope('col')
                    ->text(__('Type')),
            ]);
        }

        /**
         * @var ArrayObject<string, Component>
         */
        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminPagesListHeaderV2 -- MetaRecord, ArrayObject<string, mixed>, bool
        App::behavior()->callBehavior('adminPagesListHeaderV2', $this->rs, $cols, true);

        // Cope with optional columns
        $this->userColumns('related', $cols, true);

        // Prepare listing
        $lines = [];
        $types = [];
        $count = 0;
        while ($this->rs->fetch()) {
            $post_id = $this->rs->intField('post_id');
            $lines[] = $this->postLine($count, isset($entries[$post_id]), $include_type);
            if (!in_array($this->rs->post_type, $types)) {
                $types[] = $this->rs->post_type;
                $count++;
            }
        }

        if ($filter) {
            $caption = sprintf(
                __('List of %s entry matching the filter.', 'List of %s entries matching the filter.', $this->rs_count),
                $this->rs_count
            );
        } elseif (count($types) === 1) {
            $stats = [
                (new Text(null, sprintf((__('List of entries (%s)')), $this->rs_count))),
            ];
            foreach (App::status()->post()->dump(false) as $status) {
                $nb = (int) App::blog()->getPosts(['post_status' => $status->level()], true)->cardinal();
                if ($nb !== 0) {
                    $stats[] = (new Set())
                        ->separator(' ')
                        ->items([
                        ]);
                }
            }

            $caption = (new Set())
                ->separator('')
                ->items($stats)
            ->render();
        } else {
            // Different types of entries
            $caption = sprintf(__('List of entries (%s)'), $this->rs_count);
        }

        $fmt = fn (string $title, string $image, string $class): string => sprintf(
            (new Img('images/%2$s'))
                    ->alt('%1$s')
                    ->class(['mark', 'mark-%3$s'])
                    ->render() . ' %1$s',
            $title,
            $image,
            $class
        );

        $buffer = (new Div())
            ->class('table-outer')
            ->items([
                (new Table())
                    ->class(['maximal', 'dragable'])
                    ->caption(new Caption($caption))
                    ->items([
                        (new Thead())
                            ->rows([
                                (new Tr())
                                    ->items($cols),
                            ]),
                        (new Tbody())
                            ->id('pageslist')
                            ->rows($lines),
                    ]),
                (new Para())
                    ->class('info')
                    ->items([
                        (new Text(
                            null,
                            __('Legend: ') .
                            $fmt(__('Published'), 'published.svg', 'published') . ' - ' .
                            $fmt(__('Unpublished'), 'unpublished.svg', 'unpublished') . ' - ' .
                            $fmt(__('Scheduled'), 'scheduled.svg', 'scheduled') . ' - ' .
                            $fmt(__('Pending'), 'pending.svg', 'pending') . ' - ' .
                            $fmt(__('Protected'), 'locker.svg', 'locked') . ' - ' .
                            $fmt(__('Attachments'), 'attach.svg', 'attach') . ' - ' .
                            $fmt(__('In widget'), 'selected.svg', 'selected')
                        )),
                    ]),
            ])
        ->render();
        if ($enclose_block !== '') {
            $buffer = sprintf($enclose_block, $buffer);
        }

        echo $pager . $buffer . $pager;
    }

    /**
     * Return a page line.
     *
     * @param   int     $count          The count
     * @param   bool    $checked        The checked
     * @param   bool    $include_type   Include the post type column
     */
    private function postLine(int $count, bool $checked, bool $include_type): Tr
    {
        $img = (new Img('images/%2$s'))
            ->alt('%1$s')
            ->title('%1$s')
            ->class(['mark', 'mark-%3$s'])
            ->render();

        $post_classes = ['line'];

        $post_status = $this->rs->intField('post_status');
        if (App::status()->post()->isRestricted($post_status)) {
            $post_classes[] = 'offline';
        }

        $img_status = '';
        switch ($post_status) {
            case App::status()->post()::PUBLISHED:
                $img_status     = sprintf($img, __('Published'), 'published.svg', 'published');
                $post_classes[] = 'sts-online';

                break;
            case App::status()->post()::UNPUBLISHED:
                $img_status     = sprintf($img, __('Unpublished'), 'unpublished.svg', 'unpublished');
                $post_classes[] = 'sts-offline';

                break;
            case App::status()->post()::SCHEDULED:
                $img_status     = sprintf($img, __('Scheduled'), 'scheduled.svg', 'scheduled');
                $post_classes[] = 'sts-scheduled';

                break;
            case App::status()->post()::PENDING:
                $img_status     = sprintf($img, __('Pending'), 'pending.svg', 'pending');
                $post_classes[] = 'sts-pending';

                break;
        }

        $protected = '';
        if ($this->rs->post_password) {
            $protected = sprintf($img, __('Protected'), 'locker.svg', 'locked');
        }

        $selected = '';
        if ($this->rs->post_selected) {
            $selected = sprintf($img, __('In widget'), 'selected.svg', 'selected');
        }

        $attach   = '';
        $nb_media = is_numeric($nb_media = $this->rs->countMedia()) ? (int) $nb_media : 0;
        if ($nb_media > 0) {
            $attach_str = $nb_media === 1 ? __('%d attachment') : __('%d attachments');
            $attach     = sprintf($img, sprintf($attach_str, $nb_media), 'attach.svg', 'attach');
        }

        $pos_classes = ['nowrap', 'minimal'];
        if (!App::auth()->prefs()->accessibility->nodragdrop) {
            $pos_classes[] = 'handle';
        }

        $post_id    = $this->rs->intField('post_id');
        $post_title = $this->rs->strField('post_title');
        $post_type  = $this->rs->strField('post_type');
        $post_dt    = $this->rs->strField('post_dt');
        $user_id    = $this->rs->strField('user_id');

        $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';

        $cols = [
            'position' => (new Td())
                ->class($pos_classes)->items([
                    (new Number(['order[' . $post_id . ']'], 1))
                        ->value($count + 1)
                        ->class('position')
                        ->title(sprintf(__('position of %s'), Html::escapeHTML($post_title))),
                ]),
            'check' => (new Td())
                ->class('nowrap')
                ->items([
                    (new Checkbox(['entries[]'], $checked))
                        ->value($post_id)
                        ->disabled(!$this->rs->isEditable())
                        ->title(__('Select this page')),
                ]),
            'title' => (new Td())
                ->class('maximal')
                ->items([
                    (new Link())
                        ->href(App::postTypes()->get($post_type)->adminUrl($post_id))
                        ->text(Html::escapeHTML($post_title)),
                ]),
            'date' => (new Td())
                ->class(['nowrap', 'count'])
                ->items([
                    (new Timestamp(Date::dt2str(__('%Y-%m-%d %H:%M'), $post_dt)))
                        ->datetime(Date::iso8601((int) strtotime((string) $post_dt), $user_tz)),
                ]),
            'author' => (new Td())
                ->class('nowrap')
                ->text($user_id),
            'status' => (new Td())
                ->class(['nowrap', 'status'])
                ->text($img_status . ' ' . $selected . ' ' . $protected . ' ' . $attach),
        ];

        if ($include_type) {
            $cols = array_merge($cols, [
                'type' => (new Td())
                    ->class(['nowrap', 'status'])
                    ->separator(' ')
                    ->items([
                        App::postTypes()->image($post_type),
                    ]),
            ]);
        }

        /**
         * @var ArrayObject<string, Component>
         */
        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminPagesListValueV2 -- MetaRecord, ArrayObject<string, mixed>, bool
        App::behavior()->callBehavior('adminPagesListValueV2', $this->rs, $cols, true);

        // Cope with optional columns
        $this->userColumns('related', $cols);

        return (new Tr())
            ->id('p' . $post_id)
            ->class($post_classes)
            ->items($cols);
    }
}
