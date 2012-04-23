{block name="tasksHeader"}{/block}
{block name="tasksList"}
{if $tasks}
<ul class="tabstrip {$tasksTabCount}tabs">
{foreach $tasksGroupLinks as $index => $groupLink}
<li{if $tasksGroup == $index} class="active"{/if}><a href="{$groupLink.url}">By {$groupLink.title}</a>
{/foreach}
</ul>
{foreach $tasks as $group}
    {$navListHeading=$group.title|default:''}
    {include file="findInclude:common/templates/navlist.tpl" navListHeading=$navListHeading navlistItems=$group.items subTitleNewline=true}
{/foreach}
{else}
{"NO_TASKS"|getLocalizedString}
{/if}
{/block}
{block name="tasksFooter"}{/block}