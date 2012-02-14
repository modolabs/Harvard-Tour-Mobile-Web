{include file="findInclude:common/templates/header.tpl"}
<div class="nonfocal"><h3>{$contentTitle}</h3>
<span class="smallprint">{$uploadDate}</span>
{if $contentDescription}
<p>{$contentDescription}</p>
{/if}
</div>
{if $links}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links subTitleNewline=true}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
