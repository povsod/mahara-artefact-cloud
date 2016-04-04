{if $folders}
{foreach $folders folder}
<div class="folderdownload-item" title="{$folder.title}">
  <div class="fl"><img src="{$folder.iconsrc}" alt=""></div>
  <div style="margin-left: 30px;">
    <h4>{$folder.title|str_shorten_text:20}</h4>
    {if $folder.description}<p>{$folder.description}</p>{/if}
    <div class="description">{$folder.size} | {$folder.ctime}
    | <a href="{$WWWROOT}artefact/cloud/blocktype/dropbox/details.php?id={$folder.id}&type=folder&view={$viewid}">{str tag=Details section=artefact.file}</a></div>
  </div>
</div>
{/foreach}
{/if}

{if $files}
{foreach $files file}
<div class="filedownload-item" title="{$file.title}">
  <div class="fl"><a href="{$WWWROOT}artefact/cloud/blocktype/dropbox/download.php?id={$file.id}&view={$viewid}" target="_blank"><img src="{$file.iconsrc}" alt=""></a></div>
  <div style="margin-left: 30px;">
    <h4><a href="{$WWWROOT}artefact/cloud/blocktype/dropbox/download.php?id={$file.id}&view={$viewid}" target="_blank">{$file.title|str_shorten_text:20}</a></h4>
    {if $file.description}<p>{$file.description}</p>{/if}
    <div class="description">{$file.size} | {$file.ctime}
    | <a href="{$WWWROOT}artefact/cloud/blocktype/dropbox/details.php?id={$file.id}&type=file&view={$viewid}">{str tag=Details section=artefact.file}</a>
    | <a href="{$WWWROOT}artefact/cloud/blocktype/dropbox/preview.php?id={$file.id}&view={$viewid}" target="_blank">{str tag=preview section=artefact.cloud}</a></div>
  </div>
</div>
{/foreach}
{/if}

