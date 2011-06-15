{extends file="findExtends:common/templates/tabs.tpl"}

{block name="tabsStart"}
  <div id="pagehead">
    {$pageHeader}
    {$smarty.block.parent}
{/block}

{block name="tab"}
  <li{if $tabKey == $tabbedView['current']} class="active"{/if}>
    <a href="javascript:void(0);" onclick="showTab('{$tabKey}Tab', this);{$tabInfo['javascript']}">
      <img src="/modules/tour/images/lens-{$tabKey}@2x.png" alt="{$tabInfo['title']}" width="34" height="34" border="0" />
    </a>
  </li>
{/block}

{block name="tabsEnd"}
    {$smarty.block.parent}
  </div>{* pagehead *}
{/block}

{block name="tabBodies"}
  {if count($tabBodies) <= 1}
    <div id="pagehead" class="brief">{$pageHeader}</div>
  {/if}
  <div id="tabbodies">
    {foreach $tabBodies as $tabKey => $tabBody}
      {if isset($tabbedView['tabs'][$tabKey])}
        <div class="tabbody" id="{$tabKey}Tab">
          {$tabBody}
        </div>
      {/if}
    {/foreach}
  </div>
  <div class="clear"></div>
{/block}
