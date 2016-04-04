<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-zotero
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2014 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

define('INTERNAL', 1);
define('MENUITEM', 'content/clouds');
define('SECTION_PLUGINTYPE', 'artefact');
define('SECTION_PLUGINNAME', 'cloud');
define('SECTION_PAGE', 'manage');
define('ZOTERO_SUBPAGE', 'manage2');
require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
require_once(get_config('libroot') . 'view.php');
require_once('lib.php');

if (get_record('usr_account_preference', 'field', 'zoteroexportformat', 'usr', $USER->get('id'))) {
    $exportformat = get_account_preference($USER->get('id'), 'zoteroexportformat');
}
else {
    $exportformat = 'bibtex';
}


$manageform = pieform(array(
    'name'       => 'manageform',
    'renderer'   => 'datatables',
    'plugintype' => 'artefact',
    'pluginname' => 'cloud',
    'configdirs' => array(get_config('libroot') . 'form/', get_config('docroot') . 'artefact/cloud/form/'),
    'elements'   => array(
	    'export' => array(
            'type' => 'select', 
            'title' => get_string('selectexportformat', 'blocktype.cloud/zotero'),
            'value' => null,
            'defaultvalue' => $exportformat,
            'options' => array(
			    'bibtex' => 'BibTeX',
				'bookmarks' => 'Netscape Bookmark(s)',
				'coins' => 'COinS (ContextObjects in Spans)',
				'csljson' => 'Citation Style Language',
				'mods' => 'MODS (Metadata Object Description Schema)',
				'refer' => 'Refer/BibIX',
				'rdf_bibliontology' => 'Bibliographic Ontology RDF',
				'rdf_dc' => 'Unqualified Dublin Core RDF',
				'rdf_zotero' => 'Zotero RDF',
				'ris' => 'RIS (Research Information Systems)',
				'tei' => 'TEI (Text Encoding Initiative)',
				'wikipedia' => 'Wikipedia Citation Templates',
			),
		),
		'submit' => array(
            'type' => 'submit',
            'value' => get_string('save'),
            //'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/zotero/manage.php',
		),
        'manage' => array(
            'type'     => 'datatables',
            'title'    => '', //get_string('selectfiles','blocktype.cloud/dropbox'),
            'service'  => 'zotero',
            'block'    => 0,
            'fullpath' => null,
            'options'  => array(
                'manageButtons'  => true,
                'showFolders'    => true,
                'showFiles'      => true,
                'selectFolders'  => false,
                'selectFiles'    => false,
                'selectMultiple' => false
            ),
        ),
    ),
));

function manageform_submit(Pieform $form, $values) {
    global $USER;
    set_account_preference($USER->get('id'), 'zoteroexportformat', $values['export']);
	redirect('/artefact/cloud/blocktype/zotero/manage.php');
}


$smarty = smarty(array(get_config('wwwroot').'artefact/cloud/blocktype/zotero/script.js'));
$smarty->assign('SERVICE', 'zotero');
$smarty->assign('manageform', $manageform);
$smarty->display('blocktype:zotero:manage.tpl');

?>