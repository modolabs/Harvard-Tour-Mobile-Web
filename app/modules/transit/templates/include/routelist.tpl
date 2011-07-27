  <h3 class="nonfocal">Currently serviced by:</h3>
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
    <div class="focal">No routes currently servicing this stop</div>  
  {/if}
  
  {if count($offlineRoutes)}
    <h3 class="nonfocal">Serviced at other times by:</h3>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$offlineRoutes accessKey=false}
  {/if}
