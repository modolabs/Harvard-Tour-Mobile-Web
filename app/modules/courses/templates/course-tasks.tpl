{if $tasks}
<ul class="tabstrip twotabs">
{foreach $courseTasksGroupLinks as $index => $groupLink}
<li{if $courseTasksGroup == $index} class="active"{/if}><a href="{$groupLink.url}">By {$groupLink.title}</a>
{/foreach}
</ul>
{foreach $tasks as $group}
    {$navListHeading=$group.title|default:''}
    {include file="findInclude:common/templates/navlist.tpl" navListHeading=$navListHeading navlistItems=$group.items subTitleNewline=true}
{/foreach}
{else}
{"NO_TASKS"|getLocalizedString}
{/if}
