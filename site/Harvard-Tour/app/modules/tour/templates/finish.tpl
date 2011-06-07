{include file="findInclude:common/templates/header.tpl"}

<div id="pagehead" class="brief">
  {include file="findInclude:modules/tour/templates/include/navHeader.tpl" navTitle="Thank You!" nextURL=$nextURL prevURL=$prevURL}
</div>
<div id="content">
  {include file="findInclude:modules/tour/templates/include/pagecontents.tpl" pageContents=$contents}
</div>

{include file="findInclude:common/templates/footer.tpl"}
