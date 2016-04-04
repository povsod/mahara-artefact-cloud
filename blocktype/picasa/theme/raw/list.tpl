{if $files}
{foreach $files file}
<div class="filedownload-item" title="{$file.title}">
  <div class="fl">{if $file.type == 'file'}<a href="{$WWWROOT}artefact/cloud/blocktype/picasa/download.php?id={$file.id}" target="_blank"><img src="{$file.iconsrc}" alt=""></a>{else}<img src="{$file.iconsrc}" alt="">{/if}</div>
  <div style="margin-left: 30px;">
    <h4>{if $file.type == 'file'}<a href="{$WWWROOT}artefact/cloud/blocktype/picasa/download.php?id={$file.id}" target="_blank">{$file.title|str_shorten_text:20}</a>{else}{$file.title|str_shorten_text:20}{/if}</h4>
    {if $file.description}<p>{$file.description}</p>{/if}
    <div class="description">{$file.size} | {$file.ctime}
    | <a href="{$WWWROOT}artefact/cloud/blocktype/picasa/details.php?id={$file.id}&type=file&view={$viewid}">{str tag=Details section=artefact.file}</a>
    | <a href="{$WWWROOT}artefact/cloud/blocktype/picasa/preview.php?id={$file.id}&view={$viewid}" target="_blank">{str tag=preview section=artefact.cloud}</a></div>
  </div>
</div>
{/foreach}
{/if}

{if $folders}
{foreach $folders folder}
<div class="folderdownload-item" title="{$folder.title}">
  <h2>{$folder.title|str_shorten_text:20}</h2>
  <div style="line-height:4px">&nbsp;</div>
  {foreach $folder.files file}
  <div class="filedownload-item" title="{$file.title}">
    <div class="fl">{if $file.type == 'file'}<a href="{$WWWROOT}artefact/cloud/blocktype/picasa/download.php?id={$file.id}" target="_blank"><img src="{$file.iconsrc}" alt=""></a>{else}<img src="{$file.iconsrc}" alt="">{/if}</div>
    <div style="margin-left: 30px;">
      <h4>{if $file.type == 'file'}<a href="{$WWWROOT}artefact/cloud/blocktype/picasa/download.php?id={$file.id}" target="_blank">{$file.title|str_shorten_text:20}</a>{else}{$file.title|str_shorten_text:20}{/if}</h4>
      <div class="description">{$file.size} | {$file.ctime}
      | <a href="{$WWWROOT}artefact/cloud/blocktype/picasa/details.php?id={$file.id}&type=file&view={$viewid}">{str tag=Details section=artefact.file}</a>
      | <a href="{$WWWROOT}artefact/cloud/blocktype/picasa/preview.php?id={$file.id}" target="_blank">{str tag=preview section=artefact.cloud}</a></div>
    </div>
  </div>
  {/foreach}
</div>
{/foreach}
{/if}
