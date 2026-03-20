<?php /* $Id: addedit.php 374 2012-06-26 07:35:45Z caseydk $ $URL: svn+ssh://caseydk@svn.code.sf.net/p/web2project-mod/code/notebook/trunk/addedit.php $ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

$object_id = (int) w2PgetParam($_GET, 'note_id', 0);

$object = new CNotebook();
$object->setId($object_id);

// check permissions for this record
$canAddEdit = $object->canAddEdit();
$canAuthor = $object->canCreate();
$canEdit = $object->canEdit();
if (!$canAddEdit) {
	$AppUI->redirect(ACCESS_DENIED);
}

// load the record data
$obj = $AppUI->restoreObject();
if ($obj) {
    $object = $obj;
    $object_id = $object->getId();
} else {
    $object->load($object_id);
}
if (!$object && $object_id > 0) {
	$AppUI->setMsg('Note');
    $AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
    $AppUI->redirect('m=' . $m);
}

$_creator = new CUser();
$_creator->load($object->note_creator);
$creator = new CContact();
$creator->load($_creator->user_contact);
$_modifier = new CUser();
$_modifier->load($object->note_modified_by);
$modifier = new CContact();
$modifier->load($_modifier->user_contact);

print '<script type="text/javascript" src="' . w2PgetConfig('base_url') . '/lib/tiny_mce/tiny_mce.js"></script>';
print '
<script language="javascript" type="text/javascript">
	tinyMCE.init({
		// General options
		mode : "textareas",
		theme : "advanced",
		plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,wordcount,advlist,autosave",
        theme_advanced_resizing_min_height : 240,

		// Theme options
		theme_advanced_buttons1 : "fullscreen,print,preview,save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect,|,tablecontrols",
		theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,|,forecolor,backcolor,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,ltr,rtl",
		theme_advanced_buttons3 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak,restoredraft",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,

		// Style formats
		style_formats : [
			{title : "Bold text", inline : "b"},
			{title : "Red text", inline : "span", styles : {color : "#ff0000"}},
			{title : "Red header", block : "h1", styles : {color : "#ff0000"}},
			{title : "Example 1", inline : "span", classes : "example1"},
			{title : "Example 2", inline : "span", classes : "example2"},
			{title : "Table styles"},
			{title : "Table row 1", selector : "tr", classes : "tablerow1"}
		],

	});
</script>
';

$note_task = (int) w2PgetParam($_GET, 'task_id', 0);
$note_parent = (int) w2PgetParam($_GET, 'note_parent', 0);
$note_project = (int) w2PgetParam($_GET, 'project_id', 0);
$note_company = (int) w2PgetParam($_GET, 'company_id', 0);

$q = new w2p_Database_Query();
$q->addQuery('notes.*');
$q->addQuery('u.user_username');
$q->addQuery('c.contact_first_name, c.contact_last_name');
$q->addQuery('cm.contact_first_name AS modified_first_name, cm.contact_last_name AS modified_last_name');
$q->addQuery('project_id');
$q->addQuery('task_id, task_name');
$q->addQuery('company_id, company_name');
$q->addTable('notes');
$q->leftJoin('users', 'u', 'note_creator = u.user_id');
$q->leftJoin('contacts', 'c', 'u.user_contact = c.contact_id');
$q->leftJoin('users', 'um', 'note_modified_by = um.user_id');
$q->leftJoin('contacts', 'cm', 'um.user_contact = cm.contact_id');
$q->leftJoin('companies', 'co', 'company_id = note_company');
$q->leftJoin('projects', 'p', 'project_id = note_project');
$q->leftJoin('tasks', 't', 'task_id = note_task');
$q->addWhere('note_id = ' . (int)$object_id);

$df = $AppUI->getPref('SHDATEFORMAT');
$tf = $AppUI->getPref('TIMEFORMAT');

$note_created = new w2p_Utilities_Date($object->note_created);
$note_modified = new w2p_Utilities_Date($object->note_modified);

// setup the title block
$ttl = $object_id ? 'Edit Note' : 'Add Note';
$titleBlock = new w2p_Theme_TitleBlock($ttl, 'notebook.png', $m, $m . '.' . $a);
$titleBlock->addCrumb('?m=' . $m, 'notes list');
$titleBlock->show();

if ($object->note_project) {
	$note_project = $object->note_project;
}
if ($object->note_task) {
	$note_task = $object->note_task;
	$task_name = $object->task_name;
} elseif ($note_task) {
	$q->clear();
	$q->addQuery('task_name');
	$q->addTable('tasks');
	$q->addWhere('task_id = ' . (int)$note_task);
	$task_name = $q->loadResult();
} else {
	$task_name = '';
}

if (intval(w2PgetParam($_GET, 'company_id', 0))) {
	$extra = array('where' => 'project_active = 1 AND project_company = ' . $note_company);
} else {
	$extra = array('where' => 'project_active = 1');
}
$project = new CProject();
$projects = $project->getAllowedRecords($AppUI->user_id, 'projects.project_id,project_name', 'project_name', null, $extra, 'projects');
$projects = arrayMerge(array('0' => $AppUI->_('All', UI_OUTPUT_JS)), $projects);

?>
<script language="javascript" type="text/javascript">
function submitIt() {
	var f = document.editFrm;
	f.submit();
}

function popTask() {
    var f = document.editFrm;
    if (f.note_project.selectedIndex == 0) {
        alert( "<?php echo $AppUI->_('Please select a project first!', UI_OUTPUT_JS); ?>" );
    } else {
        window.open('./index.php?m=public&a=selector&dialog=1&callback=setTask&table=tasks&task_project='
            + f.note_project.options[f.note_project.selectedIndex].value, 'task','left=50,top=50,height=250,width=400,resizable')
    }
}

// Callback function for the generic selector
function setTask( key, val ) {
    var f = document.editFrm;
    if (val != '') {
        f.note_task.value = key;
        f.task_name.value = val;
    } else {
        f.note_task.value = '0';
        f.task_name.value = '';
    }
}
</script>

<form name="editFrm" action="?m=notebook" method="post" class="form-horizontal addedit notebook">
	<input type="hidden" name="dosql" value="do_note_aed" />
	<input type="hidden" name="note_id" value="<?php echo $object_id; ?>" />
	<input type="hidden" name="note_company" value="<?php echo $note_company; ?>" />
	<div class="std addedit notebook">
		<div class="column left">
			<p>
				<label><?php echo $AppUI->_('Note Title'); ?>:</label>
				<input type="text" class="text name" name="note_name" value="<?php echo $object->note_name; ?>" />
			</p>
			<p>
				<label><?php echo $AppUI->_('Private'); ?>:</label>
				<input type="checkbox" value="1" name="note_private" <?php echo ($object->note_private ? 'checked="checked"' : ''); ?> />
			</p>
			<?php if ($object_id) { ?>
				<p>
					<label><?php echo $AppUI->_('Created By'); ?>:</label>
					<?php echo $creator->contact_display_name; ?>, <?php echo $note_created->format($df . ' ' . $tf); ?>
				</p>
				<p>
					<label><?php echo $AppUI->_('Modified By'); ?>:</label>
					<?php echo $modifier->contact_display_name; ?>, <?php echo $note_modified->format($df . ' ' . $tf); ?>
				</p>
			<?php } ?>
			<p>
				<label><?php echo $AppUI->_('Category'); ?>:</label>
				<?php echo arraySelect(w2PgetSysVal('NoteCategory'), 'note_category', 'class="text"', $object->note_category, true); ?>
			</p>
			<p>
				<label><?php echo $AppUI->_('Status'); ?>:</label>
				<?php echo arraySelect(w2PgetSysVal('NoteStatus'), 'note_status', 'class="text"', $object->note_status, true); ?>
			</p>
			<?php if ($object->note_company) { ?>
				<p>
					<label><?php echo $AppUI->_('Company'); ?>:</label>
					<?php echo $company_name; ?>
				</p>
			<?php } ?>
			<?php if ($object->note_project) { ?>
				<p>
					<label><?php echo $AppUI->_('Project'); ?>:</label>
					<?php echo arraySelect($projects, 'note_project', 'size="1" class="text" style="width:270px"', $note_project); ?>
				</p>
			<?php } ?>
			<p>
				<label><?php echo $AppUI->_('Task'); ?>:</label>
				<input type="hidden" name="note_task" value="<?php echo $note_task; ?>" />
				<input type="text" class="text" name="task_name" value="<?php echo $task_name; ?>" size="40" disabled="disabled" />
				<input type="button" class="button" value="<?php echo $AppUI->_('select task'); ?>..." onclick="popTask()" />
			</p>
			<p>
				<label><?php echo $AppUI->_('Description'); ?>:</label>
				<textarea class="description" name="note_body"><?php echo $object->note_body; ?></textarea>
			</p>
			<p>
				<label><?php echo $AppUI->_('Note Doc URL'); ?>:</label>
				<input type="field" class="text url" name="note_doc_url" value="<?php echo $object->note_doc_url ?>" />
				<a href="javascript: void(0);" onclick="testURL()">[test]</a>
			</p>
			<p>
				<label></label>
			</p>
			<input type="button" class="cancel button btn btn-danger" value="<?php echo $AppUI->_('back'); ?>" onclick="javascript:history.back(-1)" />
			<input type="button" class="save button btn btn-primary" value="<?php echo $AppUI->_('save'); ?>" onclick="submitIt()" />
		</div>
	</div>
</form>