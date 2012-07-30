<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2012 Catalyst IT Ltd and others; see:
 *                         http://wiki.mahara.org/Contributors
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
 *
 * @package    mahara
 * @subpackage blocktype-zotero
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

$string['title'] = 'File(s) from Zotero';
$string['description'] = 'Select files from Zotero cloud';

$string['service'] = 'Zotero'; // Same as plugin folder name, but can be CamelCase, e.g.: SkyDrive
$string['servicename'] = 'Zotero'; // Full service name, e.g.: WIndows Live SkyDrive

$string['requesttokennotreturned'] = 'There was no request token';
$string['accesstokennotreturned'] = 'There was no access token';
$string['accesstokensaved'] = 'Access token saved sucessfully';
$string['accesstokensavefailed'] = 'Failed to save access token';

$string['applicationgeneral'] = 'General information';
$string['applicationdesc'] = 'You must create %san application%s, if you wish to access and use Zotero API.';
$string['applicationname'] = 'Application name';
$string['applicationnamedesc'] = 'You must provide unique application name, e.g. the name of this site.';
$string['applicationtype'] = 'Application Type';
$string['applicationtypedesc'] = 'Please select \'Browser\' application type, when creating a new application.';
$string['consumerkey'] = 'Client key';
$string['consumerkeydesc'] = 'When you\'ll create an application, you\'ll get a Client key. Paste it here.';
$string['consumersecret'] = 'Client secret';
$string['consumersecretdesc'] = 'When you\'ll create an application, you\'ll get a Client secret. Paste it here.';
$string['applicationweb'] = 'Website';
$string['applicationwebdesc'] = 'The URL where this application will be used, e.g. URL of your site.';
$string['redirecturl'] = 'Redirect URL';
$string['redirecturldesc'] = 'URL to return user to, after successful authentication. Copy it and paste it to application settings.';

$string['selectreferences'] = 'Select reference collection';
$string['allreferences'] = 'All references';

$string['bibliographystyle'] = 'Bibliography style';

$string['style.american-anthropological-association'] = 'American Anthropological Association';
$string['style.apa5th'] = 'American Psychological Association 5th Edition';
$string['style.apa'] = 'American Psychological Association 6th Edition';
$string['style.chicago-author-date'] = 'Chicago Manual of Style (author-date)';
$string['style.chicago-fullnote-bibliography'] = 'Chicago Manual of Style (full note)';
$string['style.chicago-note-bibliography'] = 'Chicago Manual of Style (note)';
$string['style.elsevier-with-titles'] = 'Elsevier\'s Harvard Style (with titles)';
$string['style.harvard1'] = 'Harvard Reference format 1 (author-date)';
$string['style.ieee'] = 'IEEE';
$string['style.iso690-author-date-en'] = 'ISO-690 (author-date, English)';
$string['style.iso690-numeric-en'] = 'ISO-690 (numeric, English)';
$string['style.mhra'] = 'Modern Humanities Research Association (note with bibliography)';
$string['style.mla'] = 'Modern Language Association';
$string['style.mla-url'] = 'Modern Language Association with URL';
$string['style.nature'] = 'Nature';
$string['style.turabian-author-date'] = 'Turabian Style (author-date)';
$string['style.turabian-fullnote-bibliography'] = 'Turabian Style (full note with bibliography)';
$string['style.vancouver'] = 'Vancouver';

$string['creator.author'] = 'Author'; // Avtor
$string['creator.contributor'] = 'Contributor'; // 
$string['creator.editor'] = 'Editor'; // Urednik
$string['creator.seriesEditor'] = 'Series editor'; // Urednik zbirke
$string['creator.translator'] = 'Translator'; // Prevajalec

$string['type'] = 'Type';
$string['type.note'] = 'Note';
$string['type.book'] = 'Book';
$string['type.bookSection'] = 'Book Section';
$string['type.journalArticle'] = 'Journal Article';
$string['type.magazineArticle'] = 'Magazine Article';
$string['type.newspaperArticle'] = 'Newspaper Article';
$string['type.thesis'] = 'Thesis';
$string['type.letter'] = 'Letter';
$string['type.manuscript'] = 'Manuscript';
$string['type.interview'] = 'Interview';
$string['type.film'] = 'Film';
$string['type.artwork'] = 'Artwork';
$string['type.webpage'] = 'Web Page';
$string['type.attachment'] = 'Attachment';
$string['type.report'] = 'Report';
$string['type.bill'] = 'Bill';
$string['type.case'] = 'Case';
$string['type.hearing'] = 'Hearing';
$string['type.patent'] = 'Patent';
$string['type.statute'] = 'Statute';
$string['type.email'] = 'E-mail';
$string['type.map'] = 'Map';
$string['type.blogPost'] = 'Blog Post';
$string['type.instantMessage'] = 'Instant Message';
$string['type.forumPost'] = 'Forum Post';
$string['type.audioRecording'] = 'Audio Recording';
$string['type.presentation'] = 'Presentation';
$string['type.videoRecording'] = 'Video Recording';
$string['type.tvBroadcast'] = 'TV Broadcast';
$string['type.radioBroadcast'] = 'Radio Broadcast';
$string['type.podcast'] = 'Podcast';
$string['type.computerProgram'] = 'Computer Program';
$string['type.conferencePaper'] = 'Conference Paper';
$string['type.document'] = 'Document';
$string['type.encyclopediaArticle'] = 'Encyclopedia Article';
$string['type.dictionaryEntry'] = 'Dictionary Entry';

$string['abstract'] = 'Abstract';
$string['series'] = 'Series';
$string['seriesNumber'] = 'Series Number';
$string['volume'] = 'Volume';
$string['numVolumes'] = 'Number of Volumes';
$string['publisher'] = 'Publisher';
$string['edition'] = 'Edition';
$string['place'] = 'Place';
$string['date'] = 'Date';
$string['numPages'] = 'Number of Pages';
$string['language'] = 'Language';
$string['ISBN'] = 'ISBN';
$string['ISSN'] = 'ISSN';
$string['url'] = 'URL';
$string['accessDate'] = 'Access Date';

$string['author'] = 'Author';
$string['title'] = 'Title';

$string[''] = '';

?>
