{block name="tasksHeader"}{/block}
{block name="tasksList"}
{if $tasks}
{block name="groupSelector"}
<ul class="tabstrip {$tasksTabCount}tabs">
{foreach $tasksGroupLinks as $index => $groupLink}
<li{if $tasksGroup == $index} class="active"{/if}><a href="{$groupLink.url}">{$groupLink.title}</a></li>
{/foreach}
</ul>
{/block}
{foreach $tasks as $group}
    {$navListHeading=$group.title|default:''}
    {include file="findInclude:modules/courses/templates/include/tasksList.tpl" tasksListHeading=$navListHeading tasks=$group.items}
{/foreach}
{else}
<p>{"NO_TASKS"|getLocalizedString}</p>
{/if}
{/block}
{block name="tasksFooter"}{/block}