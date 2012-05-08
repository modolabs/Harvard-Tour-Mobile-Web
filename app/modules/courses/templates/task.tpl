{include file="findInclude:common/templates/header.tpl"}
<div class="nonfocal"><h2>{$taskTitle}</h2>
<span class="termtitle">
{if $taskDate}
{$taskDate}<br />
{/if}
{if $taskDueDate}
Due: {$taskDueDate}
{/if}
</span>
</div>
{if $taskDescription}
<div class="focal">{$taskDescription}</div>
{/if}
{if $links}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links subTitleNewline=true}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
