{include file="findInclude:common/templates/header.tpl"}

{if $contentTypes}
    <h2 class="nonfocal">{"CONTENT_TYPE_TITLE"|getLocalizedString}</h2>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$contentTypes} 
{/if}
<ul id="tabs">
<li class="active"> <a href="{$linkToOtherTab}"> Updates</a></li>
<li> <a href="{$linkToOtherTab}">Resources</a></li>
</ul>
<div id="tabbodies">
<div class="tab body">
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$contents subTitleNewline=true}
</div>
</div>
{include file="findInclude:common/templates/footer.tpl"}
