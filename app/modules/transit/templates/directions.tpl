{include file="findInclude:common/templates/header.tpl" scalable=false}

<div class="nonfocal">
  <h2 class="refreshContainer">{$routeInfo['name']}</h2>
  {if count($directionList)}
    <div class="smallprint">Choose a {if $routeInfo['splitByHeadsign']}bus type{else}direction{/if}:</div>
  {/if}
</div>

{include file="findInclude:common/templates/results.tpl" results=$directionList noResultsText="Buses not running"}

{include file="findInclude:common/templates/footer.tpl"}
