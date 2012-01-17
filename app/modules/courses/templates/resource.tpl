{include file="findInclude:common/templates/header.tpl"}

<h2>{$title}</h2>

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
<a href="{$linkByTopic}">By topic</a>&nbsp;&nbsp;&nbsp;
<a href="{$linkByDate}">By Date</a>
</div>
  {foreach $resources as $itemname =>$item}
	{if $itemname}<h3>{$itemname}</h3>{/if}
	<div style=""><a href="{$seeAllLinks["$itemname"]}">see all {count($item)}</a>
	</div>
<div style = "margin-left:15px;" id="tabbodies">
<div class="tab body">
		{include file="findInclude:common/templates/navlist.tpl" navlistItems=$item}
</div>
</div>
	<br/>
  {/foreach}
{include file="findInclude:common/templates/footer.tpl"}
