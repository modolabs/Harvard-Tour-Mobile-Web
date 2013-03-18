<div id="helptext">
  {if $staticMap}
    {if $newTour}
      Switch to <a href="{$listViewURL}">list view</a> to choose a different starting point
    {elseif $view == 'overview'}
      Switch to <a href="{$listViewURL}">list view</a> to jump to another point in the tour
    {else}
      To jump elsewhere in the tour, use the <a href="{$listViewURL}">tour overview list</a>
    {/if}
  {else}
    {if $newTour}
      Tap any grey pin to choose a different starting point
    {else}
      Tap any grey pin to jump to another point in the tour
    {/if}
  {/if}
</div>
</div>{* close container *}
{if $staticMap}
  <div id="static_map_container">
    <img id="static_map" src="{$staticMap}" />
  </div>
{else}
  <div id="map_container">
    <div id="map_canvas">
    </div>
    <div id="map_loading">
      <img src="/common/images/loading2.gif" />&nbsp;Loading map...
    </div>
  </div>
{/if}
<div style="display: none">{* rest of container *}
