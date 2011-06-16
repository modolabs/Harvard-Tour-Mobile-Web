<div id="helptext">
  {if $newTour}
    Tap any grey pin to choose a different starting point
  {else}
    Tap any grey pin to jump to another point in the tour
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
<div>{* rest of container *}
