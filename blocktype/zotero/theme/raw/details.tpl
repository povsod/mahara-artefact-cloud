{if $microheaders}{include file="viewmicroheader.tpl"}{else}{include file="header.tpl"}{/if}

        <h2>
            {if $viewid != 0}
            <a href="{$WWWROOT}view/view.php?id={$viewid}">{$viewtitle}</a>{if $ownername} {str tag=by section=view}
            <a href="{$WWWROOT}{$ownerlink}">{$ownername}</a>: {$data.name}{/if}
            {else}{$viewtitle}{/if}
        </h2>

        <div id="view">
        <h4>
            <div class="fl filedata-thumb">
                {if $type == 'folder'}
                <img alt="" src="{theme_url filename="images/folder.png"}">
                {else}
                <img alt="" src="{theme_url filename="images/file.png"}">
                {/if}
            </div>
            {$data.name}
        </h4>

        <table class="filedata">
            <tbody>
                <tr>
                    <th valign="top">{str tag=creators section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle">{foreach from=$data.creators item=c}
                    {$c.firstName} {$c.lastName} ({str tag=creator.$c.creatorType section=blocktype.cloud/zotero})<br>
                    {/foreach}</td>
                </tr>
                <tr>
                    <th>{str tag=type section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle">{str tag=type.$data.type section=blocktype.cloud/zotero}</td>
                </tr>
                <tr>
                    <th valign="top">{str tag=abstract section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle">{$data.abstract}</td>
                </tr>
                <tr>
                    <th>{str tag=Created section=artefact.file}:</th>
                    <td style="vertical-align:middle">{$data.created}</td>
                </tr>
                <tr>
                    <th>{str tag=lastmodified section=artefact.file}:</th>
                    <td style="vertical-align:middle">{$data.updated}</td>
                </tr>
                {if $data.series}
                <tr>
                    <th>{str tag=series section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle">{$data.series}</td>
                </tr>
                {/if}
                {if $data.seriesNumber}
                <tr>
                    <th>{str tag=seriesNumber section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle">{$data.seriesNumber}</td>
                </tr>
                {/if}
                {if $data.volume}
                <tr>
                    <th>{str tag=volume section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle">{$data.volume}</td>
                </tr>
                {/if}
                {if $data.numVolumes}
                <tr>
                    <th>{str tag=numVolumes section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle">{$data.numVolumes}</td>
                </tr>
                {/if}
                <tr>
                    <th>{str tag=publisher section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle">{$data.publisher}</td>
                </tr>
                {if $data.edition}
                <tr>
                    <th>{str tag=edition section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle">{$data.edition}</td>
                </tr>
                {/if}
                {if $data.place}
                <tr>
                    <th>{str tag=place section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle">{$data.place}</td>
                </tr>
                {/if}
                {if $data.language}
                <tr>
                    <th>{str tag=language section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle">{$data.language}</td>
                </tr>
                {/if}
                {if $data.ISBN}
                <tr>
                    <th>{str tag=ISBN section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle">{$data.ISBN}</td>
                </tr>
                {/if}
                {if $data.ISSN}
                <tr>
                    <th>{str tag=ISSN section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle">{$data.ISSN}</td>
                </tr>
                {/if}
                {if $data.url}
                <tr>
                    <th>{str tag=url section=blocktype.cloud/zotero}:</th>
                    <td style="vertical-align:middle"><a href="{$data.url}">{$data.url}</a></td>
                </tr>
                {/if}
            </tbody>
        </table>
        </div>

{if $microheaders}{include file="microfooter.tpl"}{else}{include file="footer.tpl"}{/if}
