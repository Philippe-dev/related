<html>
  <head>
    <title><?php echo __('Related pages'); ?></title>
    <?php echo dcPage::jsPageTabs($default_tab);?>
    <?php echo dcPage::jsLoad('js/jquery/jquery-ui.custom.js');?>
    <?php echo dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js');?>
    <?php echo dcPage::jsLoad('index.php?pf=related/js/_pages.js');?>
    <?php echo dcPage::jsLoad('index.php?pf=related/js/filter-controls.js');?>
    <script type="text/javascript">
      //<![CDATA[
      <?php echo dcPage::jsVar('dotclear.msg.confirm_delete_posts',__("Are you sure you want to delete selected pages?"));?>
      <?php echo dcPage::jsVar('dotclear.msg.show_filters', $show_filters ? 'true':'false');?>
      <?php echo dcPage::jsVar('dotclear.msg.filter_posts_list',$form_filter_title);?>
      <?php echo dcPage::jsVar('dotclear.msg.cancel_the_filter',__('Cancel filters and display options'));?>
      //]]>
    </script>
  </head>
  <body>
    <?php echo dcPage::breadcrumb(array(html::escapeHTML(dcCore::app()->blog->name) => '', __('Related pages') => ''));?>
    <?php echo dcPage::notices();?>

    <div class="multi-part" id="related_settings" title="<?php echo __('Settings');?>">
      <form action="<?php echo $p_url;?>" method="post" enctype="multipart/form-data">
	<div class="fieldset">
	  <h3><?php echo __('Plugin activation'); ?></h3>
	  <p>
	    <?php echo form::checkbox('related_active', 1, $related_active);?>
	    <label class="classic" for="related_active"><?php echo __('Enable Related plugin');?></label>
	  </p>
	</div>

	<?php if ($related_active):?>
        <div class="fieldset">
          <h3><?php echo  __('General options');?></h3>
          <p>
	    <label for="repository" class="classic"><?php echo __('Repository path :').' ';?>
              <?php echo form::field('repository', 80, 255, $related_files_path);?>
            </label>
	  </p>
	</div>
        <div class="fieldset">
          <h3><?php echo __('Advanced options');?></h3>
          <p>
	    <label for="url_prefix" class="classic"><?php echo  __('URL prefix :').' ';?>
	      <?php echo form::field('url_prefix', 80, 255, $related_url_prefix);?>
            </label>
	  </p>
	</div>
	<?php endif;?>
	<?php echo form::hidden(array('p'),'related');?>
	<?php echo dcCore::app()->formNonce();?>
	<input type="submit" name="saveconfig" value="<?php echo __('Save configuration');?>"/>
      </form>
    </div>
    <?php if ($related_active):?>
    <div class="multi-part" id="pages_compose" title="<?php echo __('Manage pages');?>">
      <p class="top-add">
	<a class="button add" href="plugin.php?p=related&amp;do=edit&amp;st=post"><?php echo __('New post as page');?></a>
	&nbsp;-&nbsp;
        <a class="button add" href="plugin.php?p=related&amp;do=edit&amp;st=file"><?php echo __('New included page');?></a>
      </p>

      <p><a id="filter-control" class="form-control" href="<?php echo $p_url;?>#pages_compose"></a></p>
      <form action="<?php echo dcCore::app()->adminurl->get('admin.plugin');?>" method="get" id="filters-form">
	<h3 class="out-of-screen-if-js"><?php echo $form_filter_title;?></h3>
	<div class="table">
	  <div class="cell">
	    <h4><?php echo __('Filters');?></h4>
	    <p>
	      <label for="user_id" class="ib"><?php echo __('Author:');?></label>
	      <?php echo form::combo('user_id',$users_combo,$user_id);?>
	    </p>
	    <p>
	      <label for="status" class="ib"><?php echo __('Status:');?></label>
	      <?php echo form::combo('status',$status_combo,$status);?>
	    </p>
	  </div>

	  <div class="cell filters-sibling-cell">
	    <p>
	      <label for="in_widget" class="ib"><?php echo __('In widget:');?></label>
	      <?php echo form::combo('in_widget', $in_widget_combo, $in_widget);?>
	    </p>
	    <p>
	      <label for="month" class="ib"><?php echo __('Month:');?></label>
	      <?php echo form::combo('month',$dt_m_combo,$month);?>
	    </p>
	    <p>
	      <label for="lang" class="ib"><?php echo __('Lang:');?></label>
	      <?php echo form::combo('lang',$lang_combo,$lang);?>
	    </p>
	  </div>

	  <div class="cell filters-options">
	    <h4><?php echo __('Display options');?></h4>
	    <p>
	      <label for="sortby" class="ib"><?php echo __('Order by:');?></label>
	      <?php echo form::combo('sortby',$sortby_combo,$sortby);?>
	    </p>
	    <p>
	      <label for="order" class="ib"><?php echo __('Sort:');?></label>
	      <?php echo form::combo('order',$order_combo,$order);?>
	    </p>
	    <p>
	      <span class="label ib"><?php echo __('Show');?></span>
	      <label for="nb" class="classic">
		<?php echo form::field('nb',3,3,$nb_per_page).' '.__('pages per page');?>
	      </label>
	    </p>
	  </div>
	</div>

	<p>
	  <input type="submit" value="<?php echo __('Apply filters and display options');?>" />
	  <?php echo form::hidden(array('p'),'related');?>
	  <br class="clear" />
	</p>
      </form>

      <?php
	 $page_list->display($page, $nb_per_page,
      '<form action="'.dcCore::app()->adminurl->get('admin.plugin').'" method="post" id="form-pages">'.
        '%s'.
        '<div class="two-cols">'.
          '<p class="col checkboxes-helpers"></p>'.
          '<p class="col right">'.__('Selected entries action:').
            form::combo('action',$combo_action).
            '<input type="submit" value="'.__('ok').'" /></p>'.
          form::hidden(array('post_type'),'related').
          form::hidden(array('p'),'related').
          dcCore::app()->formNonce().
          '</div>'.
        '</form>'
      );
      ?>
    </div>
    <div class="multi-part" id="pages_order" title="<?php echo __('Arrange public list');?>">
      <?php $public_pages = relatedHelpers::getPublicList($all_pages);?>
      <?php if (!empty($public_pages)):?>
      <form action="plugin.php?p=related" method="post" id="form-public-pages">
        <table class="dragable ">
          <thead>
	    <tr>
              <th><?php echo __('Order');?></th>
              <th class="nowrap"><?php echo __('Visible page in widget');?></th>
	      <th class="nowrap maximal"><?php echo __('Page title');?></th>
            </tr>
	  </thead>
          <tbody id="pages-list" >
            <?php $i = 1;foreach ($public_pages as $page):?>
            <tr class="line<?php ($page['active']? '' : ' offline');?>" id="p_<?php echo $page['id'];?>">
              <td class="handle">
		<?php echo form::field(array('p_order['.$page['id'].']'),2,5,(string) $i, 'position');?>
	      </td>
              <td class="nowrap">
		<?php echo form::checkbox(array('p_visibles[]'),$page['id'],$page['active']);?>
	      </td>
              <td class="nowrap">
		<?php echo $page['title'];?>
	      </td>
            </tr>
	    <?php $i++;endforeach;?>
          </tbody>
	</table>
        <p>
	  <?php echo form::hidden(array('public_order'),'').dcCore::app()->formNonce();?>
          <input type="submit" name="pages_upd" value="<?php echo __('Save');?>" />
	</p>
	<p class="col checkboxes-helpers"></p>
      </form>
      <?php else:?>
      <p><strong><?php echo __('No page');?></strong></p>
      <?php endif;?>
    </div>
    <?php endif;?>
    <?php dcPage::helpBlock('related_pages');?>
  </body>
</html>
