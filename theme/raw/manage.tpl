{include file="header.tpl"}

{if $SUBSERVICE}
<h1><img src="{theme_url filename="$SUBSERVICE/service.png" plugin="artefact/cloud/blocktype/$SERVICE"}" border="0"></h1>
{else}
<h1><img src="{theme_url filename="images/service.png" plugin="artefact/cloud/blocktype/$SERVICE"}" border="0"></h1>
{/if}
<div class="btn-top-right btn-group btn-group-top">
    <a class="btn btn-default clouds" href="{$WWWROOT}artefact/cloud/index.php">
        <span class="icon icon-lg icon-cloud left"></span>
        {str section="artefact.cloud" tag="clouds"}
    </a>
</div>
{$manageform|safe}

{include file="footer.tpl"}
