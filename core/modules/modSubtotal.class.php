<?php
/* <Add title and subtotal in propal, order, invoice.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup	titre	titre module
 * 	\brief		titre module descriptor.
 * 	\file		core/modules/modtitre.class.php
 * 	\ingroup	titre
 * 	\brief		Description and activation file for module titre
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module titre
 */
class modSubtotal extends DolibarrModules
{

    /**
     * 	Constructor. Define names, constants, directories, boxes, permissions
     *
     * 	@param	DoliDB		$db	Database handler
     */

    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

		$this->editor_name = 'ATM Consulting';
        // Id for module (must be unique).
        // Use a free id here
        // (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 104777; // 104000 to 104999 for ATM CONSULTING
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'subtotal';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        // It is used to group modules in module setup page

        $this->family = "ATM Consulting - CRM";

        // Module label (no space allowed)
        // used if translation string 'ModuleXXXName' not found
        // (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description
        // used if translation string 'ModuleXXXDesc' not found
        // (where XXX is value of numeric property 'numero' of module)
        $this->description = "Module permettant d'ajouter des titres, sous-totaux et des sous-totaux intermédiaires dans un tableau ou une liste, tout en facilitant le déplacement fluide d'une ligne d'éléments d'un sous-total à un autre.";
        // Possible values for version are: 'development', 'experimental' or version


        $this->version = '3.26.0';


		// Url to the file with your last numberversion of this module
		require_once __DIR__ . '/../../class/techatm.class.php';
		$this->url_last_version = \subtotal\TechATM::getLastModuleVersionUrl($this);

        // Key used in llx_const table to save module status enabled/disabled
        // (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        // Where to store the module in setup page
        // (0=common,1=interface,2=others,3=very specific)
        $this->special = 2;
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png
        // use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png
        // use this->picto='pictovalue@module'
        $this->picto = 'modsubtotal@subtotal'; // mypicto@titre
        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
        // for default path (eg: /titre/core/xxxxx) (0=disable, 1=enable)
        // for specific path of parts (eg: /titre/core/modules/barcode)
        // for specific css file (eg: /titre/css/titre.css.php)
        $this->module_parts = array(
            // Set this to 1 if module has its own trigger directory
            'triggers' => 1,
            // Set this to 1 if module has its own login method directory
            //'login' => 0,
            // Set this to 1 if module has its own substitution function file
            //'substitutions' => 0,
            // Set this to 1 if module has its own menus handler directory
            //'menus' => 0,
            // Set this to 1 if module has its own barcode directory
            //'barcode' => 0,
            // Set this to 1 if module has its own models directory
            'models' => 1,
            // Set this to relative path of css if module has its own css file
            //'css' => '/titre/css/mycss.css.php',
            // Set here all hooks context managed by module
            'hooks' => array(
                'invoicecard'
                ,'invoicesuppliercard'
                ,'propalcard'
                ,'supplier_proposalcard'
                ,'ordercard'
                ,'ordersuppliercard'
                ,'odtgeneration'
                ,'orderstoinvoice'
                ,'orderstoinvoicesupplier'
                ,'admin'
                ,'invoicereccard'
                ,'consumptionthirdparty'
            	,'ordershipmentcard'
            	,'expeditioncard'
				,'deliverycard'
				,'paiementcard'
				,'referencelettersinstacecard'
                ,'shippableorderlist'
				,'propallist'
				,'orderlist'
				,'invoicelist'
				,'supplierorderlist'
				,'supplierinvoicelist'
                ,'cron'
				,'pdfgeneration'
            ),
            // Set here all workflow context managed by module
            //'workflow' => array('order' => array('WORKFLOW_ORDER_AUTOCREATE_INVOICE')),
            'tpl' => 1
        );

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/titre/temp");
        $this->dirs = array();

        // Config pages. Put here list of php pages
        // stored into titre/admin directory, used to setup module.
        $this->config_page_url = array("subtotal_setup.php@subtotal");

