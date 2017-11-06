<?php if(!defined('IN_GS')){ die('you cannot load this page directly.'); }
/**
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * NOTE: DO NOT DELETE OR CHANGE VARIABLES IN THIS FILE!
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * If you want to change your ImForms templates you should create your own
 * 'custom.admin.php' file in the same directory. To do so, simply copy the admin.php
 * file complete, or only the variable that you want to change or extend to the
 * 'custom.admin.php' file. SimpleCatalog will load it at runtime and read it's
 * content, the entries in this admin.php file will be overwritten by your
 * custom.admin.php changes.
 */
$this->listViewHeader =
<<<EOD
	<div id="manager-header">
		<h3 class="floated">[[plugin_name]]</h3>
	</div>
	<div id="delay"><div id="clamp"><span id="loader"></span></div></div>
	[[messages]]
EOD;

/**
 * Header template
 */
$this->formViewHeader =
<<<EOD
	<div id="manager-header">
		<h3 class="floated">[[plugin_name]]</h3>
		<div class="edit-nav clearfix">
			[[overview_link]]
			<a href="[[link_1]]" class="btn checkChangedbutton">[[prev_save_form_link]]</a>
		</div>
	</div>
	<div id="delay"><div id="clamp"><span id="loader"></span></div></div>
	[[messages]]
EOD;


/**
 * A form list wrapper
 */
$this->formListWrapper =
<<<EOD
	<div class="highlight">
		<form method="post" id="itemList" 
			action="load.php?id=[[plugin_id]]&section=form&action=edit">
			<table id="im-itemlist-table" class="highlight ui-sortable">
				<thead>
					<tr>
						<th>[[form_name_label]]</th>
						<th>[[form_created_label]]</th>
						<th>[[form_modified_label]]</th>
						<th>[[form_active_label]]</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody id="im-itemlist-body">
					[[item_rows]]
				</tbody>
			</table>
			<div class="interact-compresser">
				<button type="submit" class="btn btn-primary">[[create_form_button_label]]</button>
			</div>
		</form>
	</div>
EOD;


$this->msgsWrapper =
<<<EOD
	<div class="msgs-wrapper">[[msgs_list]]</div>
EOD;


/**
 * A form list row
 */
$this->formRow =
<<<EOD
	<tr class="sortable">
		<td>
			<a href="load.php?id=[[plugin_id]]&section=form&action=edit&edit=[[item_id]]" title="Edit form: [[item_title]]">[[item_title]]</a>
		</td>
		<td>
			[[created]]
		</td>
		<td>
			[[modified]]
		</td>
		<td>
			<a href="load.php?id=[[plugin_id]]&activate=[[item_id]]" 
				class="switch_active" title="activate/deactivate item"><i class="fa [[active]]" aria-hidden="true"></i></a>
		</td>
		<td class="im-del">
			<a onclick="return confirm('Are you sure you want to delete this form?');" 
				href="load.php?id=[[plugin_id]]&delete=[[item_id]]" 
					title="Delete form"><i class="fa fa-times" aria-hidden="true"></i></a>
		</td>
	</tr>
EOD;

/**
 * A Form editor wrapper
 */
