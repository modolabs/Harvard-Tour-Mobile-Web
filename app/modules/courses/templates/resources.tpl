{include file="findInclude:common/templates/header.tpl"}

{block name="resourcesHeader"}{/block}
{if $resourcesLinks}
{block name="groupSelector"}
{if $resourcesGroupLinks}
<ul class="tabstrip {$resourcesTabCount}tabs" id="resources-grouplist">
{foreach $resourcesGroupLinks as $index => $groupLink}
<li{if $resourcesGroup == $index} class="active"{/if} index="{$index}"><a href="{$groupLink.url}" onclick="return updateGroupTab('resources', '{$index}', '{$groupLink.url}');">{$groupLink.title}</a></li>
{/foreach}
</ul>
{/if}
{/block}
<div id="resources-content">
{block name="resourcesList"}
{foreach $resourcesLinks as $group}
    {if $group['url']}
    <div class="seeall"><a href="{$group['url']}">{'SEE_ALL'|getLocalizedString:$group['count']}</a></div>
    {/if}
    {$resourcesListHeading=$group.title|default:''}
    {include file="findInclude:modules/courses/templates/include/resourcesList.tpl" resourcesListHeading=$resourcesListHeading resources=$group.items}
{/foreach}
{/block}
</div>
{else}
<p>{"NO_RESOURCES"|getLocalizedString}</p>
{/if}
{block name="resourcesFooter"}{/block}

{include file="findInclude:common/templates/footer.tpl"}