        // Dependencies
        // List of modules id that must be enabled if this module is enabled
        $this->depends = array();

		$this->conflictwith=array('modMilestone');
        // List of modules id to disable if this one is disabled
        $this->requiredby = array();
        // Minimum version of PHP required by module
        $this->phpmin = array(7,0);
        // Minimum version of Dolibarr required by module
        $this->need_dolibarr_version = array(16,0);
        $this->langfiles = array("subtotal@subtotal"); // langfiles@titre
        // Constants
        // List of particular constants to add when module is enabled
        // (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example:
        $this->const = array(
            	0=>array(
            		'SUBTOTAL_STYLE_TITRES_SI_LIGNES_CACHEES',
            		'chaine',
            		'I',
            		'Définit le style (B : gras, I : Italique, U : Souligné) des sous titres lorsque le détail des lignes et des ensembles est caché',
            		1
            	)
				,1=>array('SUBTOTAL_ALLOW_ADD_BLOCK', 'chaine', '1', 'Permet l\'ajout de titres et sous-totaux')
				,2=>array('SUBTOTAL_ALLOW_EDIT_BLOCK', 'chaine', '1', 'Permet de modifier titres et sous-totaux')
				,3=>array('SUBTOTAL_ALLOW_REMOVE_BLOCK', 'chaine', '1', 'Permet de supprimer les titres et sous-totaux')
				,4=>array('SUBTOTAL_TITLE_STYLE', 'chaine', 'BU')
				,5=>array('SUBTOTAL_SUBTOTAL_STYLE', 'chaine', 'B')
            //	1=>array(
            //		'MYMODULE_MYNEWCONST2',
            //		'chaine',
            //		'myvalue',
            //		'This is another constant to add',
            //		0
            //	)
        );




        // Array to add new pages in new tabs
        // Example:
        $this->tabs = array(
            //	// To add a new tab identified by code tabname1
            //	'objecttype:+tabname1:Title1:langfile@titre:$user->rights->titre->read:/titre/mynewtab1.php?id=__ID__',
            //	// To add another new tab identified by code tabname2
            //	'objecttype:+tabname2:Title2:langfile@titre:$user->rights->othermodule->read:/titre/mynewtab2.php?id=__ID__',
            //	// To remove an existing tab identified by code tabname
            //	'objecttype:-tabname'
        );
        // where objecttype can be
        // 'thirdparty'			to add a tab in third party view
        // 'intervention'		to add a tab in intervention view
        // 'order_supplier'		to add a tab in supplier order view
        // 'invoice_supplier'	to add a tab in supplier invoice view
        // 'invoice'			to add a tab in customer invoice view
        // 'order'				to add a tab in customer order view
        // 'product'			to add a tab in product view
        // 'stock'				to add a tab in stock view
        // 'propal'				to add a tab in propal view
        // 'member'				to add a tab in fundation member view
        // 'contract'			to add a tab in contract view
        // 'user'				to add a tab in user view
        // 'group'				to add a tab in group view
        // 'contact'			to add a tab in contact view
        // 'categories_x'		to add a tab in category view
        // (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)

		//Compatibility V16
		$dictionnariesTablePrefix = '';
		if (intval(DOL_VERSION)< 16) $dictionnariesTablePrefix =  MAIN_DB_PREFIX;

        // Dictionnaries
        if (!isModEnabled('subtotal')) {
            $conf->subtotal=new stdClass();
            $conf->subtotal->enabled = 0;
        }
        $this->dictionaries = array(
			'langs'=>'subtotal@subtotal',
            'tabname'=>array($dictionnariesTablePrefix.'c_subtotal_free_text'),		// List of tables we want to see into dictonnary editor
            'tablib'=>array($langs->trans('subtotalFreeLineDictionary')),													// Label of tables
            'tabsql'=>array('SELECT f.rowid as rowid, f.label, f.content, f.entity, f.active FROM '.MAIN_DB_PREFIX.'c_subtotal_free_text as f WHERE f.entity='.$conf->entity),	// Request to select fields
            'tabsqlsort'=>array('label ASC'),																					// Sort order
            'tabfield'=>array('label,content'),							// List of fields (result of select to show dictionary)
            'tabfieldvalue'=>array('label,content'),						// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array('label,content,entity'),					// List of fields (list of fields for insert)
            'tabrowid'=>array('rowid'),											// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array(isModEnabled('subtotal'))
		);

