{if $microheaders}{include file="viewmicroheader.tpl"}{else}{include file="header.tpl"}{/if}

        <h2>
            <a href="{$WWWROOT}view/view.php?id={$viewid}">{$viewtitle}</a>{if $ownername} {str tag=by section=view}
            <a href="{$WWWROOT}{$ownerlink}">{$ownername}</a>: {$data.title}
        </h2>

        <div id="view">
        <h4>
            <div class="fl filedata-thumb">
                {if $type == 'collection'}
                <img alt="" src="{theme_url filename="images/folder.png"}">
                {else}
                <img alt="" src="{theme_url filename="images/file.png"}">
                {/if}
            </div>
            {$data.title}
        </h4>

        <table class="filedata">
            <tbody>
                {foreach from=$data.creators item=creator}
                {if $creator.firstName != '' && $creator.lastName != ''}
                <tr>
                    <th>{str tag='creator.$creator.creatorType' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$creator.lastName}, {$creator.firstName}</td>
                </tr>
                {elseif $creator.name != ''}
                <tr>
                    <th>{str tag='creator.$creator.creatorType' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$creator.name}</td>
                </tr>
                {/if}
                {/foreach}
                {if $data.type != ''}
                <tr>
                    <th>{str tag='type' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{str tag='type.$data.type' section='blocktype.cloud/zotero'}</td>
                </tr>
                {/if}
                {if $data.abstract != ''}
                <tr>
                    <th>{str tag='abstract' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$data.abstract}</td>
                </tr>
                {/if}
                {if $data.series != ''}
                <tr>
                    <th>{str tag='series' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$data.series}</td>
                </tr>
                {/if}
                {if $data.seriesNumber != ''}
                <tr>
                    <th>{str tag='seriesNumber' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$data.seriesNumber}</td>
                </tr>
                {/if}
                {if $data.volume != ''}
                <tr>
                    <th>{str tag='volume' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$data.volume}</td>
                </tr>
                {/if}
                {if $data.numVolumes != ''}
                <tr>
                    <th>{str tag='numVolumes' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$data.numVolumes}</td>
                </tr>
                {/if}
                {if $data.publisher != ''}
                <tr>
                    <th>{str tag='publisher' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$data.publisher}</td>
                </tr>
                {/if}
                {if $data.edition != ''}
                <tr>
                    <th>{str tag='edition' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$data.edition}</td>
                </tr>
                {/if}
                {if $data.place != ''}
                <tr>
                    <th>{str tag='place' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$data.place}</td>
                </tr>
                {/if}
                {if $data.date != ''}
                <tr>
                    <th>{str tag='date' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$data.date}</td>
                </tr>
                {/if}
                {if $data.numPages != ''}
                <tr>
                    <th>{str tag='numPages' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$data.numPages}</td>
                </tr>
                {/if}
                {if $data.language != ''}
                <tr>
                    <th>{str tag='language' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$data.language}</td>
                </tr>
                {/if}
                {if $data.ISBN != ''}
                <tr>
                    <th>{str tag='ISBN' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle"><a href="http://www.worldcat.org/isbn/{$data.ISBN}" target="_blank">{$data.ISBN}</a></td>
                </tr>
                {/if}
                {if $data.ISSN != ''}
                <tr>
                    <th>{str tag='ISSN' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle"><a href="http://www.worldcat.org/issn/{$data.ISSN}" target="_blank">{$data.ISSN}</a></td>
                </tr>
                {/if}
                {if $data.url != ''}
                <tr>
                    <th>{str tag='url' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle"><a href="{$data.url}" target="_blank">{$data.url}</a></td>
                </tr>
                {/if}
                {if $data.accessDate != ''}
                <tr>
                    <th>{str tag='accessDate' section='blocktype.cloud/zotero'}:</th>
                    <td style="vertical-align:middle">{$data.accessDate}</td>
                </tr>
                {/if}
                <tr>
                    <th>{str tag=Created section=artefact.file}:</th>
                    <td style="vertical-align:middle">{$data.created}</td>
                </tr>
                <tr>
                    <th>{str tag=lastmodified section=artefact.file}:</th>
                    <td style="vertical-align:middle">{$data.updated}</td>
                </tr>
                {if $data.series != ''}
                <tr>
                    <th>series:</th>
                    <td style="vertical-align:middle">{$data.series}</td>
                </tr>
                {/if}
            </tbody>
        </table>
        </div>

{if $microheaders}{include file="microfooter.tpl"}{else}{include file="footer.tpl"}{/if}
