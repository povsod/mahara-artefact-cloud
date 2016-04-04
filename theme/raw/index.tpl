{include file="header.tpl"}

<div id="cloudserviceslist" class="panel-items">
{if $data}
    {foreach from=$data item=service}
        {include file="artefact:cloud:service.tpl" service=$service}
    {/foreach}
{/if}
</div>

{include file="footer.tpl"}
