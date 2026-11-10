<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

/**
 *  Name: Notebook
 *  Directory: notebook
 *  Version 5.0.0
 *  Type: user
 *  UI Name: Notebook
 *  UI Icon: ?
 */

$config = array();
$config['mod_name'] = 'Notebook'; // name the module
$config['mod_version'] = '5.0.0'; // add a version number
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
$config['requirements'] = array(
    array('require' => 'php',         'comparator' => '>=', 'version' => '8.3'),
    array('require' => 'web2project', 'comparator' => '>=', 'version' => '5'),
);

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
        if (!$this->_meetsRequirements()) {
            return false;
        }

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
            `note_name` varchar(255) NOT NULL default \'\',
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
            ) ENGINE = MYISAM DEFAULT CHARSET=utf8 ');

        if (!$q->exec()) {
            $this->_errors['install'] = 'Notebook: the notes table could not be created. ' .
                (new w2p_Database_Connection($GLOBALS['db']))->error();
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

        $this->storeListSettings();

        return parent::install();
	}

    public function upgrade($old_version)
    {
        if (!$this->_meetsRequirements()) {
            return false;
        }

        // NOTE: the cases fall through so every later step runs too.
        switch ($old_version) {
            case '3.0.0':
                $this->addNameColumn();
                $this->storeListSettings();
            case '4.0.0':
                $this->convertBodies();
                $this->fillCreators();
                $this->dropTitleColumn();
            default:
                //do nothing
        }
        return true;
    }

    /**
     * 5.0 drops the TinyMCE editor: note bodies are plain text, shown the same
     *   way as core descriptions. Convert the HTML that TinyMCE stored.
     */
    private function convertBodies()
    {
        $q = $this->_getQuery();
        $q->addTable('notes');
        $q->addQuery('note_id, note_body');
        $notes = $q->loadList();

        foreach ($notes as $note) {
            $text = CNotebook::htmlToText($note['note_body']);
            if ($text === $note['note_body']) {
                continue;
            }
            $q = $this->_getQuery();
            $q->addTable('notes');
            $q->addUpdate('note_body', $text);
            $q->addWhere('note_id = ' . (int) $note['note_id']);
            $q->exec();
        }

        return true;
    }

    /**
     * Before 5.0 the creator was never saved. Fill it in from the history
     *   entry written when the note was created, where there is one.
     */
    private function fillCreators()
    {
        $q = $this->_getQuery();
        $q->addTable('history');
        $q->addQuery('history_item, history_user');
        $q->addWhere("history_table = 'notes'");
        $q->addWhere("history_action = 'Create'");
        // the newest entry wins if a note id was used before (a reinstall)
        $q->addOrder('history_id ASC');
        $creators = $q->loadHashList();

        foreach ($creators as $note_id => $user_id) {
            $q = $this->_getQuery();
            $q->addTable('notes');
            $q->addUpdate('note_creator', (int) $user_id);
            $q->addWhere('note_id = ' . (int) $note_id);
            $q->addWhere('note_creator = 0');
            $q->exec();
        }

        return true;
    }

    /**
     * 4.0 renamed note_title to note_name (so the core list and view helpers
     *   recognise it): add the column and copy the titles across.
     */
    private function addNameColumn()
    {
        $q = $this->_getQuery();
        $q->alterTable('notes');
        $q->createDefinition("note_name varchar(255) NOT NULL default ''");
        $q->exec();

        $q = $this->_getQuery();
        $q->addTable('notes');
        $q->addUpdate('note_name', 'note_title', false, true);
        $q->exec();

        return true;
    }

    /**
     * Nothing has written note_title since 4.0, so it only holds stale titles.
     *   Every 3.x and 4.x table has it; a 5.0 install never runs this.
     */
    private function dropTitleColumn()
    {
        $q = $this->_getQuery();
        $q->alterTable('notes');
        $q->createDefinition(array(array('action' => 'DROP', 'type' => '', 'spec' => 'note_title')));
        $q->exec();

        return true;
    }

    private function storeListSettings()
    {
        $q = $this->_getQuery();
        $q->setDelete('module_config');
        $q->addWhere("module_name = 'notebook'");
        $q->addWhere("module_config_name = 'index_list'");
        $q->exec();

        $module = new w2p_System_Module();
        $fieldList = array('note_name', 'note_category', 'note_status', 'note_project', 'note_task',
            'note_creator', 'note_created');
        $fieldNames = array('Note Title', 'Category', 'Status', 'Project', 'Task', 'Creator', 'Date');
        $module->storeSettings('notebook', 'index_list', $fieldList, $fieldNames);

        return true;
    }
}