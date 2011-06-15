<div id="helptext">Tap any pin to select it as the next stop</div>
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