$this->formEdotorWrapper =
<<<EOD
	<div class="edit-forms-panel">
		<form id="edit-form" name="edit-form" action="[[form_editor_action]]" method="post">
			<div class="field-group-compresser first">
				<fieldset>
					<legend>[[form_attributes_label]]</legend>
					<div class="form-group">
						<div>
							<ul class="form-props-infowrapper">
								[[formprops]]
							</ul>
						</div>
					</div>
				</fieldset>
			</div>
			<div class="field-group-compresser">
				<fieldset>
					<legend>[[form_fields_label]]</legend>
					<div class="form-group">
						<label for="asmSelect0">[[select_fields_label]]</label>
						<select id="asmSelect0" class="form-control" name="fields">
							<option>&nbsp;</option>
							[[asm_options]]
						</select>
						<div class="asmElements">
							<p class="field-info">[[order_fields_info]]</p>
							<ol class="sortable[[ui_sortable]]">
								[[selected_elements]]
							</ol>
						</div>
					</div>
				</fieldset>
				<input type="hidden" id="elements" name="elements" value="[[elements_value]]">
				<input type="hidden" name="action" value="saveform">
			</div>
			<div class="interact-compresser">
				<a href="load.php?id=[[plugin_id]]" class="btn checkChangedbutton"><strong>[[prev_save_form_link]]</strong></a>
				<button type="submit" id="submitform" class="btn btn-primary">[[save_form_button]]</button>
			</div>
		</form>
	</div>
	<script>
	$(document).ready(function() {
		$('.sortable').nestedSortable({
			handle: 'div',
			items: 'li',
			toleranceElement: '> div',
			maxLevels: 4,
			relocate: function() {
				$('.fieldLink').addClass('fieldLinkDisabled').attr('href', '');
				$(this).closest('form').data('changed', true);
			}
		});
		
		$('.sortable').on('click', '.fieldLinkDisabled', function(e) {
			e.preventDefault();
			 alert("[[save_form_prompt]]");
			 return false;
		});
		
		$('#submitform').click(function(e) {
			e.preventDefault();
			serialized = $('.sortable').nestedSortable('serialize');
			$('#elements').val(serialized);
			$('#edit-form').submit();
		});
		
		$('.sortable').on('click', '.asmListItemRemove', function(e) {
			e.preventDefault();
			var id = $(this).attr('rel');
			$('#'+id).remove();
		});
		
		// exit confirmation
		$('.checkChangedbutton').click(function(e) {
  			if($("#edit-form").closest('form').data('changed')) {
     			if(true !== confirm("[[form_confirm_exit]]")) {
     				e.preventDefault();
			 		return false;
			 	}
  			}
		});
		
		var count = [[count]];
		$('#asmSelect0').on('change', function(e) {
			e.preventDefault();
			var current = $(this).find('option:selected');
			var rel = current.attr('rel');
			var val = $(this).val();
			var itemid = current.text();
			if(typeof rel == 'undefined') return;
			if(rel.length > 1) {
				$('.sortable').append('<li id="'+itemid+'_'+count+'" class="'+rel+'"><div>'+current.text()+
					'<span class="asmListItemStatus"><a href="#" class="fieldLink fieldLinkDisabled">[[edit_link_text]]</a> | <a href="#" rel="'+itemid+'_'+count+'"' +
					' class="asmListItemRemove"><i class="fa fa-trash"></i></a></span></div></li>');
			} else {
				$('.sortable').append('<li id="'+itemid+'_'+count+'" ><div>'+current.text()+
					'<span class="asmListItemStatus"><a href="#" class="fieldLink fieldLinkDisabled">[[edit_link_text]]</a> | <a href="#" rel="'+itemid+'_'+count+'"' +
					' class="asmListItemRemove"><i class="fa fa-trash"></i></a></span></div></li>');
			}
			$(this).find('option:nth-child(1)').prop('selected', 'selected');
			count++;
			$('.fieldLink').addClass('fieldLinkDisabled').attr('href', '');
		});
		// triggered on form element change
		$("form :input").change(function() {
  			$(this).closest('form').data('changed', true);
  			$('.fieldLink').addClass('fieldLinkDisabled').attr('href', '');
		});
	});
	</script>
EOD;

/**
 * Property input row
 */
$this->formPropertyRow =
<<<EOD
	<li><label for="[[id]]" class="label-col">[[label]]</label><input type="[[type]]" id="[[id]]" name="[[name]]" class="inline-form-control" value="[[value]]"></li>
EOD;

/**
 * Property option row
 */
$this->formPropertySelectRow =
<<<EOD
	<li><label for="[[id]]" class="label-col">[[label]]</label><select id="[[id]]" name="[[name]]" class="inline-form-control">[[options]]</select></li>
EOD;

/**
 * Property option row
 */
$this->formPropertyAreaRow =
<<<EOD
	<li class="area-wrapper"><label for="[[id]]" class="label-col area-label">[[label]]</label><textarea id="[[id]]" name="[[name]]" class="inline-form-control">[[value]]</textarea></li>
EOD;

/**
 * Property option
 */
$this->formPropertyOption =
<<<EOD
	<option value="[[value]]"[[selected]]>[[label]]</option>
EOD;

$this->formPropertyCheckboxRow =
<<<EOD
	<li><label for="[[id]]" class="label-col">[[label]]</label><input id="[[id]]" type="[[type]]" name="[[name]]" value="[[value]]"[[checked]]></li>
EOD;


/**
 * ASM Option
 */
$this->formFieldAsmOption =
<<<EOD
	<option rel="[[rel]]" value="[[value]]">[[label]]</option>
EOD;


/**
 * A Field editor wrapper
 */
$this->fieldEdotorWrapper =
<<<EOD
	<div class="edit-forms-panel">
		<form id="edit-form" name="edit-form" action="[[field_editor_action]]" method="post">
			<div class="field-group-compresser first">
				<fieldset>
					<legend>[[field_type]] [[attributes_label]]</legend>
					<div class="form-group">
						<div>
							<ul class="form-props-infowrapper">
								[[formprops]]
							</ul>
						</div>
					</div>
				</fieldset>
			</div>
			<div class="interact-compresser">
				<input type="hidden" name="action" value="savefield">
				<a href="[[prev_link]]" class="btn checkChangedbutton"><strong>[[prev_save_form_link]]</strong></a>
				<button type="submit" id="submitform" class="btn btn-primary">[[save_field_button]]</button>
			</div>
		</form>
	</div>
	<script>
	$(document).ready(function() {
		// exit confirmation
		$('.checkChangedbutton').click(function(e) {
  			if($("#edit-form").closest('form').data('changed')) {
     			if(true !== confirm("[[form_confirm_exit]]")) {
     				e.preventDefault();
			 		return false;
			 	}
  			}
		});
		
		// triggered on form element change
		$("form :input").change(function() {
  			$(this).closest('form').data('changed', true);
		});
	});
	</script>
EOD;

$this->link =
<<<EOD
	<a href="[[link]]" class="[[class]]">[[link_text]]</a>
EOD;
