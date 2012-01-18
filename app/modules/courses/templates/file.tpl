{include file="findInclude:common/templates/header.tpl"}
<div id="tabbodies">
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$options}
<hr/>
<div style="margin:25px;">
{$itemName}
<br/>
{if publisheddate}
<span class="smallprint">{$uploadDate}</span>
{/if}
<br/>
{if $description}
Optional description field: {$description}
{/if}
</div>
</div>
{include file="findInclude:common/templates/footer.tpl"}
