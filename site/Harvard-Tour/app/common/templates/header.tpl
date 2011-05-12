{extends file="findExtends:common/templates/header.tpl"}

{block name="navbar"}
  <a name="top"> </a>
  <div id="navbar"{if $hasHelp} class="helpon"{/if}>
    <a id="homelink" href="/">
      <img src="/common/images/homelink@2x.png" width="27" height="33" border="0" alt="" />
      harvard yard tour
    </a>
		<ul id="helplinks">
      {if $showHelpLink}
			  <li><a href="mockup-map.html">map</a></li>
      {/if}
      {if $showMapLink}
			  <li><a href="mockup-help.html">help</a></li>
			{/if}
		</ul>
  </div>
{/block}
