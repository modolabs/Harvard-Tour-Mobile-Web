{if $tappable|default: false}
  <div id="helptext">Tap any pin to select it as your starting point</div>
{/if}
</div>{* close container *}
<div id="map_container"{if $tappable|default: false}class="tappable"{/if}>
  <div id="map_canvas">
  </div>
  <div id="map_loading">
    <img src="/common/images/loading2.gif" />&nbsp;Loading map...
  </div>
</div>
<div>{* rest of container *}
