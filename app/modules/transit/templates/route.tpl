{include file="findInclude:common/templates/header.tpl" scalable=false}

{$tabBodies = array()}

{capture name="mapPane" assign="mapPane"}
  {block name="mapPane"}
    <div id="map">
      <img src="{$mapImageSrc}" height="{$mapImageSize}" width="{$mapImageSize}" />
    </div>
  {/block}
{/capture}
{$tabBodies['map'] = $mapPane}

{capture name="stopsPane" assign="stopsPane"}
  {foreach $routeInfo['stops'] as $stopID => $stopInfo}
    {capture name="subtitle" assign="subtitle"}
      {include file="findInclude:modules/{$moduleID}/templates/include/predictions.tpl" predictions=$stopInfo['predictions']}
    {/capture}
    {if $subtitle}
      {$routeInfo['stops'][$stopID]['subtitle'] = $subtitle}
    {/if}
  {/foreach}

  {block name="stopsPane"}
    <span class="smallprint">{$routeConfig['stopTimeHelpText']}</span>
    <div id="schedule">
      {include file="findInclude:common/templates/results.tpl" results=$routeInfo['stops'] noResultsText="Stop information not available"}
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
      {if $routeConfig['serviceLogo']}
        <span id="servicelogo">
          {if $routeConfig['serviceLink']}<a href="{$routeConfig['serviceLink']}">{/if}
            <img src="/modules/{$moduleID}/images/{$routeConfig['serviceLogo']}{$serviceLogoExt|default:'.png'}" />
          {if $routeConfig['serviceLink']}</a>{/if}
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
        Refreshed at {$lastRefresh|date_format:"%l:%M"}<span class="ampm">{$lastRefresh|date_format:"%p"}</span>
        {if $routeConfig['serviceName']}&nbsp;using {$routeConfig['serviceName']}{/if}
      {else}
        Bus not running.
      {/if}
    {/block}
    {block name="autoReload"}
      <br/>Will refresh automatically in <span id="reloadCounter">{$autoReloadTime}</span> seconds
    {/block}  
  </p>
{block name="tabView"}
	  {include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies}
{/block}
</div>

{include file="findInclude:common/templates/footer.tpl"}