        // Boxes
        // Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array(); // Boxes list

        /*
          $this->boxes[$r][1] = "myboxb.php";
          $r++;
         */

        // Permissions
        $this->rights = array(); // Permission array used by this module
        $r = 0;

        // Add here list of permission defined by
        // an id, a label, a boolean and two constant strings.
        // Example:
        //// Permission id (must not be already used)
        //$this->rights[$r][0] = 2000;
        //// Permission label
        //$this->rights[$r][1] = 'Permision label';
        //// Permission by default for new user (0/1)
        //$this->rights[$r][3] = 1;
        //// In php code, permission will be checked by test
        //// if ($user->rights->permkey->level1->level2)
        //$this->rights[$r][4] = 'level1';
        //// In php code, permission will be checked by test
        //// if ($user->rights->permkey->level1->level2)
        //$this->rights[$r][5] = 'level2';
        //$r++;
        // Main menu entries
        $this->menus = array(); // List of menus to add

    }

    /**
     * Function called when module is enabled.
     * The init function add constants, boxes, permissions and menus
     * (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * 	@param		string	$options	Options when enabling module ('', 'noboxes')
     * 	@return		int					1 if OK, 0 if KO
     */
    public function init($options = '')
    {
	  	global $conf, $db;


/*		if(isModEnabled('milestone')) {
			exit("Attention, ce module rentre ne conflit avec le module Jalon/Milestones. Merci de le désactiver auparavant.");
		}
      */
	    $sql = array();

        $result = $this->loadTables();
        dol_include_once('/core/class/extrafields.class.php');

        $extra = new ExtraFields($db); // propaldet, commandedet, facturedet
        $TElementType = array('propaldet', 'commandedet', 'facturedet', 'supplier_proposaldet', 'commande_fournisseurdet', 'facture_fourn_det');
        foreach($TElementType as $element_type) {
            $extra->addExtraField('show_total_ht', 'Afficher le Total HT sur le sous-total', 'int', 0, 10, $element_type, 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
            $extra->addExtraField('show_reduc', 'Afficher la réduction sur le sous-total', 'int', 0, 10, $element_type, 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
            $extra->addExtraField('subtotal_show_qty', 'Afficher la Quantité du Sous-Total', 'int', 0, 10, $element_type, 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
        }

		$extra->addExtraField('hideblock', 'Cacher les lignes contenues dans ce titre', 'int', 4, 2, 'propaldet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
		$extra->addExtraField('hideblock', 'Cacher les lignes contenues dans ce titre', 'int', 4, 2, 'commandedet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
		$extra->addExtraField('hideblock', 'Cacher les lignes contenues dans ce titre', 'int', 4, 2, 'commande_fournisseurdet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
		$extra->addExtraField('hideblock', 'Cacher les lignes contenues dans ce titre', 'int', 4, 2, 'facturedet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
		$extra->addExtraField('hideblock', 'Cacher les lignes contenues dans ce titre', 'int', 4, 2, 'facture_fourn_det', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);

		return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted
     *
     * 	@param		string	$options	Options when enabling module ('', 'noboxes')
     * 	@return		int					1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }

    /**
     * Create tables, keys and data required by module
     * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     * and create data commands must be stored in directory /titre/sql/
     * This function is called by this->init
     *
     * 	@return		int		<=0 if KO, >0 if OK
     */
    private function loadTables()
    {
        return $this->_load_tables('/subtotal/sql/');
    }
}
