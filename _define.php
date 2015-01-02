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

if (!defined('DC_RC_PATH')) return;

$this->registerModule(
	/* Name */		"Related Pages",
	/* Description*/	"Serve pages & scripts",
	/* Author */		"Pep, contributors, Nicolas Roudaire",
	/* Version */		'1.2.0',
	/* Permissions */	'contentadmin,pages'
);
