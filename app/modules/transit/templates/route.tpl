{include file="findInclude:common/templates/header.tpl" scalable=false}

{$tabBodies = array()}

{capture name="mapPane" assign="mapPane"}
  {block name="mapPane"}
    {if $staticMap}
      <div id="map">
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
  {/block}
{/capture}
{$tabBodies['map'] = $mapPane}

{capture name="stopsPane" assign="stopsPane"}
  <span class="smallprint">{$routeConfig['stopTimeHelpText']}</span>
  {block name="stopsPane"}
    <div id="schedule">
      <div id="ajaxcontainer">
        {include file="findInclude:modules/transit/templates/include/stoplist.tpl"}
      </div>
    </div>
  {/block}
{/capture}
{$tabBodies['stops'] = $stopsPane}

<a name="scrolldown"></a>
<div class="focal shaded">
  <h2 class="refreshContainer">
    {block name="refreshButton"}
      <div id="refresh"><a href="{$refreshURL}">
        <img src="/common/images/refresh.png" alt="Update" width="82" height="32">
      </a></div>
    {/block}
    {$routeInfo['name']}
  </h2>
  
  <p class="smallprint logoContainer clear">
    {block name="headerServiceLogo"}
      {if $serviceInfo['id']}
        <span id="servicelogo">
          {if $serviceInfo['url']}<a href="{$serviceInfo['url']}">{/if}
            <img src="/modules/transit/images/{$serviceInfo['id']}{$imageExt}" />
          {if $serviceInfo['url']}</a>{/if}
        </span>
      {/if}
    {/block}
    {block name="routeInfo"}
      {if $routeInfo['description']}
        {$routeInfo['description']}<br/>
      {/if}
      {if $routeInfo['summary']}
        {$routeInfo['summary']}<br/>
      {/if}
      {if $routeInfo['running']}
        Refreshed at <span id="lastrefreshtime">{$lastRefresh|date_format:"%l:%M"}<span class="ampm">{$lastRefresh|date_format:"%p"}</span></span>
        {if $serviceInfo['title']}&nbsp;using {$serviceInfo['title']|escape:'htmlall'}{/if}
      {else}
        Bus not running.
      {/if}
    {/block}
    {block name="autoReload"}
      {if $autoReloadTime}
        <br/>Will refresh automatically in <span id="reloadCounter">{$autoReloadTime}</span> seconds
      {/if}
    {/block}  
  </p>
{block name="tabView"}
	  {include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies}
{/block}
</div>

{include file="findInclude:common/templates/footer.tpl"}
