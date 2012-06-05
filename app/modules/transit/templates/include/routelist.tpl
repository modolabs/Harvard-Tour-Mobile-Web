  <h3 class="nonfocal">{"STOP_ROUTES_HEADING_CURRENT"|getLocalizedString}</h3>
  {if count($runningRoutes)}  
    {foreach $runningRoutes as $i => $routeInfo}
      {capture name="subtitle" assign="subtitle"}
        {include file="findInclude:modules/{$moduleID}/templates/include/predictions.tpl" predictions=$routeInfo['predictions']}
      {/capture}
      {if trim($subtitle)}
        {$runningRoutes[$i]['subtitle'] = $subtitle}
      {/if}
    {/foreach}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$runningRoutes accessKey=false subTitleNewline=true}
  {else}
    <div class="focal">{"STOP_ROUTES_HEADING_NO_CURRENT"|getLocalizedString}</div>  
  {/if}
  
  {if count($offlineRoutes)}
    <h3 class="nonfocal">{"STOP_ROUTES_HEADING_OTHER"|getLocalizedString}</h3>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$offlineRoutes accessKey=false}
  {/if}
