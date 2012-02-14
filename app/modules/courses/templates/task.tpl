{include file="findInclude:common/templates/header.tpl"}
<div class="nonfocal"><h3>{$taskTitle}</h3>
<span class="smallprint">{$taskDate}</span><br />
<span class="smallprint">Due: {$taskDueDate}</span>
{if $taskDescription}
<p>{$taskDescription}</p>
{/if}
</div>
{if $links}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links subTitleNewline=true}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
