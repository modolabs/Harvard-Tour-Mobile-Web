{extends file="findExtends:modules/{$moduleID}/templates/route.tpl"}

{block name="mapPane"}
  <p class="image">
    <img src="{$mapImageSrc}" height="{$mapImageHeight}" width="{$mapImageWidth}" />
  {if $serviceInfo['id']}
    <table align="center">
      <tr>
        <td valign="middle">
          {if $serviceInfo['url'] && !$serviceInfo['title']}<a href="{$serviceInfo['url']}">{/if}
            <img src="/modules/transit/images/{$serviceInfo['id']}{$imageExt}" />
          {if $serviceInfo['url'] && !$serviceInfo['title']}</a>{/if}
        </td>
        {if $serviceInfo['title']}
          <td valign="middle">
            {if $serviceInfo['url']}<a href="{$serviceInfo['url']}">{/if}
              {$serviceInfo['title']|escape:'htmlall'}
            {if $serviceInfo['url']}</a>{/if}
          </td>
        {/if}
      </tr>
    </table>
  {/if}
{/block}

{block name="stopsPane"}
  <table width="100%" id="schedule">
    {foreach $routeInfo['stops'] as $routeID => $stop}
      <tr>
        <td width="18px" valign="middle">
          {if $stop['img']}
            <img src="{$stop['img']}" width="16" height="13" alt="Bus arriving next at this stop" />
          {/if}
        </td>
        <td valign="middle"{if $stop['upcoming']} class="current"{/if}>
          <a href="{$stop['url']}">
            {$stop['title']}
          </a>
        </td>
      </tr>
    {/foreach}
  </table>
{/block}

{block name="refreshButton"}
{/block}

{block name="headerServiceLogo"}
{/block}

{block name="routeInfo"}
  {$smarty.block.parent}
  (<a href="{$refreshURL}">refresh</a>)
{/block}

{block name="autoReload"}
{/block}

{block name="tabView"}
    {$tabBodies['stops']}
    {$tabBodies['map']}
{/block}
