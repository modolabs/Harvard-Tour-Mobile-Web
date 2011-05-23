{extends file="findExtends:common/templates/header.tpl"}

{block name="viewportHeadTag"}
  {$scalable = false}
  {$smarty.block.parent}
{/block}

{block name="navbar"}
  <div id="navbar"{if $hasHelp} class="helpon"{/if}>
    <a id="homelink" href="/">
      <img src="/common/images/homelink@2x.png" width="27" height="33" border="0" alt="" />
      Harvard Yard Tour
    </a>
		<ul id="helplinks">
      {if $mapLink}
			  <li><a href="{$mapLink}">map</a></li>
      {/if}
      {if $helpLink}
			  <li><a href="{$helpLink}">help</a></li>
			{/if}
		</ul>
  </div>
{/block}
