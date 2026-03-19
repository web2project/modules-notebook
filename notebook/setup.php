<?php /* $Id: setup.php 183 2011-01-03 16:50:41Z caseydk $ $URL: svn+ssh://caseydk@svn.code.sf.net/p/web2project-mod/code/notebook/trunk/setup.php $ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

/**
 *  Name: Notebook
 *  Directory: notebook
 *  Version 4.0.0
 *  Type: user
 *  UI Name: Notebook
 *  UI Icon: ?
 */

$config = array();
$config['mod_name'] = 'Notebook'; // name the module
$config['mod_version'] = '4.0.0'; // add a version number
$config['mod_directory'] = 'notebook'; // tell web2Project where to find this module
$config['mod_setup_class'] = 'CSetupNotebook'; // the name of the PHP setup class (used below)
$config['mod_type'] = 'user'; // 'core' for modules distributed with w2P by standard, 'user' for additional modules from dotmods
$config['mod_ui_name'] = 'Notebook'; // the name that is shown in the main menu of the User Interface
$config['mod_ui_icon'] = 'notebook.png'; // name of a related icon
$config['mod_description'] = 'User notes in a easy way'; // some description of the module
$config['mod_config'] = false; // show 'configure' notebook in viewmods
$config['mod_main_class'] = 'CNotebook'; // the name of the PHP class used by the module
$config['permissions_item_table'] = 'notes';
$config['permissions_item_field'] = 'note_id';
$config['permissions_item_label'] = 'note_name';

if ($a == 'setup') {
	echo w2PshowModuleConfig($config);
}

class CSetupNotebook extends w2p_System_Setup
{
	public function remove()
    {
        $q = $this->_getQuery();
		$q->dropTable('notes');
		$q->exec();

		$q->clear();
		$q->setDelete('sysvals');
		$q->addWhere("sysval_title = 'NoteCategory'");
		$q->exec();

		$q->clear();
		$q->setDelete('sysvals');
		$q->addWhere("sysval_title = 'NoteStatus'");
		$q->exec();

        return parent::remove();
	}

	public function install()
    {
        $q = $this->_getQuery();
		$q->createTable('notes');
		$q->createDefinition('(
            `note_id` int(10) unsigned NOT NULL auto_increment,
            `note_parent` int(10) unsigned NOT NULL default 0,
            `note_company` int(10) unsigned NOT NULL default 0,
            `note_department` int(10) unsigned NOT NULL default 0,
            `note_project` int(10) unsigned NOT NULL default 0,
            `note_task` int(10) unsigned NOT NULL default 0,
            `note_file` int(10) unsigned NOT NULL default 0,
            `note_module` int(10) unsigned NOT NULL default 0,
            `note_module_name` varchar(64) NOT NULL default \'\',
            `note_record_id` int(10) unsigned NOT NULL default 0,
            `note_category` int(3) unsigned NOT NULL default 0,
            `note_status` int(3) unsigned NOT NULL default 0,
            `note_title` varchar(255) NOT NULL default \'\',
            `note_body` text NOT NULL,
            `note_doc_url` varchar(255) NOT NULL default \'\',
            `note_private` int(1) unsigned NOT NULL default 0,
            `note_creator` int(10) unsigned NOT NULL default 0,
            `note_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `note_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
            `note_modified_by` int(10) unsigned NOT NULL default 0,
            PRIMARY KEY  (`note_id`),
            KEY idx_note_company ( note_company ) ,
            KEY idx_note_project ( note_project ) ,
            KEY idx_note_task ( note_task ) ,
            KEY idx_note_user ( note_creator ) ,
            KEY idx_note_parent ( note_parent )
            ) ENGINE = MYISAM ');

        if (!$q->exec()) {
            return false;
        }

        $i = 0;
        $noteCategories = ['Unknown', 'Idea', 'Workflow', 'Document'];
        foreach ($noteCategories as $category) {
            $q = $this->_getQuery();
            $q->addTable('sysvals');
            $q->addInsert('sysval_key_id', 1);
            $q->addInsert('sysval_title', 'NoteCategory');
            $q->addInsert('sysval_value', $category);
            $q->addInsert('sysval_value_id', $i);
            $q->exec();
            $i++;
        }

        $i = 0;
        $noteStatus = ['Unknown', 'Reference', 'Read', 'Review', 'Do', 'Important', 'Requirement'];
        foreach ($noteStatus as $status) {
            $q = $this->_getQuery();
            $q->addTable('sysvals');
            $q->addInsert('sysval_key_id', 1);
            $q->addInsert('sysval_title', 'NoteStatus');
            $q->addInsert('sysval_value', $status);
            $q->addInsert('sysval_value_id', $i);
            $q->exec();
            $i++;
        }

        $this->addColumns();

        return parent::install();
	}

    public function upgrade($old_version) {
        switch ($old_version) {
            case '3.0.0':
                $this->addColumns();
            default:
                //do nothing
        }
        return true;
    }

    private function addColumns()
    {
        $q = $this->_getQuery();
        $q->alterTable('notes');
        $q->addField('note_name', 'varchar(255)');
        $q->exec();

        $q->clear();
        $q->addTable('notes');
        $q->addUpdate('note_name', 'note_title', false, true);
        $q->exec();

        $module = new w2p_System_Module();
        $fieldList = array('note_name', 'note_category', 'note_status', 'note_project', 'note_task',
            'note_creator', 'note_created');
        $fieldNames = array('Note Title', 'Category', 'Status', 'Project', 'Task', 'Creator', 'Date');
        $module->storeSettings('notebook', 'index_list', $fieldList, $fieldNames);

        return true;
    }
}