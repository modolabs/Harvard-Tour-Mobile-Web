{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}

{if $contentTypes}
    <h2 class="nonfocal">{"CONTENT_TYPE_TITLE"|getLocalizedString}</h2>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$contentTypes} 
{/if}


{capture assign="tabBody"}
<ul class="tabstrip threetabs">
{foreach $groupLinks as $index => $groupLink}
<li{if $group == $index} class="active"{/if}><a href="{$groupLink.url}">By {$groupLink.title}</a>
{/foreach}
</ul>

{foreach $resources as $group}
    <h3 class="nonfocal">{if $group.title}{$group.title} {/if}
    {if $group.url}<a href="{$group.url}">{"SEE_ALL"|getLocalizedString:$group.count}</a>{/if}</h3>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$group.items subTitleNewline=true}
{/foreach}
{/capture}
{include file="findInclude:modules/courses/templates/courseTabs.tpl" tabBody=$tabBody}

{include file="findInclude:common/templates/footer.tpl"}
