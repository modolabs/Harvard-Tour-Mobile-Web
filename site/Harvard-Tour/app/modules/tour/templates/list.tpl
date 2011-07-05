{include file="findInclude:common/templates/header.tpl"}

<div id="pagehead" class="brief">
  <div id="pagetitle" class="overview"><h1>{if $newTour}Starting Point{else}Tour Overview{/if}</h1></div>
  <div id="viewtoggle">
    <a class="active" href="{$mapViewURL}">map</a>
    <span class="spacer">|</span>
    <span>list</span>
    {if !$newTour}<a id="doneURL" class="active" href="{$doneURL}">return</a>{/if}
  </div>
</div>

<div id="content">
  {include file="findInclude:modules/tour/templates/include/list.tpl" stops=$stops}
</div>

{include file="findInclude:common/templates/footer.tpl"}
