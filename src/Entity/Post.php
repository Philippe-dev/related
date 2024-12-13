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

namespace Dotclear\Plugin\related\Entity;

use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;

class Post
{
    private ?MetaRecord $post_meta_record = null;

    private string $post_id            = '';
    private ?string $cat_id            = '';
    private string $post_dt            = '';
    private string $post_type          = 'related';
    private string $post_format        = '';
    private ?string $post_password     = null;
    private string $post_url           = '';
    private string $post_lang          = '';
    private string $post_title         = '';
    private string $post_excerpt       = '';
    private string $post_excerpt_xhtml = '';
    private string $post_content       = '';
    private string $post_content_xhtml = '';
    private string $post_notes         = '';
    private int $post_status           = 0;
    private bool $post_selected        = true;

    public function __construct()
    {
    }

    public function fromMetaRecord(MetaRecord $post): self
    {
        $this->post_meta_record = $post;

        $this->post_id            = $post->post_id;
        $this->post_dt            = date('Y-m-d H:i', strtotime($post->post_dt));
        $this->post_selected      = (bool) $post->post_selected;
        $this->cat_id             = $post->cat_id;
        $this->post_format        = $post->post_format;
        $this->post_password      = $post->post_password;
        $this->post_url           = $post->post_url;
        $this->post_lang          = $post->post_lang;
        $this->post_title         = $post->post_title;
        $this->post_excerpt       = $post->post_excerpt;
        $this->post_excerpt_xhtml = $post->post_excerpt_xhtml;
        $this->post_content       = $post->post_content;
        $this->post_content_xhtml = $post->post_content_xhtml;
        $this->post_notes         = $post->post_notes;
        $this->post_status        = (int) $post->post_status;

        return $this;
    }

    public function toMetaRecord(): ?MetaRecord
    {
        return $this->post_meta_record;
    }

    public function setCursor(Cursor $cur): void
    {
        $cur->post_title         = $this->post_title;
        $cur->cat_id             = null;
        $cur->post_dt            = $this->post_dt;
        $cur->post_type          = $this->post_type;
        $cur->post_format        = $this->post_format;
        $cur->post_password      = $this->post_password;
        $cur->post_lang          = $this->post_lang;
        $cur->post_excerpt       = $this->post_excerpt;
        $cur->post_excerpt_xhtml = $this->post_excerpt_xhtml;
        $cur->post_content       = $this->post_content;
        $cur->post_content_xhtml = $this->post_content_xhtml;
        $cur->post_notes         = $this->post_notes;
        $cur->post_status        = $this->post_status;
        $cur->post_selected      = (int) $this->post_selected;
        $cur->post_url           = $this->post_url;
        $cur->post_open_comment  = 0;
        $cur->post_open_tb       = 0;
    }

    public function getPostId(): string
    {
        return $this->post_id;
    }

    public function setPostFormat(string $post_format): self
    {
        $this->post_format = $post_format;

        return $this;
    }

    public function getPostFormat(): string
    {
        return $this->post_format;
    }

    public function setPostPassword(string $post_password): self
    {
        $this->post_password = $post_password;

        return $this;
    }

    public function getPostPassword(): ?string
    {
        return $this->post_password;
    }

    public function setPostTitle(string $post_title): self
    {
        $this->post_title = $post_title;

        return $this;
    }

    public function getPostTitle(): string
    {
        return $this->post_title;
    }

    public function setPostContent(string $post_content): self
    {
        $this->post_content = $post_content;

        return $this;
    }

    public function getPostContent(): string
    {
        return $this->post_content;
    }

    public function setPostExcerpt(string $post_excerpt): self
    {
        $this->post_excerpt = $post_excerpt;

        return $this;
    }

    public function getPostExcerpt(): string
    {
        return $this->post_excerpt;
    }

    public function getPostStatus(): int
    {
        return $this->post_status;
    }

    public function setPostStatus(int $post_status): self
    {
        $this->post_status = $post_status;

        return $this;
    }

    public function getPostUrl(): string
    {
        return $this->post_url;
    }

    public function setPostUrl(string $post_url): self
    {
        $this->post_url = $post_url;

        return $this;
    }

    public function getPostNotes(): string
    {
        return $this->post_notes;
    }

    public function setPostNotes(string $post_notes): self
    {
        $this->post_notes = $post_notes;

        return $this;
    }

    public function getCat(): string
    {
        return $this->cat_id;
    }

    public function setCat(string $cat_id): self
    {
        $this->cat_id = $cat_id;

        return $this;
    }

    public function getPostDate(): string
    {
        return $this->post_dt;
    }

    public function setPostDate(string $post_dt): self
    {
        $this->post_dt = $post_dt;

        return $this;
    }

    public function getPostType(): string
    {
        return $this->post_type;
    }

    public function setPostType(string $post_type): self
    {
        $this->post_type = $post_type;

        return $this;
    }

    public function getPostLang()
    {
        return $this->post_lang;
    }

    public function setPostLang(string $post_lang): self
    {
        $this->post_lang = $post_lang;

        return $this;
    }

    public function getPostExcerptXhtml()
    {
        return $this->post_excerpt_xhtml;
    }

    public function setPostExcerptXhtml(string $post_excerpt_xhtml): self
    {
        $this->post_excerpt_xhtml = $post_excerpt_xhtml;

        return $this;
    }

    public function getPostContentXhtml()
    {
        return $this->post_content_xhtml;
    }

    public function setPostContentXhtml(string $post_content_xhtml): self
    {
        $this->post_content_xhtml = $post_content_xhtml;

        return $this;
    }

    public function getPostSelected(): bool
    {
        return $this->post_selected;
    }

    public function setPostSelected(bool $post_selected): self
    {
        $this->post_selected = $post_selected;

        return $this;
    }
}
