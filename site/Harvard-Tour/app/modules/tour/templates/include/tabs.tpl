{extends file="findExtends:common/templates/tabs.tpl"}

{block name="tabsStart"}
  <div id="pagehead" class="tabbed">
    {$pageHeader}
    {$smarty.block.parent}
{/block}

{block name="tab"}
  <li{if $tabKey == $tabbedView['current']} class="active"{/if}>
    <a href="javascript:void(0);" onclick="showTab('{$tabKey}Tab', this);{$tabInfo['javascript']}">
      <img src="/modules/tour/images/lens-{$tabKey}@2x.png" alt="{$tabInfo['title']}" width="30" height="30" border="0" />
	  <br/>
	  {if $tabKey=="info"}Info
	  {else if $tabKey=="insideout"}Inside/Out
	  {else if $tabKey=="fastfacts"}Fast Facts
	  {else if $tabKey=="history"}History
	  {else if $tabKey=="innovation"}Innovation
	  {/if}
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
		  {if $tabKey=="insideout"}<h2 class="lensname">Inside/Out</h2>
		  {else if $tabKey=="fastfacts"}<h2 class="lensname">Fast Facts</h2>
		  {else if $tabKey=="history"}<h2 class="lensname">History</h2>
		  {else if $tabKey=="innovation"}<h2 class="lensname">Innovation</h2>
		  {/if}
          {$tabBody}
        </div>
      {/if}
    {/foreach}
  </div>
  <div class="clear"></div>
{/block}
