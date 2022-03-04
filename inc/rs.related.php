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

class rsRelated extends rsRelatedBase
{
	public static function isEditable($rs) {
		if ($rs->core->auth->check('contentadmin',$rs->core->blog->id)) {
			return true;
		}

		if (!$rs->exists('user_id')) {
			return false;
		}

		if ($rs->core->auth->check('pages',$rs->core->blog->id)
            && $rs->user_id == $rs->core->auth->userID()) {
			return true;
		}

		return false;
	}

	public static function isDeletable($rs) {
		if ($rs->core->auth->check('contentadmin',$rs->core->blog->id)) {
			return true;
		}

		if (!$rs->exists('user_id')) {
			return false;
		}

		if ($rs->core->auth->check('pages',$rs->core->blog->id)
            && $rs->user_id == $rs->core->auth->userID()) {
			return true;
		}

		return false;
	}
}
