<?php
/**
 * @brief related, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Pep, Nicolas Roudaire and contributors
 *
 * @copyright GPL-2.0 [https://www.gnu.org/licenses/gpl-2.0.html]
 */

declare(strict_types=1);

namespace Dotclear\Plugin\related;

use ArrayObject;
use Exception;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Filter\Filter;
use Dotclear\Core\Backend\Filter\Filters;
use Dotclear\Core\Backend\Filter\FiltersLibrary;
use Dotclear\App;

class FilterPages extends Filters
{
    public function __construct(private string $post_type = 'related')
    {
        parent::__construct('posts');

        $this->add((new Filter('post_type', $post_type))->param('post_type'));

        $filters = new ArrayObject([
            FiltersLibrary::getPageFilter(),
            $this->getPostUserFilter(),
            $this->getPostStatusFilter(),
            $this->getPostSelectedFilter(),
            $this->getPostMonthFilter(),
            $this->getPostLangFilter(),
        ]);

        $filters = $filters->getArrayCopy();

        $this->add($filters);
    }

    public function getPostUserFilter(): ?Filter
    {
        $users = null;

        try {
            $users = App::blog()->getPostsUsers($this->post_type);
            if ($users->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return null;
        }

        $combo = Combos::getUsersCombo($users);
        App::lexical()->lexicalKeySort($combo, App::lexical()::ADMIN_LOCALE);

        return (new Filter('user_id'))
            ->param()
            ->title(__('Author:'))
            ->options(array_merge(
                ['-' => ''],
                $combo
            ))
            ->prime(true);
    }

    public function getPostStatusFilter(): Filter
    {
        return (new Filter('status'))
            ->param('post_status')
            ->title(__('Status:'))
            ->options(array_merge(
                ['-' => ''],
                Combos::getPostStatusesCombo()
            ))
            ->prime(true);
    }

    public function getPostMonthFilter(): ?Filter
    {
        $dates = null;

        try {
            $dates = App::blog()->getDates([
                'type'      => 'month',
                'post_type' => $this->post_type,
            ]);
            if ($dates->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return null;
        }

        return (new Filter('month'))
            ->param('post_month', fn ($f) => substr($f[0], 4, 2))
            ->param('post_year', fn ($f) => substr($f[0], 0, 4))
            ->title(__('Month:'))
            ->options(array_merge(
                ['-' => ''],
                Combos::getDatesCombo($dates)
            ));
    }

    public function getPostLangFilter(): ?Filter
    {
        $langs = null;

        try {
            $langs = App::blog()->getLangs(['post_type' => $this->post_type]);
            if ($langs->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return null;
        }

        return (new Filter('lang'))
            ->param('post_lang')
            ->title(__('Lang:'))
            ->options(array_merge(
                ['-' => ''],
                Combos::getLangsCombo($langs, false)
            ));
    }

    public function getPostSelectedFilter(): Filter
    {
        return (new Filter('selected'))
            ->param('post_selected')
            ->title(__('In widget:'))
            ->options([
                '-'       => '',
                __('yes') => '1',
                __('no')  => '0',
            ]);
    }
}
