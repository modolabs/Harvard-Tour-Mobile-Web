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

{block name="headerServiceLogo"}
{/block}

{block name="headerServiceInfo"}
  {$smarty.block.parent}
  &nbsp;(<a href="{$refreshURL}">refresh</a>)
{/block}

{block name="autoReload"}
{/block}

{block name="tabView"}
    {$tabBodies['stops']}
    {$tabBodies['map']}
{/block}
