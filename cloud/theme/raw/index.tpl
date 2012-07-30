{include file="header.tpl"}

<table id="cloudlist" width="100%" class="tablerenderer cloud">
    <colgroup width="10%"></colgroup>
    <colgroup width="45%"></colgroup>
    <colgroup width="12%"></colgroup>
    <colgroup width="12%"></colgroup>
    <colgroup width="20%"></colgroup>
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
            <td align="center"><img src="{$WWWROOT}artefact/cloud/blocktype/{$cloud.service_name}/img/logo.png" width="32" height="32"></td>
            <td style="vertical-align:middle"><a href="{$cloud.service_url}" target="_blank">{str tag='servicename' section='blocktype.cloud/$cloud.service_name'}</a></td>
            <td style="vertical-align:middle">{if $cloud.service_auth}<a href="{$WWWROOT}artefact/cloud/blocktype/{$cloud.service_name}/account.php" title="{str tag="account" section="artefact.cloud"}" class="btn-view">{str tag="account" section="artefact.cloud"}</a>{/if}</td>
            <td style="vertical-align:middle">{if $cloud.service_manage}<a href="{$WWWROOT}artefact/cloud/blocktype/{$cloud.service_name}/manage.php" title="{str tag="manage" section="artefact.cloud"}" class="btn-manage">{str tag="manage" section="artefact.cloud"}</a>{/if}</td>
            <td style="vertical-align:middle">{if $cloud.service_auth}<a href="{$WWWROOT}artefact/cloud/blocktype/{$cloud.service_name}/account.php?action=logout" title="{str tag="accessrevoke" section="artefact.cloud"}" class="btn-deny">{str tag="accessrevoke" section="artefact.cloud"}</a>{else}<a href="{$WWWROOT}artefact/cloud/blocktype/{$cloud.service_name}/account.php?action=login" title="{str tag="accessgrant" section="artefact.cloud"}" class="btn-access">{str tag="accessgrant" section="artefact.cloud"}</a>{/if}</td>
        </tr>
        {/foreach}
    </tbody>
</table>


{include file="footer.tpl"}
