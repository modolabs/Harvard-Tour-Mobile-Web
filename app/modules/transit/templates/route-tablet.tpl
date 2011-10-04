{extends file="findExtends:modules/transit/templates/route.tpl"}

{block name="headerServiceLogo"}
  {if $serviceInfo['id']}
    <span id="servicelogo">
      {if $serviceInfo['url']}<a href="{$serviceInfo['url']}">{/if}
        <img src="/modules/transit/images/{$serviceInfo['id']}{$imageExt}" />
      {if $serviceInfo['url']}</a>{/if}
    </span>
    <span id="serviceinfo" class="smallprint">
      {if $routeInfo['running']}
        Refreshed at <span id="lastrefreshtime">{$lastRefresh|date_format:"%l:%M"}<span class="ampm">{$lastRefresh|date_format:"%p"}</span></span>
        {if $serviceInfo['title']}&nbsp;using {$serviceInfo['title']|escape:'htmlall'}{/if}
      {else}
        Bus not running.
      {/if}
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
{/block}
{block name="tabView"}
  <div class="tabwrapper">
    {$smarty.block.parent}
  </div>
{/block}
