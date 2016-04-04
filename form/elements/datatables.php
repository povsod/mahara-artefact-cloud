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

defined('INTERNAL') || die();

/**
 * Provides a mechanism for selecting one or more files from 
 * given folder of given cloud.
 * Shows the files and sub-folders of given folder.
 *
 * @param Pieform  $form    The form to render the element for
 * @param array    $element The element to render
 * @return string           The HTML for the element
 */
function pieform_element_datatables(Pieform $form, $element) {/*{{{*/
    $name = Pieform::hsc($element['name']);
    $WWWROOT = get_config('wwwroot');
    $SERVICE = Pieform::hsc($element['service']); // Cloud service name
    $FULLPATH = (isset($element['fullpath']) ? $element['fullpath'] : '/');
    $options = (isset($element['options']) ? $element['options'] : null);
	$path = $SERVICE . 'path';
    
    $strService = get_string('servicename', 'blocktype.cloud/'.$SERVICE);
    $strName    = get_string('Name', 'artefact.file');
    $strSelect  = (isset($options['manageButtons']) && $options['manageButtons'] ? get_string('manage', 'artefact.cloud') : get_string('select', 'mahara'));

    $html = <<<EOHTML
<input type="hidden" name="{$name}" id="{$name}" value="">
<input type="hidden" name="{$path}" id="{$path}" value="">
<table id="fileList" class="tablerenderer filelist" width="100%">
    <thead>
        <tr> 
            <th class="filethumb" width="24">&nbsp;</th>
            <th>{$strName}</th>
            <th width="40">&nbsp;</th>
            <th width="1"></th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
EOHTML;

    $result = $html;
    if (isset($options['manageButtons']) && $options['manageButtons']) {
        $result .= '<script type="text/javascript">';
        $result .= pieform_element_datatables_views_js($form, $element);
        $result .= '</script>';
    }
    return $result;
}


