<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-zotero
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2016 Gregor Anzelj, info@povsod.com
 *
 */

define('INTERNAL', 1);
define('PUBLIC', 1);

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
safe_require('artefact', 'cloud');
safe_require('blocktype', 'cloud/zotero');

$id     = param_variable('id', 0); // Possible values: numerical (= folder id), 0 (= root folder), parent (= get parent folder id from path)
$type   = param_alpha('type', 'collection'); // Possible values: item (file), collection (folder)
$format = param_variable('format', 'bibtex');


$prefix = str_replace(' ', '_', $id);
if ($type == 'tag') {
    $tag = $id;
    $id = 0;
}
else {
    $tag = null;
}


$citation = array(
    'bibtex'            => array('ext' => 'bib',  'type' => 'application/x-bibtex'),
    'bookmarks'         => array('ext' => 'html', 'type' => 'text/html; charset=UTF-8'),
    'coins'             => array('ext' => 'cos',  'type' => 'text/html; charset=UTF-8'),
    'csljson'           => array('ext' => 'csl',  'type' => 'application/vnd.citationstyles.csl+json'),
    'mods'              => array('ext' => 'mods', 'type' => 'application/mods+xml'),
    'refer'             => array('ext' => 'ris',  'type' => 'application/x-research-info-systems'),
    'rdf_bibliontology' => array('ext' => 'rdf',  'type' => 'application/rdf+xml'),
    'rdf_dc'            => array('ext' => 'rdf',  'type' => 'application/rdf+xml'),
    'rdf_zotero'        => array('ext' => 'rdf',  'type' => 'application/rdf+xml'),
    'ris'               => array('ext' => 'ris',  'type' => 'application/x-research-info-systems'),
    'tei'               => array('ext' => 'xml',  'type' => 'text/xml'),
    'wikipedia'         => array('ext' => 'wct',  'type' => 'text/x-wiki'),
);

$filename = $prefix . '_' . $format . '.' . $citation[$format]['ext'];
$content = PluginBlocktypeZotero::export_citation($id, $type, $format, $tag);
    
header('Pragma: no-cache');
header('Content-disposition: attachment; filename="' . str_replace('"', '\"', $filename) . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Type: ' . $citation[$format]['type']);
echo $content;
exit;
