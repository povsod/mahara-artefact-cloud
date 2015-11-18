<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-dropbox
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2015 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

define('INTERNAL', 1);
define('PUBLIC', 1);

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
require_once(get_config('libroot') . 'view.php');
safe_require('artefact', 'cloud');
safe_require('blocktype', 'cloud/dropbox');

$id = param_variable('id', 0);
$viewid = param_integer('view', null);

$owner = null;
if ($viewid > 0) {
    $view = new View($viewid);
    $owner = $view->get('owner');
    if (!can_view_view($viewid)) {
        throw new AccessDeniedException();
    }
}

$urlpath = str_replace('%2F', '/', rawurlencode($id));

// Redirect to file preview url...
$public_url = PluginBlocktypeDropbox::public_url($urlpath, $owner);
redirect($public_url);

?>
