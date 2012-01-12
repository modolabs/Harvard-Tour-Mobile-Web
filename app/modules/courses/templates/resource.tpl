{include file="findInclude:common/templates/header.tpl"}

{if $contentTypes}
    <h2 class="nonfocal">{"CONTENT_TYPE_TITLE"|getLocalizedString}</h2>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$contentTypes} 
{/if}
<ul id="tabs">
<li> <a href="{$linkToUpdateTab}"> Updates</a></li>
<li class="active"> <a href="{$linkToResourcesTab}">Resources</a></li>
</ul>
<div style="padding:5px;">
<br/>
<br/>
<a <a href="{$linkByTopic}">By topic</a>&nbsp;&nbsp;&nbsp;
<a <a href="{$linkByDate}">By Date</a>
</div>
<div style = "margin-left:15px;" id="tabbodies">
  {foreach $resources as $itemname =>$item}
	{if $itemname}<h3>{$itemname}</h3> <div style=""><a href="{$seeAllLinks["$itemname"]}">see all {count($item)}</a></div>{/if}
<div class="tab body">
		{include file="findInclude:common/templates/navlist.tpl" navlistItems=$item}
</div>
	<br/>
  {/foreach}
</div>
{include file="findInclude:common/templates/footer.tpl"}
