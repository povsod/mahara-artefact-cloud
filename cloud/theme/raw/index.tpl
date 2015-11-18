{include file="header.tpl"}

<table id="cloudlist" width="100%" class="tablerenderer cloud">
    <colgroup width="10%"></colgroup>
    <colgroup width="75%"></colgroup>
    <colgroup width="5%"></colgroup>
    <colgroup width="5%"></colgroup>
    <colgroup width="5%"></colgroup>
    <thead>
        <tr>
            <th>&nbsp;</th>
            <th class="cloudservice">{str tag='service' section='artefact.cloud'}</th>
            <th class="cloudaccount">{str tag='account' section='artefact.cloud'}</th>
            <th class="cloudaccount">{str tag='manage' section='artefact.cloud'}</th>
            <th class="cloudaccess">{str tag='access' section='artefact.cloud'}</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$data item=cloud}
        <tr class="{cycle values='r0,r1'}">
            <td align="center"><img src="{$WWWROOT}artefact/cloud/blocktype/{$cloud.service_name}/theme/raw/static/images/thumb.png" width="24" height="24"></td>
            <td style="vertical-align:middle"><a href="{$cloud.service_url}" target="_blank">{str tag='servicename' section='blocktype.cloud/$cloud.service_name'}{if $cloud.service_subname}&nbsp;-&nbsp;{$cloud.service_subname}{/if}</a></td>
            <td align="center" style="vertical-align:middle">{if $cloud.service_auth}<a href="{$WWWROOT}artefact/cloud/blocktype/{$cloud.service_name}/account.php" title="{str tag=account section=artefact.cloud}"><img src="{theme_url filename='images/btn_secreturl.png'}" alt="{str tag=account section=artefact.cloud}"></a>{/if}</td>
            <td align="center" style="vertical-align:middle">{if $cloud.service_manage}<a href="{$WWWROOT}artefact/cloud/blocktype/{$cloud.service_name}/manage.php" title="{str tag=manage section=artefact.cloud}"><img src="{theme_url filename='images/btn_configure.png'}" alt="{str tag=manage section=artefact.cloud}"></a>{/if}</td>
            <td align="center" style="vertical-align:middle">{if $cloud.service_auth}<a href="{$WWWROOT}artefact/cloud/blocktype/{$cloud.service_name}/account.php?action=logout" title="{str tag=accessrevoke section=artefact.cloud}"><img src="{theme_url filename='images/btn_deleteremove.png'}" alt="{str tag=accessrevoke section=artefact.cloud}"></a>{else}<a href="{$WWWROOT}artefact/cloud/blocktype/{$cloud.service_name}/account.php?action=login" title="{str tag=accessgrant section=artefact.cloud}"><img src="{theme_url filename='images/btn_access.png'}" alt="{str tag=accessgrant section=artefact.cloud}"></a>{/if}</td>
        </tr>
        {/foreach}
    </tbody>
</table>


{include file="footer.tpl"}
