{include file="findInclude:common/templates/header.tpl"}

<div id="pagehead" class="brief">
  <div id="pagetitle" class="overview"><h1>Tour Help</h1></div>
  <div id="viewtoggle">
    <a href="{$doneURL}">return</a>
  </div>
</div>
<div id="content">
  {include file="findInclude:modules/tour/templates/include/pagecontents.tpl" pageContents=$contents}
</div>

{include file="findInclude:common/templates/footer.tpl"}
