{extends file="findExtends:common/templates/header.tpl"}

{block name="viewportHeadTag"}
  {$scalable = false}
  {$smarty.block.parent}
{/block}

{block name="navbar"}
  <div id="navbar"{if $hasHelp} class="helpon"{/if}>
    <a id="homelink" href="/">
      <img src="/common/images/homelink@2x.png" width="28" height="33" border="0" alt="" />
      Tour of the Heights
    </a>
		<ul id="helplinks">
      {if $tourMapLink}
			  <li><a href="{$tourMapLink}">map</a></li>
      {/if}
      {if $tourHelpLink}
			  <li><a href="{$tourHelpLink}">help</a></li>
			{/if}
		</ul>
  </div>
{/block}
