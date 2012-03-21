{include file="findInclude:common/templates/header.tpl"}
<div class="nonfocal"><h3>{$taskTitle}</h3>
{if $taskDate}
<span class="smallprint">{$taskDate}</span><br />
{/if}
{if $taskDueDate}
<span class="smallprint">Due: {$taskDueDate}</span>
{/if}
</div>
{if $taskDescription}
<div class="focal">{$taskDescription}</div>
{/if}
{if $links}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links subTitleNewline=true}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
