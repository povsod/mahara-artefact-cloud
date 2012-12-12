{include file="header.tpl"}

<h1><img src="{$WWWROOT}artefact/cloud/blocktype/{$account.service_name}/img/service.png" border="0"></h1>

<table>
 <thead>
   <tr>
     <th>{str tag=userinfo section=artefact.cloud}</th>
{if $account.space_amount != null}
     <th><center>{str tag=usageinfo section=artefact.cloud}</center></th>
{/if}
   </tr>
 </thead>
 <tbody>
    <td>
      <ul>
        <li><strong>{str tag=username section=artefact.cloud}:</strong> {$account.user_name}</li>
        {if $account.user_email != null}<li><strong>{str tag=useremail section=artefact.cloud}:</strong> <a href="mailto:{$account.user_email}">{$account.user_email}</a></li>{/if}
        {if $account.user_profile != null}<li><strong>{str tag=userprofile section=artefact.cloud}:</strong> <a href="{$account.user_profile}" target="_blank">{$account.user_profile}</a></li>{/if}
        {if $account.user_id != null}<li><strong>{str tag=userid section=artefact.cloud}:</strong> {$account.user_id}</li>{/if}
      </ul>
    </td>
{if $account.space_amount != null}
    <td style="padding-left:40px;padding-right:20px;">
    <div style="padding-top:5px">
    <p id="quota_message">{str tag=quotausage section=mahara arg1=$account.space_used arg2=$account.space_amount}</p>
    <div id="quotawrap">
{if $account.space_ratio < 100}
    <div id="quota_fill" style="width: {$account.space_ratio*2}px;">&nbsp;</div>
    <p id="quota_bar">
        <span id="quota_percentage">{$account.space_ratio}%</span>
    </p>
{else}
    <div id="quota_fill" style="display: none; width: {$account.space_ratio*2}px;">&nbsp;</div>
    <p id="quota_bar_100">
        <span id="quota_percentage">{$account.space_ratio}%</span>
    </p>
{/if}
    </div>
    </div>
    </td>
{/if}
 <tbody>
</table>

{include file="footer.tpl"}
