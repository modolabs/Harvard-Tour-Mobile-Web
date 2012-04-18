{include file="findInclude:common/templates/header.tpl"}
<div class="nonfocal"><h3>{$contentTitle}</h3>
{if $contentAuthor}
<span class="smallprint">{$contentAuthor}</span><br/>
{/if}
{if $contentPublished}
<span class="smallprint">{$contentPublished}</span><br/>
{/if}
<span class="smallprint">{$courseTitle}</span><br/>
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
