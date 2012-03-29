{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}

{capture assign="tabBody"}
<ul class="tabstrip threetabs">
{foreach $groupLinks as $index => $groupLink}
<li{if $group == $index} class="active"{/if}><a href="{$groupLink.url}">By {$groupLink.title}</a>
{/foreach}
</ul>

{foreach $resources as $group}
    {$navListHeading=$group.title|default:''}
    {include file="findInclude:common/templates/navlist.tpl" navListHeading=$navListHeading navlistItems=$group.items subTitleNewline=true}
{/foreach}
{/capture}
{include file="findInclude:modules/courses/templates/courseTabs.tpl" tabBody=$tabBody}

{include file="findInclude:common/templates/footer.tpl"}
