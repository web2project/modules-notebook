<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

$delete = (int) w2PgetParam($_POST, 'del', 0);

$company_id = (int) w2PgetParam($_POST, 'note_company', 0);
$project_id = (int) w2PgetParam($_POST, 'note_project', 0);
$task_id = (int) w2PgetParam($_POST, 'note_task', 0);

$successRedirect = 'm=notebook';
$extras = '';
if ($company_id) {
    $successRedirect = 'm=companies&a=view';
    $extras = '&company_id=' . $company_id;
}
if ($project_id) {
    $successRedirect = 'm=projects&a=view';
    $extras = '&project_id=' . $project_id;
}
if ($task_id) {
    $successRedirect = 'm=tasks&a=view';
    $extras = '&task_id=' . $task_id;
}

$controller = new w2p_Controllers_Base(
    new CNotebook(), $delete, 'Notebook', $successRedirect . $extras, 'm=notebook&a=addedit' . $extras
);

$AppUI = $controller->process($AppUI, $_POST);
$AppUI->redirect($controller->resultPath);