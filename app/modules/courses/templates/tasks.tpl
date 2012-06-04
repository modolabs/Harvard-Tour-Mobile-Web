{include file="findInclude:common/templates/header.tpl"}

{block name="tasksHeader"}{/block}
{block name="tasksList"}
{if $tasks}
{block name="groupSelector"}
{if $tasksGroupLinks}
<ul class="tabstrip {$tasksTabCount}tabs" id="{$tabstripId}-tabstrip">
{foreach $tasksGroupLinks as $index => $groupLink}
<li{if $tasksGroup == $index} class="active"{/if}><a href="{$groupLink.url}" onclick="updateGroupTab(this, '{$tabstripId}', '{$groupLink.url}'); return false;">{$groupLink.title}</a></li>
{/foreach}
</ul>
{/if}
{/block}
<div id="{$tabstripId}-content">
{foreach $tasks as $group}
    {$navListHeading=$group.title|default:''}
    {include file="findInclude:modules/courses/templates/include/tasksList.tpl" tasksListHeading=$navListHeading tasks=$group.items}
{/foreach}
</div>
{else}
<p>{"NO_TASKS"|getLocalizedString}</p>
{/if}
{/block}
{block name="tasksFooter"}{/block}

{include file="findInclude:common/templates/footer.tpl"}
