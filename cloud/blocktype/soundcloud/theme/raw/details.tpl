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
                {if $data.type == 'playlist'}
                <img alt="" src="{theme_url filename="images/folder.gif"}">
                {/if}
                {if $data.type == 'track'}
                <a href="{$WWWROOT}artefact/cloud/blocktype/soundcloud/download.php?id={$id}">
                <img alt="" src="{theme_url filename="images/file.gif"}">
                </a>
                {/if}
            </div>
            {if $data.type == 'track'}<a href="{$WWWROOT}artefact/cloud/blocktype/soundcloud/download.php?id={$id}">{$data.name}</a>{else}{$data.name}{/if}
        </h4>

        <table class="filedata">
            <tbody>
                <tr>
                    <th>{str tag=Description section=artefact.file}:</th>
                    <td style="vertical-align:middle">{$data.description}</td>
                </tr>
                {if $data.type == 'playlist'}
                <tr>
                    <th>{str tag=tracks section=blocktype.cloud/soundcloud}:</th>
                    <td style="vertical-align:middle">{$data.tracks}</td>
                </tr>
                {/if}
                <tr>
                    <th>{str tag=Created section=artefact.file}:</th>
                    <td style="vertical-align:middle">{$data.created}</td>
                </tr>
                <tr>
                    <th>{str tag=duration section=blocktype.cloud/soundcloud}:</th>
                    <td style="vertical-align:middle">{$data.duration} ({$data.duration_ms} ms)</td>
                </tr>
                <tr>
                    <th>{str tag=Shared section=artefact.cloud}:</th>
                    <td style="vertical-align:middle">{if $data.shared}<img alt="" src="{theme_url filename="images/success.gif"}">{/if}</td>
                </tr>
                {if $data.type == 'track'}
                <tr>
                    <th>{str tag=Download section=artefact.file}:</th>
                    <td style="vertical-align:middle">
                    <a href="{$WWWROOT}artefact/cloud/blocktype/soundcloud/download.php?id={$id}">{str tag=Download section=artefact.file}</a>
                    </td>
                </tr>
                {/if}
            </tbody>
        </table>
        </div>

{if $microheaders}{include file="microfooter.tpl"}{else}{include file="footer.tpl"}{/if}
