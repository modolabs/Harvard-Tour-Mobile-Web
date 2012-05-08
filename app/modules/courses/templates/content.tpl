{include file="findInclude:common/templates/header.tpl"}
<div class="nonfocal">
<h2 class="contenttitle">{$contentTitle}</h2>
<p class="smallprint">
{if $contentAuthor}
{$contentAuthor}<br/>
{/if}
{if $contentPublished}
{$contentPublished}<br/>
{/if}
{$courseTitle}<br/>
</p>
</div>
{if $contentDescription}
<div class="focal">
{$contentDescription}
</div>
{/if}
{if $contentData}
<div class="focal">
{$contentData}
</div>
{/if}
{if $links}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links subTitleNewline=true}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
