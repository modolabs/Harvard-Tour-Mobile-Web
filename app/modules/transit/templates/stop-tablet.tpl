{extends file="findExtends:modules/transit/templates/stop.tpl"}

{block name="headerServiceLogo"}
  {if $serviceInfo['id']}
    <span id="servicelogo">
      {if $serviceInfo['url']}<a href="{$serviceInfo['url']}">{/if}
        <img src="/modules/transit/images/{$serviceInfo['id']}{$imageExt}" />
      {if $serviceInfo['url']}</a>{/if}
    </span>
    <span id="serviceinfo" class="smallprint">
      {if count($runningRoutes)}
        Refreshed at <span id="lastrefreshtime">{$lastRefresh|date_format:"%l:%M"}<span class="ampm">{$lastRefresh|date_format:"%p"}</span></span>
        {if $serviceInfo['title']}&nbsp;using {$serviceInfo['title']|escape:'htmlall'}{/if}
      {else}
        Bus not running.
      {/if}
    </span>
  {/if}
{/block}

{block name="stopInfo"}
{/block}
