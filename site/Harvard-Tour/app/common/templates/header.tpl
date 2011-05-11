{extends file="findExtends:common/templates/header.tpl"}

{block name="navbar"}
  <div id="navbar"{if $hasHelp} class="helpon"{/if}>
    <div class="breadcrumbs homepage">
      <a name="top" href="/">
        <img src="/common/images/title-{$navImageID|default:$configModule}.png" width="28" height="28" alt="" class="moduleicon" />
      </a>
      <span class="pagetitle">
        {$pageTitle}
      </span>
    </div>
    {if $hasHelp}
      <div class="help">
        <a href="help.php"><img src="/common/images/help.png" width="42" height="45" alt="Help" /></a>
      </div>
    {/if}
    {if $hasMap}
      <div class="map">
        <a href="map.php"><img src="/common/images/map.png" width="42" height="45" alt="Map" /></a>
      </div>
    {/if}
  </div>
{/block}
