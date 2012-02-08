{include file="findInclude:common/templates/header.tpl"}

{if $terms}
{elseif $termTitle}
<div class="nonfocal"><h3>{$termTitle}</h3></div>
{/if}

{capture assign=tabBody}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$contents subTitleNewline=true}
{/capture}
{include file="findInclude:modules/courses/templates/courseTabs.tpl" tabBody=$tabBody}

{include file="findInclude:common/templates/footer.tpl"}