function pieform_element_datatables_views_js(Pieform $form, $element) {
    $name = (isset($element['name']) ? Pieform::hsc($element['name']) : 'instconf');

    $options = (isset($element['options']) ? $element['options'] : null);
    $MODE = '000000';
    $MODE[0] = (isset($options['manageButtons']) ? ($options['manageButtons'] ? '1' : '0') : '0');
    $MODE[1] = (isset($options['showFolders']) ? ($options['showFolders'] ? '1' : '0') : '1');
    $MODE[2] = (isset($options['showFiles']) ? ($options['showFiles'] ? '1' : '0') : '1');
    $MODE[3] = (isset($options['selectFolders']) ? ($options['selectFolders'] ? '1' : '0') : '0');
    $MODE[4] = (isset($options['selectFiles']) ? ($options['selectFiles'] ? '1' : '0') : '1');
    $MODE[5] = (isset($options['selectMultiple']) ? ($options['selectMultiple'] ? '1' : '0') : '1');
    
    $id = (isset($element['current']) ? $element['current'] : 0);
    $WWWROOT  = get_config('wwwroot');
    $SERVICE  = Pieform::hsc($element['service']);
    $BLOCKID  = Pieform::hsc($element['block']);
	$path = $SERVICE . 'path';
    
    $firstpage    = json_encode(get_string('firstpage', 'artefact.cloud'));
    $previouspage = json_encode(get_string('previouspage', 'artefact.cloud'));
    $nextpage     = json_encode(get_string('nextpage', 'artefact.cloud'));
    $lastpage     = json_encode(get_string('lastpage', 'artefact.cloud'));
    $emptytable   = json_encode(get_string('emptytable', 'artefact.cloud'));
    $info         = json_encode(get_string('info', 'artefact.cloud'));
    $infoempty    = json_encode(get_string('infoempty', 'artefact.cloud'));
    $infofiltered = json_encode(get_string('infofiltered', 'artefact.cloud'));
    $lengthmenu   = json_encode(get_string('lengthmenu', 'artefact.cloud'));
    $loading      = json_encode(get_string('loading', 'artefact.cloud'));
    $processing   = json_encode(get_string('processing', 'artefact.cloud'));
    $search       = json_encode(get_string('search', 'artefact.cloud'));
    $zerorecords  = json_encode(get_string('zerorecords', 'artefact.cloud'));
    
    $js = <<<EOJS
//
// fnReloadAjax()
//
jQuery.fn.dataTableExt.oApi.fnReloadAjax = function ( oSettings, sNewSource, fnCallback, bStandingRedraw ) {
    if ( typeof sNewSource != 'undefined' && sNewSource != null ) {
        oSettings.sAjaxSource = sNewSource;
    }
    this.oApi._fnProcessingDisplay( oSettings, true );
    var that = this;
    var iStart = oSettings._iDisplayStart;
    var aData = [];
    
    this.oApi._fnServerParams( oSettings, aData );
    
    oSettings.fnServerData( oSettings.sAjaxSource, aData, function(json) {
        /* Clear the old information from the table */
        that.oApi._fnClearTable( oSettings );
        
        /* Got the data - add it to the table */
        var aData = (oSettings.sAjaxDataProp !== "") ?    that.oApi._fnGetObjectDataFn( oSettings.sAjaxDataProp )( json ) : json;
        
        for ( var i=0 ; i<aData.length ; i++ ) {
            that.oApi._fnAddData( oSettings, aData[i] );
        }
        
        oSettings.aiDisplay = oSettings.aiDisplayMaster.slice();
        that.fnDraw();
        
        if ( typeof bStandingRedraw != 'undefined' && bStandingRedraw === true ) {
            oSettings._iDisplayStart = iStart;
            that.fnDraw( false );
        }
        
        that.oApi._fnProcessingDisplay( oSettings, false );
        
        /* Callback user function - for event handlers etc */
        if ( typeof fnCallback == 'function' && fnCallback != null ) {
            fnCallback( oSettings );
        }
    }, oSettings );
}


var oTable = jQuery('#fileList').dataTable( {
    "asStripeClasses": [ 'r0', 'r1' ],
    "bLengthChange": false,
    "bFilter": false,
    "bSort": true,
    "bInfo": false,
    "bPaginate": false,
    "bProcessing": true,
    "bServerSide": false,
    "sAjaxSource": '{$WWWROOT}artefact/cloud/form/elements/datatables.json.php?service={$SERVICE}&mode={$MODE}&block={$BLOCKID}&id=0',
    "aaSorting": [[3,'desc']],
    "aaSortingFixed": [[3,'desc']],
    "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
        if (aData[3] == "folder" || aData[3] == "parentfolder") {
            jQuery(nRow).addClass('folder');
        }
        if (aData[3] == "file") {
            jQuery(nRow).addClass('file');
        }
        return nRow;
    },
    "oLanguage": {
        "oPaginate": {
            "sFirst": {$firstpage},
            "sPrevious": {$previouspage},
            "sNext": {$nextpage},
            "sLast": {$lastpage}
        },
        "sEmptyTable": {$emptytable},
        "sInfo": {$info},
        "sInfoEmpty": {$infoempty},
        "sInfoFiltered": {$infofiltered},
        "sLengthMenu": {$lengthmenu},
        "sLoadingRecords": {$loading},
        "sProcessing": {$processing},
        "sSearch": {$search},
        "sZeroRecords": {$zerorecords}
    },
    "aoColumnDefs": [
        { "aTargets": [ 0 ], "sClass": "filethumb", "bSortable": false },     /* Icon column */
        { "aTargets": [ 1 ], "sClass": "filename" },                          /* Name column */
        { "aTargets": [ 2 ], "sClass": "center s", "bSortable": false },      /* Ctrl column */
        // This column must be last, because it is hidden or everything gets screwed up!
        { "aTargets": [ 3 ], "bVisible": false, "bSortable": false }          /* Type column */
    ]
} );
    
jQuery('#fileList').on('click', 'a.changefolder', function () {
    oTable.fnReloadAjax('{$WWWROOT}artefact/cloud/form/elements/datatables.json.php?service={$SERVICE}&mode={$MODE}&block={$BLOCKID}&id=' + jQuery(this).attr('id'));
} );
    
jQuery('#instconf').submit(function () {
    // Serialize values of all form elements and write then into hidden input element.
    // That way we can get all the values on 'the other side' and save them into DB.
	var files = jQuery(this).find('input[name="artefacts[]"]').serialize();
    jQuery('#{$name}').val(files);
} );
EOJS;
    return $js;
}

/**
 * When the element exists in a form that's present when the page is
 * first generated the following function gets called and the js file
 * below will be inserted into the head data.  Unfortunately, when
 * this element is present in a form that gets called in an ajax
 * request (currently on the view layout page), the .js file is not
 * loaded and so it's added explicitly to the smarty() call.
 */
function pieform_element_datatables_get_headdata($element) {
    $headdata = array(
        '<script type="text/javascript" src="' . get_config('wwwroot') . 'js/jquery/jquery.js"></script>',
        '<script type="text/javascript" src="' . get_config('wwwroot') . 'artefact/cloud/datatables/js/jquery.dataTables.min.js"></script>'
    );
    return $headdata;
}

?>
