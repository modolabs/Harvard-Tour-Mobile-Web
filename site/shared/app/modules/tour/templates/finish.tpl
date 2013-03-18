{include file="findInclude:common/templates/header.tpl"}

<div id="pagehead" class="brief">
  {include file="findInclude:modules/tour/templates/include/navheader.tpl" navTitle="Thank You!" nextURL=$nextURL prevURL=$prevURL}
</div>
<div id="content">
  {if count($contents) > 1}
    {$before = array_slice($contents, 0, 1)}
    {$after = array_slice($contents, 1)}
    {include file="findInclude:modules/tour/templates/include/pagecontents.tpl" pageContents=$before}
    <ul class="nav">
      <li><a href="{$startOverURL}" onclick="return confirm('Are you sure you want to restart the tour?');">Take the tour again</a></li>
    </ul>
    {include file="findInclude:modules/tour/templates/include/pagecontents.tpl" pageContents=$after}
  {else}
    {include file="findInclude:modules/tour/templates/include/pagecontents.tpl" pageContents=$contents}
  {/if}
</div>

{include file="findInclude:common/templates/footer.tpl"}
