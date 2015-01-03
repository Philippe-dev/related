<html>
  <head>
    <title><?php echo __('Related pages'); ?></title>
    <?php echo dcPage::jsToolMan();?>
    <?php echo dcPage::jsPageTabs($default_tab);?>
    <?php echo dcPage::jsLoad('index.php?pf=related/js/_pages.js');?>
  </head>
  <body>
    <?php echo dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			__('Related pages') => ''
		)); ?>
    <?php if (!empty($message)):?>
    <p class="message"><?php echo $message;?></p>
    <?php endif;?>

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
	    <label class=" classic"><?php echo __('Repository path :').' ';?>
              <?php echo form::field('repository', 60, 255, $related_repository);?>
            </label>
	  </p>
	</div>
        <div class="fieldset">
          <h3><?php echo __('Advanced options');?></h3>
          <p>
	    <label class=" classic"><?php echo  __('URL prefix :').' ';?>
	      <?php echo form::field('url_prefix', 60, 255, $related_url_prefix);?>
            </label>
	  </p>
	</div>
	<?php endif;?>
	<?php echo form::hidden('p','related');?>
	<?php echo $core->formNonce();?>
	<input type="submit" name="saveconfig" value="<?php echo __('Save configuration');?>"/>
      </form>
    </div>
    <?php if ($related_active):?>
    <div class="multi-part" id="pages_compose" title="<?php echo __('Manage pages');?>">
      <p class="top-add">	
		<a class="button add" href="plugin.php?p=related&amp;do=edit&amp;st=post"><?php echo __('New post as page');?></a>&nbsp;-&nbsp;
        <a class="button add" href="plugin.php?p=related&amp;do=edit&amp;st=file"><?php echo __('New included page');?></a>
      </p>
      <?php
	 $page_list->display($page,$nb_per_page,
      '<form action="posts_actions.php" method="post" id="form-pages">'.
        '%s'.
        '<div class="two-cols">'.
          '<p class="col checkboxes-helpers"></p>'.
          '<p class="col right">'.__('Selected entries action:').
            form::combo('action',$combo_action).
            '<input type="submit" value="'.__('ok').'" /></p>'.
          form::hidden(array('post_type'),'related').
          form::hidden(array('redir'),html::escapeHTML($_SERVER['REQUEST_URI'])).
          $core->formNonce().
          '</div>'.
        '</form>'
      );
      ?>
    </div>
    <div class="multi-part" id="pages_order" title="<?php echo __('Arrange public list');?>">
      <?php $public_pages = relatedHelpers::getPublicList($pages);?>
      <?php if (!empty($public_pages)):?>
      <form action="plugin.php?p=related" method="post" id="form-public-pages">
        <table class="dragable ">
          <thead>
	    <tr>
              <th><?php echo __('Order');?></th>
              <th><?php echo __('Visible');?></th>
	      <th class="nowrap maximal"><?php echo __('Page title');?></th>
            </tr>
	  </thead>
          <tbody id="pages-list" >
            <?php $i = 1;foreach ($public_pages as $page):?>
            <tr class="line<?php ($page['active']? '' : ' offline');?>" id="p_<?php echo $page['id'];?>">
              <td class="handle">
		<?php echo form::field(array('p_order['.$page['id'].']'),2,5,(string) $i);?>
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
	  <?php echo form::hidden('public_order','').$core->formNonce();?>
          <input type="submit" name="pages_upd" value="<?php echo __('Save');?>" />
	</p>
      </form>
      <?php else:?>
      <p><strong><?php echo __('No page');?></strong></p>
      <?php endif;?>
    </div>
    <?php endif;?>
    <?php dcPage::helpBlock('related_pages');?>
  </body>
</html>
