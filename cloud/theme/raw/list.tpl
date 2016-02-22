{if $folders}
<ul class="list-group">
{foreach $folders folder}
    <li class="folderdownload-item list-group-item">
        {if $folder.iconsrc}
            <img src="{$folder.iconsrc}" alt="" class="folder-icon text-inline" />
        {else}
            <span class="icon icon-{$folder.artefacttype} icon-lg left"></span>
        {/if}

        <h4 class="title list-group-item-heading text-inline">
            <a href="{$WWWROOT}artefact/cloud/blocktype/{$SERVICE}/details.php?id={$folder.id}&type=folder&view={$viewid}" class="inner-link">
                 {$folder.title}
                 <span class="sr-only">
                    {str tag=Details section=artefact.file}
                </span>
            </a>
        </h4>
        <span class="text-small text-midtone"> -
            {$folder.ctime}
            [{$folder.size}]
        </span>
        {if $folder.description}
        <div class="folder-description">
            <p class="text-small">
                {$folder.description}
            </p>
        </div>
        {/if}

        {if $folder.files}
            <ul class="list-group">
            {foreach $folder.files file}
            <li class="filedownload-item list-group-item">
                <a href="{$WWWROOT}artefact/cloud/blocktype/{$SERVICE}/download.php?id={$file.id}&view={$viewid}" class="outer-link icon-on-hover">
                    <span class="sr-only">
                        {str tag=Download section=artefact.file} {$file.title}
                    </span>
                </a>

                {if $file.iconsrc}
                    <img src="{$file.iconsrc}" alt="" class="file-icon text-inline" />
                {else}
                    <span class="icon icon-{$file.artefacttype} icon-lg left"></span>
                {/if}

                <h4 class="title list-group-item-heading text-inline">
                    <a href="{$WWWROOT}artefact/cloud/blocktype/{$SERVICE}/details.php?id={$file.id}&type=file&view={$viewid}" class="inner-link">
                        {$file.title}
                        <span class="sr-only">
                            {str tag=Details section=artefact.file}
                        </span>
                    </a>
                </h4>
                <span class="text-small text-midtone"> -
                    {$file.ctime}
                    [{$file.size}]
                </span>
                <span class="icon icon-download icon-lg pull-right text-watermark icon-action"></span>
            </li>
            {/foreach}
            </ul>
        {/if}

    </li>
{/foreach}
</ul>
{/if}

{if $files}
<ul class="list-group">
{foreach $files file}
    <li class="filedownload-item list-group-item">
        <a href="{$WWWROOT}artefact/cloud/blocktype/{$SERVICE}/download.php?id={$file.id}&view={$viewid}" class="outer-link icon-on-hover">
            <span class="sr-only">
                {str tag=Download section=artefact.file} {$file.title}
            </span>
        </a>

        {if $file.iconsrc}
            <img src="{$file.iconsrc}" alt="" class="file-icon text-inline" />
        {else}
            <span class="icon icon-{$file.artefacttype} icon-lg left"></span>
        {/if}

        <h4 class="title list-group-item-heading text-inline">
            <a href="{$WWWROOT}artefact/cloud/blocktype/{$SERVICE}/details.php?id={$file.id}&type=file&view={$viewid}" class="inner-link">
                 {$file.title}
                 <span class="sr-only">
                    {str tag=Details section=artefact.file}
                </span>
            </a>
        </h4>
        <span class="text-small text-midtone"> -
            {$file.ctime}
            [{$file.size}]
        </span>
        <span class="icon icon-download icon-lg pull-right text-watermark icon-action"></span>
        {if $file.description}
        <div class="file-description">
            <p class="text-small">
                {$file.description}
            </p>
        </div>
        {/if}
    </li>
{/foreach}
</ul>
{/if}
