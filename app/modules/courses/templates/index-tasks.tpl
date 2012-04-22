{if $tasks}
{block name="groupSelector"}
<ul class="tabstrip {$indexTasksTabCount}tabs">
{foreach $indexTasksGroupLinks as $index => $groupLink}
<li{if $indexTasksGroup == $index} class="active"{/if}><a href="{$groupLink.url}">By {$groupLink.title}</a>
{/foreach}
</ul>
{/block}
{foreach $tasks as $group}
    {$navListHeading=$group.title|default:''}
    {include file="findInclude:common/templates/navlist.tpl" navListHeading=$navListHeading navlistItems=$group.items subTitleNewline=true}
{/foreach}
{else}
{"NO_TASKS"|getLocalizedString}
{/if}
