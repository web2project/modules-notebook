<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

$note_id = (int) w2PgetParam($_GET, 'note_id', 0);

$obj = new CNotebook();

if (!$obj->load($note_id)) {
    $AppUI->redirect(ACCESS_DENIED);
}

print '<script type="text/javascript" src="' . w2PgetConfig('base_url') . '/lib/tiny_mce/tiny_mce.js"></script>';
print '
<script language="javascript" type="text/javascript">
	tinyMCE.init({
		// General options
		mode : "textareas",
		theme : "advanced",
		readonly : true
	});
</script>
<script language="javascript" type="text/javascript">
    function delIt() {
        if (confirm("' . $AppUI->_('doDelete') . ' note?")){
            document.frmDelete.submit();
        }
    }
</script>
';

// setup the title block
$ttl = 'View Note';
$titleBlock = new w2p_Theme_TitleBlock($ttl, 'notebook.png', $m, $m . '.' . $a);
$titleBlock->addCrumb('?m=' . $m, 'notes list');
$titleBlock->addCrumb('?m=' . $m . '&a=addedit&note_id=' . $note_id, 'edit note');
$canDelete = $perms->checkModule($m, 'delete');
if ($canDelete && $note_id > 0) {
	$titleBlock->addCrumbDelete('delete note', $canDelete, $msg);
}
$titleBlock->show();

if ($obj->note_task) {
    $_task = new CTask();
    $_task->load($obj->note_task);
    $obj->note_project = $_task->task_project;
}

if ($obj->note_project) {
    $_project = new CProject();
    $_project->load($obj->note_project);
    $obj->note_company = $_project->project_company;
}

$categories = w2PgetSysVal('NoteCategory');
$status = w2PgetSysVal('NoteStatus');
?>

<form name="frmDelete" action="?m=notebook" method="post" accept-charset="utf-8">
    <input type="hidden" name="dosql" value="do_note_aed" />
    <input type="hidden" name="del" value="1" />
    <input type="hidden" name="note_id" value="<?php echo $note_id; ?>" />
</form>

<?php

$view = new w2p_Output_HTML_ViewHelper($AppUI);

?>

<div class="std view notebook">
    <div class="column left" style="width: 25%">
        <p><?php $view->showLabel('Note Title'); ?>
            <?php $view->showField('note_name', $obj->note_name); ?>
        </p>
        <p><?php $view->showLabel('Company'); ?>
            <?php $view->showField('note_company', $obj->note_company); ?>
        </p>
        <p><?php $view->showLabel('Project'); ?>
            <?php $view->showField('note_project', $obj->note_project); ?>
        </p>
        <p><?php $view->showLabel('Task'); ?>
            <?php $view->showField('note_task', $obj->note_task); ?>
        </p>
        <p><?php $view->showLabel('Created By'); ?>
            <?php $view->showField('note_owner', $obj->note_creator); ?>
        </p>
        <p><?php $view->showLabel('Created At'); ?>
            <?php $view->showField('_datetime', $obj->note_created); ?>
        </p>
        <p><?php $view->showLabel('Modified By'); ?>
            <?php $view->showField('note_owner', $obj->note_modified_by); ?>
        </p>
        <p><?php $view->showLabel('Modified At'); ?>
            <?php $view->showField('_datetime', $obj->note_modified); ?>
        </p>
    </div>
    <div class="column right" style="width: 70%;">
        <p><?php $view->showLabel('Private'); ?>
            <input type="checkbox" disabled="disabled" name="note_private" <?php echo ($obj->note_private ? 'checked="checked"' : ''); ?> />
        </p>
        <p><?php $view->showLabel('Category'); ?>
            <?php $view->showField('note_category', $categories[$obj->note_category]); ?>
        </p>
        <p><?php $view->showLabel('Status'); ?>
            <?php $view->showField('note_status', $status[$obj->note_status]); ?>
        </p>
        <p><?php $view->showLabel('URL'); ?>
            <?php $view->showField('note_doc_url', $obj->note_doc_url); ?>
        </p>
        <p><?php $view->showLabel('Description'); ?>
            <?php $view->showField('_description', $obj->note_body); ?>
        </p>
    </div>
</div>