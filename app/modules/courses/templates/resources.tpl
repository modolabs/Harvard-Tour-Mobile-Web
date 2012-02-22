{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}

{if $contentTypes}
    <h2 class="nonfocal">{"CONTENT_TYPE_TITLE"|getLocalizedString}</h2>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$contentTypes} 
{/if}


{capture assign="tabBody"}
<ul class="tabstrip threetabs">
<li{if $group == 'topic'} class="active"{/if}><a href="{$groupLinks.topic}">By topic</a>
<li{if $group == 'date'} class="active"{/if}><a href="{$groupLinks.date}">By Date</a>
<li{if $group == 'type'} class="active"{/if}><a href="{$groupLinks.type}">By Type</a>
</ul>

{foreach $resources as $group}
    {if $group.title}<h3 class="nonfocal">{$group.title} {/if}
    {if $group.url}<a href="{$seeAllLinks.{$group.title}}">see all {count($group.items)}</a>{/if}</h3>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$group.items subTitleNewline=true}
{/foreach}
{/capture}
{include file="findInclude:modules/courses/templates/courseTabs.tpl" tabBody=$tabBody}

{include file="findInclude:common/templates/footer.tpl"}
