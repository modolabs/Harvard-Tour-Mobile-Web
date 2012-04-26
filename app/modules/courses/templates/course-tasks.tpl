{block name="tasksHeader"}{/block}
{block name="tasksList"}
{if $tasks}
<ul class="tabstrip {$tasksTabCount}tabs">
{foreach $tasksGroupLinks as $index => $groupLink}
<li{if $tasksGroup == $index} class="active"{/if}><a href="{$groupLink.url}">{$groupLink.title}</a></li>
{/foreach}
</ul>
{foreach $tasks as $group}
    {$navListHeading=$group.title|default:''}
    {include file="findInclude:modules/courses/templates/tasksList.tpl" tasksListHeading=$navListHeading tasks=$group.items}
{/foreach}
{else}
{"NO_TASKS"|getLocalizedString}
{/if}
{/block}
{block name="tasksFooter"}{/block}