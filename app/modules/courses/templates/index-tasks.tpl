{block name="tasksHeader"}{/block}
{block name="tasksList"}
{if $tasks}
{block name="groupSelector"}
<ul class="tabstrip {$tasksTabCount}tabs">
{foreach $tasksGroupLinks as $index => $groupLink}
<li{if $tasksGroup == $index} class="active"{/if}><a href="{$groupLink.url}">By {$groupLink.title}</a>
{/foreach}
</ul>
{/block}
{foreach $tasks as $title => $group}
    {$navListHeading=$title|default:''}
    {include file="findInclude:common/templates/navlist.tpl" navListHeading=$navListHeading navlistItems=$group subTitleNewline=true}
{/foreach}
{else}
{"NO_TASKS"|getLocalizedString}
{/if}
{/block}
{block name="tasksFooter"}{/block}