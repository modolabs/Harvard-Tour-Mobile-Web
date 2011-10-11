{include file="findInclude:common/templates/header.tpl" scalable=false}

{$tabBodies = array()}

{if $hasRouteMap}
  {capture name="mapPane" assign="mapPane"}
    {block name="mapPane"}
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
    {/block}
  {/capture}
  {$tabBodies['map'] = $mapPane}
{/if}

{capture name="stopsPane" assign="stopsPane"}
  {if isset($routeInfo['directions']) && $routeInfo['directions']}
    {if $scheduleHelpText}
      <span class="smallprint">{$scheduleHelpText}</span>
    {/if}
  {else}
    {if $stopTimeHelpText}
      <span class="smallprint">{$stopTimeHelpText}</span>
    {/if}
  {/if}
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
  {block name="headerServiceLogo"}
    {if $serviceInfo['id']}
      <span id="servicelogo">
        {if $serviceInfo['url']}<a href="{$serviceInfo['url']}">{/if}
          <img src="/modules/transit/images/{$serviceInfo['id']}{$imageExt}" />
        {if $serviceInfo['url']}</a>{/if}
      </span>
    {/if}
  {/block}
  <h2 class="nameContainer">
    {$routeInfo['name']}
    {if isset($routeInfo['directions']) && count($routeInfo['directions']) > 1}
      <br /><span class="direction">{$routeInfo['directions'][$direction]['name']}</span>
    {/if}
  </h2>
  
  <p class="smallprint logoContainer clear">
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
    {if count($tabBodies) == 1}
      <div id="notabs">
    {/if}
	      {include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies}
    {if count($tabBodies) == 1}
      </div>
    {/if}
{/block}
</div>

{include file="findInclude:common/templates/footer.tpl"}
