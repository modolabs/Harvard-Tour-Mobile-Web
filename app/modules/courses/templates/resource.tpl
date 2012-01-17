{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}

{if $contentTypes}
    <h2 class="nonfocal">{"CONTENT_TYPE_TITLE"|getLocalizedString}</h2>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$contentTypes} 
{/if}

<ul id="tabs" class="nonfocal">
    <li><a href="{$linkToUpdateTab}"> Updates</a></li>
    <li class="active"><a href="{$linkToResourcesTab}">Resources</a></li>
</ul>

<div id="tabbodies">
<div class="tab body">
<ul class="tabstrip twotabs">
<li{if $type == 'topic'} class="active"{/if}><a href="{$linkByTopic}">By topic</a>
<li{if $type == 'date'} class="active"{/if}><a href="{$linkByDate}">By Date</a>
</ul>
</div>
</div>

{foreach $resources as $itemname =>$item}
    {if $itemname}<h3 class="nonfocal">{$itemname} {/if}<a href="{$seeAllLinks["$itemname"]}">see all {count($item)}</a></h3>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$item}
    <br />
{/foreach}

{include file="findInclude:common/templates/footer.tpl"}
