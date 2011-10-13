{extends file="findExtends:modules/transit/templates/include/stoplist.tpl"}

{block name="stopIconSuffix"}
  {$stopIcon = false}
{/block}

{block name="tableAttrs"} border="1" cellspacing="0" cellpadding="4"{/block}

{block name="flatList"}
  <table width="100%" id="listView">
    {foreach $routeInfo['stops'] as $routeID => $stop}
      <tr>
        <td width="18px" valign="middle">
          {if $stop['img']}
            <img src="{$stop['img']}" width="16" height="13" alt="{'CURRENT_STOP_ICON_ALT_TEXT'|getLocalizedString}" />
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
