{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal"><h2>{$taskTitle}</h2>
<p class="smallprint">
{if $taskDate}
{$taskDate}<br />
{/if}
{if $taskDueDate}
Due: {$taskDueDate}
{/if}
</p>
</div>
{if $taskDescription}
<div class="focal">{$taskDescription}</div>
{/if}
{if $links}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links subTitleNewline=true}
{/if}
{if $gradeLink}
{include file="findInclude:common/templates/navlist.tpl" navListHeading=$gradeLinkHeading navlistItems=$gradeLink subTitleNewline=true}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
