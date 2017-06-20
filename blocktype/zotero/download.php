<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-zotero
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2017 Gregor Anzelj, info@povsod.com
 *
 */

define('INTERNAL', 1);
define('PUBLIC', 1);

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
safe_require('artefact', 'cloud');
safe_require('blocktype', 'cloud/zotero');

$id = param_variable('id', 0); // Possible values: numerical (= folder id), 0 (= root folder), parent (= get parent folder id from path)
$type   = param_alpha('type', null); // Possible values: item (file), collection (folder)
$format = 'rdf_zotero';


$form = pieform(array(
    'name'       => 'exportform',
    'jsform' => true,
    'plugintype' => 'artefact',
    'pluginname' => 'cloud',
    //'template'   => 'saveform.php',
    //'templatedir' => pieform_template_dir('saveform.php', 
    'elements'   => array(
        'id' => array(
            'type'  => 'hidden',
            'value' => $id,
        ),
        'type' => array(
            'type'  => 'hidden',
            'value' => $type,
        ),
        'format' => array(
            'type' => 'radio', 
            'title' => get_string('selectexportformat', 'blocktype.cloud/zotero'),
            'value' => null,
            'defaultvalue' => null,
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
            'separator' => '<br />',
            'rules'   => array(
                'required' => true
            )
        ),
        'submit' => array(
            'type' => 'submitcancel',
            'value' => array(get_string('export', 'artefact.cloud'), get_string('cancel')),
            'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/zotero/manage.php',
        )
    ),
));
    
$smarty = smarty();
$smarty->assign('PAGEHEADING', get_string('export', 'artefact.cloud'));
$smarty->assign('form', $form);
$smarty->display('form.tpl');


function exportform_submit(Pieform $form, $values) {
    global $SESSION;
    $id = $values['id'];
    $type = $values['type'];
    $format = $values['format'];
    $SESSION->add_ok_msg(get_string('citationexported', 'blocktype.cloud/zotero'));
    redirect(get_config('wwwroot') . 'artefact/cloud/blocktype/zotero/export.php?id='.$id.'&type='.$type.'&format='.$format);
}
