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
<a>By topic</a>&nbsp;&nbsp;&nbsp;
<a>By Date</a>
</div>
<div id="tabbodies">
<div class="tab body">
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$contents subTitleNewline=true}
</div>
</div>
{include file="findInclude:common/templates/footer.tpl"}
