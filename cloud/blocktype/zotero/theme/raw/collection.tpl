{if $microheaders}{include file="viewmicroheader.tpl"}{else}{include file="header.tpl"}{/if}

        <h2>
            <a href="{$WWWROOT}view/view.php?id={$viewid}">{$viewtitle}</a>{if $ownername} {str tag=by section=view}
            <a href="{$WWWROOT}{$ownerlink}">{$ownername}</a>: {$data.title}
        </h2>

		<table id="itemslist" width="100%" class="tablerenderer items">
			<colgroup width="75%"></colgroup>
			<colgroup width="25%"></colgroup>
			<thead>
				<tr>
					<th class="itemtitle">{str tag='title' section='blocktype.cloud/zotero'}</th>
					<th class="itemauthor">{str tag='author' section='blocktype.cloud/zotero'}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$data.items item=item}
				<tr class="{cycle values='r0,r1'}">
					<td><a href="{$WWWROOT}artefact/cloud/blocktype/zotero/details.php?id={$item.id}&type=item&view={$viewid}">{$item.title}</a></td>
					<td>{$item.author}</td>
				</tr>
				{/foreach}
			</tbody>
		</table>		

{if $microheaders}{include file="microfooter.tpl"}{else}{include file="footer.tpl"}{/if}
