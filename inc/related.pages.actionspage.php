<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of Related, a plugin for DotClear2.
#
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

class relatedPagesActionsPage extends dcPostsActionsPage
{
    public function __construct($core, $uri, $redirect_args=array()) {
		parent::__construct($core, $uri, $redirect_args);
		$this->redirect_fields = array();
		$this->caller_title = __('Related pages');
	}

    public function beginPage($breadcrumb='', $head='') {
		echo
            '<html><head><title>'.__('Related pages').'</title>'.
			dcPage::jsLoad('js/_posts_actions.js').
			$head.
			'</script></head><body>'.
			$breadcrumb;
		echo '<p><a class="back" href="'.$this->getRedirection(true).'">'.__('Back to pages list').'</a></p>';
	}

	public function endPage() {
		echo '</body></html>';
	}

    public function loadDefaults() {
        dcDefaultPostActions::adminPostsActionsPage($this->core, $this);
	}

    public function process() {
		$this->from['post_type'] = 'related';

		return parent::process();
	}
}
