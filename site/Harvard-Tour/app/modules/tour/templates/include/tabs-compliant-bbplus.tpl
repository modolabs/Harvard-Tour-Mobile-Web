{extends file="findExtends:modules/tour/templates/include/tabs.tpl"}

{block name="tab"}
  <li{if $tabKey == $tabbedView['current']} class="active"{/if}>
    <a href="javascript:void(0);" onclick="showTab('{$tabKey}Tab', this);{$tabInfo['javascript']}">
      <img src="/modules/tour/images/lens-{$tabKey}.png" alt="{$tabInfo['title']}" width="34" height="34" border="0" />
    </a>
  </li>
{/block}
