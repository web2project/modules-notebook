<?php /* $Id: index_table.php 374 2012-06-26 07:35:45Z caseydk $ $URL: svn+ssh://caseydk@svn.code.sf.net/p/web2project-mod/code/notebook/trunk/index_table.php $ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}
global $AppUI, $deny1, $canRead, $canEdit;
global $notebook, $showCompany, $m, $tab;
global $company, $noteCategoryTabs, $notebookFilter, $notebookPageUrl;

/*
 * $notebookFilter is set by whoever includes this list: the Notebook index
 *   (company, project, status and search, kept in the session) or a Notes
 *   tab on another module's page (its company, project, task or creator).
 */
$filter = is_array($notebookFilter) ? $notebookFilter : array();
$company_id = (int) ($filter['company'] ?? 0);
$project_id = (int) ($filter['project'] ?? 0);
$task_id = (int) ($filter['task'] ?? 0);
$creator_id = (int) ($filter['creator'] ?? 0);
$note_status = (int) ($filter['status'] ?? -1);
$search = (string) ($filter['search'] ?? '');

$page = max(1, (int) w2PgetParam($_GET, 'page', 1));

$project = new CProject();
$task = new CTask();

$notebook = new CNotebook();
$where = [];

if ($company_id) {
	// filed on the company, or on one of its projects
	$where[] = "(note_company = $company_id OR note_project IN (SELECT project_id FROM projects WHERE project_company = $company_id))";
}
if ($project_id) {
	$where[] = "note_project = $project_id";
}
if ($task_id) {
	$where[] = "note_task = $task_id";
}
// On the Notebook index each tab is a category (set up in index.php); the
//   Notes tabs on other modules' pages show every category.
if (is_array($noteCategoryTabs) && isset($this) && $this instanceof w2p_Theme_TabBox) {
	$_categories = array_keys($noteCategoryTabs);
	$_category = $_categories[(int) $this->currentTabId] ?? -1;
	if ($_category >= 0) {
		$where[] = 'note_category = ' . (int) $_category;
	}
}
if ($creator_id) {
	$where[] = "note_creator = $creator_id";
}
if ($note_status >= 0) {
	$where[] = "note_status = $note_status";
}
if ('' !== $search) {
	// The term is quoted as one literal; % and _ in it match themselves.
	$q = new w2p_Database_Query();
	$like = $q->quote('%' . addcslashes($search, '%_\\') . '%');
	$where[] = "(note_name LIKE $like OR note_body LIKE $like)";
}

$filter = implode(" AND ", $where);
$items = $notebook->loadAll(null, $filter);

$module = new w2p_System_Module();
$fields = $module->loadSettings('notebook', 'index_list');

if (0 == count($fields)) {
    $fieldList = array('note_name', 'note_category', 'note_status', 'note_project', 'note_task', 'note_creator', 'note_created');
    $fieldNames = array('Note Title', 'Category', 'Status', 'Project', 'Task', 'Creator', 'Date');

	$module->storeSettings('notebook', 'index_list', $fieldList, $fieldNames);
    $fields = array_combine($fieldList, $fieldNames);
}

$xpg_pagesize = w2PgetConfig('page_size', 50);
$xpg_min = $xpg_pagesize * ($page - 1); // This is where we start our record set from
// counts total recs from selection
$xpg_totalrecs = count($items);
$items = array_slice($items, $xpg_min, $xpg_pagesize);

$pageNav = buildPaginationNav($AppUI, $notebookPageUrl ? $notebookPageUrl : $m, $tab, $xpg_totalrecs, $xpg_pagesize, $page);
echo $pageNav;

$note_category = w2PgetSysVal('NoteCategory');
$note_status = w2PgetSysVal('NoteStatus');
$customLookups = array('note_category' => $note_category, 'note_status' => $note_status);

$listTable = new w2p_Output_ListTable($AppUI);
echo $listTable->startTable('notebook');
echo $listTable->buildHeader($fields);
/*
 * Begin: I hate that we have to capture the result and do a string replace but this doesn't follow the naming convention
*/
$_output = $listTable->buildRows($items, $customLookups);
echo str_replace('m=notes', 'm=notebook', $_output);
/*
 * End
 */
echo $listTable->endTable();
echo $pageNav;