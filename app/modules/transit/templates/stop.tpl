{include file="findInclude:common/templates/header.tpl"}

{capture name="serviceLogo" assign="serviceLogo"}
  {if $serviceInfo['id']}
    <span id="servicelogo">
      {if $serviceInfo['url']}<a href="{$serviceInfo['url']}">{/if}
        <img src="/modules/transit/images/{$serviceInfo['id']}{$imageExt}" />
      {if $serviceInfo['url']}</a>{/if}
    </span>
  {/if}
{/capture}
{capture name="serviceInfo" assign="serviceInfo"}
  <span id="serviceinfo" class="smallprint">
    {capture name="lastRefreshTime" assign="lastRefreshTime"}
      <span id="lastrefreshtime">{$lastRefresh|date_format:"%l:%M"}<span class="ampm">{$lastRefresh|date_format:"%p"}</span></span> 
    {/capture}
    {"LAST_REFRESH"|getLocalizedString:$lastRefreshTime}
  </span>
{/capture}

<div class="nonfocal">
  {block name="headerServiceLogo"}
    {$serviceLogo}
  {/block}
  <h2 class="nameContainer">{$stopName}</h2>
  <p class="smallprint logoContainer clear">
    {block name="headerServiceInfo"}
      {$serviceInfo}
    {/block}
    {block name="autoReload"}
      {if $autoReloadTime}
        {capture assign="autoReloadTimeString" name="autoReloadTimeString"}
          <span id="reloadCounter">{$autoReloadTime}</span>
        {/capture}
        <br/>{"AUTO_RELOAD_MESSAGE"|getLocalizedString:$autoReloadTimeString}
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
