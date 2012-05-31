{block name="tasksHeader"}{/block}
{block name="tasksList"}
{if $tasks}
{block name="groupSelector"}
{if $tasksGroupLinks}
<ul class="tabstrip {$tasksTabCount}tabs" id="tasks-grouplist">
{foreach $tasksGroupLinks as $index => $groupLink}
<li{if $tasksGroup == $index} class="active"{/if} index="{$index}"><a href="{$groupLink.url}" onclick="return updateGroupTab('tasks', '{$index}', '{$groupLink.url}');">{$groupLink.title}</a></li>
{/foreach}
</ul>
{/if}
{/block}
<div id="tasks-content">
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