{include file="findInclude:common/templates/header.tpl" scalable=false}

{$tabBodies = array()}

{if $hasRouteMap}
  {capture name="mapPane" assign="mapPane"}
    {block name="mapPane"}
      {include file="findInclude:modules/transit/templates/include/map.tpl"}
    {/block}
  {/capture}
  {$tabBodies['map'] = $mapPane}
{/if}

{capture name="stopsPane" assign="stopsPane"}
  {if $routeInfo['view'] == 'schedule'}
    {if $scheduleHelpText}
      <span class="smallprint">{$scheduleHelpText}</span>
    {/if}
  {else}
    {$stopTimeHelpText = "STOP_TIME_HELP_TEXT"|getLocalizedString}
    {if $stopTimeHelpText}
      <span class="smallprint">{$stopTimeHelpText}</span>
    {/if}
  {/if}
  {block name="stopsPane"}
    <div id="stops">
      <div id="ajaxcontainer">
        {include file="findInclude:modules/transit/templates/include/stoplist.tpl"}
      </div>
    </div>
  {/block}
{/capture}
{$tabBodies['stops'] = $stopsPane}

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
  {if $routeInfo['running']}
    <span id="serviceinfo" class="smallprint">
      {capture name="lastRefreshTime" assign="lastRefreshTime"}
        <span id="lastrefreshtime">{$lastRefresh|date_format:"%l:%M"}<span class="ampm">{$lastRefresh|date_format:"%p"}</span></span> 
      {/capture}
      {if $serviceInfo['title']}
        {$lastRefreshService = $serviceInfo['title']|escape:'htmlall'}
        {"LAST_REFRESH_WITH_SERVICE"|getLocalizedString:$lastRefreshTime:$lastRefreshService}
      {else}
        {"LAST_REFRESH"|getLocalizedString:$lastRefreshTime}
      {/if}
    </span>
  {else}
    {"NOT_RUNNING_TEXT"|getLocalizedString}
  {/if}
{/capture}

<a name="scrolldown"></a>
<div class="nonfocal">
  {block name="headerServiceLogo"}
    {$serviceLogo}
  {/block}
  {block name="routeName"}
    <h2 class="nameContainer">
      {$routeInfo['name']}
      {if isset($routeInfo['directions']) && count($routeInfo['directions']) > 1}
        <br /><span class="direction">{$routeInfo['directions'][$direction]['name']}</span>
      {/if}
    </h2>
  {/block}  
  <p class="smallprint clear">
    {block name="routeInfo"}
      {if $routeInfo['description']}
        {$routeInfo['description']}<br/>
      {/if}
      {if $routeInfo['summary']}
        {$routeInfo['summary']}<br/>
      {/if}
    {/block}
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
{block name="tabView"}
    {if count($tabBodies) > 1}
	    {include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies}
    {elseif count($tabBodies)}
      {reset($tabBodies)}
    {/if}
{/block}
</div>

{include file="findInclude:common/templates/footer.tpl"}
