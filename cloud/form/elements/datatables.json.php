<?php
/**
 *
 * @package    pieform
 * @subpackage element
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2015 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

define('INTERNAL', 1);
//define('JSON', 1);

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
safe_require('artefact', 'cloud');

$id       = param_variable('id', 0);
$mode     = param_alphanum('mode', '011011');
$block    = param_alphanum('block', 0);
$service  = param_alphanum('service');


safe_require('blocktype', 'cloud/' . strtolower($service));
$filelist = call_static_method(generate_class_name('blocktype', $service), 'get_folder_content', $id, $mode, $block);

header('Content-type: text/plain');
header('Pragma: no-cache');

echo $filelist;

?>
