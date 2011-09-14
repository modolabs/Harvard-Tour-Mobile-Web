{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  {block name="headerServiceLogo"}
    {if $serviceInfo['id']}
      <span id="servicelogo">
        {if $serviceInfo['url']}<a href="{$serviceInfo['url']}">{/if}
          <img src="/modules/transit/images/{$serviceInfo['id']}{$imageExt}" />
        {if $serviceInfo['url']}</a>{/if}
      </span>
    {/if}
  {/block}
  <h2 class="nameContainer">{$stopName}</h2>
  <p class="smallprint logoContainer clear">
    {block name="stopInfo"}
      Refreshed at <span id="lastrefreshtime">{$lastRefresh|date_format:"%l:%M"}<span class="ampm">{$lastRefresh|date_format:"%p"}</span></span>
    {/block}
    {block name="autoReload"}
      {if $autoReloadTime}
        <br/>Will refresh automatically in <span id="reloadCounter">{$autoReloadTime}</span> seconds
      {/if}
    {/block}
  </p>
</div>
<div id="stopinfo">
  <div id="mapcontainer">
    {if $staticMap}
      <div id="map_static">
        <img src="{$mapImageSrc}" height="{$mapImageHeight}" width="{$mapImageWidth}" />
      </div>
    {else}
      <div id="map_dynamic">
        <div id="map_canvas">
        </div>
        <div id="map_loading">
          <img src="/common/images/loading2.gif" />&nbsp;Loading map...
        </div>
      </div>
    {/if}
  </div>
  
  <div id="ajaxcontainer">
    {include file="findInclude:modules/transit/templates/include/routelist.tpl"}
  </div>
</div>

{include file="findInclude:common/templates/footer.tpl"}
