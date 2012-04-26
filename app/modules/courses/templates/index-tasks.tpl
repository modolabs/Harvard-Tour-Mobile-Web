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
{foreach $tasks as $title => $group}
    {$tasksListHeading=$title|default:''}
    {include file="findInclude:modules/courses/templates/tasksList.tpl" tasksListHeading=$tasksListHeading tasks=$group.items}
{/foreach}
{else}
{"NO_TASKS"|getLocalizedString}
{/if}
{/block}
{block name="tasksFooter"}{/block}