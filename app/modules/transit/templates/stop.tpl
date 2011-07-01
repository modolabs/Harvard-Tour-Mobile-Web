{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2 class="refreshContainer">
    {block name="refreshButton"}
      <div id="refresh"><a href="{$refreshURL}">
        <img src="/common/images/refresh.png" alt="Update" width="82" height="32">
      </a></div>
    {/block}
    {$stopName}
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
    {block name="stopInfo"}
      Refreshed at {$lastRefresh|date_format:"%l:%M"}<span class="ampm">{$lastRefresh|date_format:"%p"}</span>
    {/block}
    {block name="autoReload"}
      <br/>Will refresh automatically in <span id="reloadCounter">{$autoReloadTime}</span> seconds
    {/block}
  </p>
</div>
<div id="map">
  <img src="{$mapImageSrc}" height="{$mapImageHeight}" width="{$mapImageWidth}" />
</div>

<h3 class="nonfocal">Currently serviced by:</h3>
  
{if count($runningRoutes)}  
  {foreach $runningRoutes as $i => $routeInfo}
    {capture name="subtitle" assign="subtitle"}
      {include file="findInclude:modules/{$moduleID}/templates/include/predictions.tpl" predictions=$routeInfo['predictions']}
    {/capture}
    {if $subtitle}
      {$runningRoutes[$i]['subtitle'] = $subtitle}
    {/if}
  {/foreach}

  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$runningRoutes accessKey=false subTitleNewline=true}
{else}
  <div class="focal">No routes currently servicing this stop</div>  
{/if}

{if count($offlineRoutes)}
  <h3 class="nonfocal">Services at other times by:</h3>
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$offlineRoutes accessKey=false}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
