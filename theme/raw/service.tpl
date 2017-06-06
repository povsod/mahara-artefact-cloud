<div class="{if $service->pending}pending panel-danger{else}panel-default{/if} panel panel-half">
    <h3 class="panel-heading profile-block">
        <span class="user-icon" id="cloudservice_{$service->name}">
            {if $service->subservice}
            <img src="{theme_url filename="$service->subservice/icon.png" plugin="artefact/cloud/blocktype/$service->name"}" alt="{$service->name}" width="72">
            {else}
            <img src="{theme_url filename="images/icon.png" plugin="artefact/cloud/blocktype/$service->name"}" alt="{$service->name}" width="72">
            {/if}
        </span>
        <a href="{$service->url}">
            {str tag=servicename section=blocktype.cloud/$service->name}
            {if $service->subname}&bullet; {$service->subname}{/if}
        </a>
    </h3>
    <div class="panel-body">
    {if $service->pending}
        <strong>{str tag=servicenotconfigured section=artefact.cloud}</strong>
    {else}
        {if $service->auth}
            <div class="username">
                <span class="icon icon-user icon-md left icon-fw"></span>
                {$service->account->user_name}
            </div>
            <div class="userid">
                <span class="icon icon-tag left icon-fw"></span>
                {$service->account->user_id}
            </div>
            <div class="username">
                <span class="icon icon-envelope left icon-fw"></span>
                <a href="mailto:{$service->account->user_email}">{$service->account->user_email}</a>
            </div>
            <div class="username">
                <span class="icon icon-link left icon-fw"></span>
                <a href="{$service->account->user_profile}" target="_blank">{$service->account->user_profile}</a>
            </div>
            {if $service->account->space_amount != null}
                <a class="collapsed" data-toggle="collapse" href="#{$service->name}_quota" aria-expanded="false">
                    <span class="icon icon-chevron-down collapse-indicator pull-right text-small"></span>
                </a>
                <div class="collapse" id="{$service->name}_quota">
                    <hr />
                    <p id="quota_message">
                        {str tag=quotausage section=mahara arg1=$service->account->space_used arg2=$service->account->space_amount|safe}
                    </p>
                    <div id="quotawrap" class="progress">
                        <div id="quota_fill" class="progress-bar {if $service->account->space_ratio < 11}small-progress{/if}" role="progressbar" aria-valuenow="{if $service->account->space_ratio }{$service->account->space_ratio}{else}0{/if}" aria-valuemin="0" aria-valuemax="100" style="width: {$service->account->space_ratio}%;">
                        <span>{$service->account->space_ratio}%</span>
                        </div>
                    </div>
                </div>
            {else}
                <a href="#">
                    <span class="icon icon-chevron-down icon-inverse pull-right text-small"></span>
                </a>
            {/if}            
        {else}
            <strong>{str tag=servicenotauthorised section=artefact.cloud}</strong>
        {/if}
    {/if}
    </div>

    {if $service->pending}
    <div class="panel-footer">
        <a href="{$WWWROOT}contact.php" class="btn">
            <span class="icon icon-envelope left icon-md"></span>
            {str tag=contactus}
        </a>
    </div>
    {else}
        {if $service->auth}
        <div class="panel-footer">
            <a href="{$WWWROOT}artefact/cloud/blocktype/{$service->name}/manage.php" class="btn">
                <span class="icon icon-wrench left icon-lg"></span>
                {str tag=manage section=artefact.cloud}
            </a>
            <a href="{$WWWROOT}artefact/cloud/blocktype/{$service->name}/account.php?action=logout&sesskey={$SESSKEY}" class="btn">
                <span class="icon icon-lock left icon-lg text-danger"></span>
                {str tag=accessrevoke section=artefact.cloud}
            </a>
        </div>
        {else}
        <div class="panel-footer">
            <a href="{$WWWROOT}artefact/cloud/blocktype/{$service->name}/account.php?action=login&sesskey={$SESSKEY}" class="btn">
                <span class="icon icon-unlock-alt left icon-lg"></span>
                {str tag=accessgrant section=artefact.cloud}
            </a>
        </div>
        {/if}
    {/if}

</div>