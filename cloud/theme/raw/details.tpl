{include file="header.tpl"}

<div class="row">
    <div class="col-md-9">

        <h1 class="page-header">
            {$data.name}
            <span class="metadata">
                {if $viewid != 0}
                    | <a href="{$WWWROOT}view/view.php?id={$viewid}">{$viewtitle}</a>{if $ownername} {str tag=by section=view} <a href="{$WWWROOT}{$ownerlink}">{$ownername}</a>{/if}
                {else}
                    {$viewtitle}
                {/if}
            </span>
        </h1>

        <div id="view" class="view-pane">
            <div id="bottom-pane" class="panel panel-secondary">
                <div id="column-container" class="no-heading view-container">
                    <table class="filedata table-condensed">
                    <tbody>
                        <tr>
                            <th>{str tag=Description section=artefact.file}:</th>
                            <td style="vertical-align:middle">{$data.description}</td>
                        </tr>
                        <tr>
                            <th>{str tag=Created section=artefact.file}:</th>
                            <td style="vertical-align:middle">{$data.created}</td>
                        </tr>
                        <tr>
                            <th>{str tag=lastmodified section=artefact.file}:</th>
                            <td style="vertical-align:middle">{$data.updated}</td>
                        </tr>
                        {if $type != 'folder'}
                        <tr>
                            <th>{str tag=Size section=artefact.file}:</th>
                            <td style="vertical-align:middle">{$data.size} ({$data.bytes} {str tag=bytes section=artefact.file})</td>
                        </tr>
                        {/if}
                        <tr>
                            <th>{str tag=Shared section=artefact.cloud}:</th>
                            <td style="vertical-align:middle">{if $data.shared}<span class="icon-check icon icon-lg"></span>{/if}</td>
                        </tr>
                        {if $type != 'folder'}
                        <tr>
                            <th>{str tag=Download section=artefact.file}:</th>
                            <td style="vertical-align:middle">
                            <a class="btn btn-default btn-sm" href="{$WWWROOT}artefact/cloud/blocktype/{$SERVICE}/download.php?id={$id}&view={$viewid}">{str tag=Download section=artefact.file}</a>
                            </td>
                        </tr>
                        {/if}
                    </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

{include file="footer.tpl"}
