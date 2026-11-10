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
}
// a note on a task belongs to the task's project
if ($note_task && !$note_project) {
	$_task = new CTask();
	$_task->load($note_task);
	$note_project = (int) $_task->task_project;
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
// keep the note's own project selectable even if it is no longer active
if ($note_project && !isset($projects[$note_project])) {
	$_project = new CProject();
	if ($_project->load($note_project)) {
		$projects[$note_project] = $_project->project_name;
	}
}
$projects = arrayMerge(array('0' => $AppUI->_('None')), $projects);

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
				<input type="text" class="text name" name="note_name" value="<?php echo htmlspecialchars((string) $object->note_name, ENT_QUOTES); ?>" />
			</p>
			<p>
				<label><?php echo $AppUI->_('Private'); ?>:</label>
				<input type="checkbox" value="1" name="note_private" <?php echo ($object->note_private ? 'checked="checked"' : ''); ?> />
			</p>
			<?php if ($object_id) { ?>
				<p>
					<label><?php echo $AppUI->_('Created By'); ?>:</label>
					<?php echo htmlspecialchars((string) $creator->contact_display_name, ENT_QUOTES); ?>, <?php echo $note_created->format($df . ' ' . $tf); ?>
				</p>
				<p>
					<label><?php echo $AppUI->_('Modified By'); ?>:</label>
					<?php echo htmlspecialchars((string) $modifier->contact_display_name, ENT_QUOTES); ?>, <?php echo $note_modified->format($df . ' ' . $tf); ?>
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
			<p>
				<label><?php echo $AppUI->_('Project'); ?>:</label>
				<?php echo arraySelect($projects, 'note_project', 'size="1" class="text" style="width:270px" onchange="setTask(0, \'\')"', $note_project); ?>
			</p>
			<p>
				<label><?php echo $AppUI->_('Task'); ?>:</label>
				<input type="hidden" name="note_task" value="<?php echo $note_task; ?>" />
				<input type="text" class="text" name="task_name" value="<?php echo htmlspecialchars((string) $task_name, ENT_QUOTES); ?>" size="40" disabled="disabled" />
				<input type="button" class="button" value="<?php echo $AppUI->_('select task'); ?>..." onclick="popTask()" />
			</p>
			<p>
				<label><?php echo $AppUI->_('Description'); ?>:</label>
				<textarea class="description" name="note_body" rows="12"><?php echo htmlspecialchars((string) $object->note_body, ENT_QUOTES); ?></textarea>
			</p>
			<p>
				<label><?php echo $AppUI->_('Note Doc URL'); ?>:</label>
				<input type="field" class="text url" name="note_doc_url" value="<?php echo htmlspecialchars((string) $object->note_doc_url, ENT_QUOTES); ?>" />
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