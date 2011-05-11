{extends file="findExtends:common/templates/header.tpl"}

{block name="navbar"}
  <div id="navbar"{if $hasHelp} class="helpon"{/if}>
    <div class="breadcrumbs{if $isModuleHome} homepage{/if}">
      <a name="top" href="/" class="homelink">
        <img src="/common/images/homelink.png" width="57" height="45" alt="Home" />
      </a>
      
      {$breadcrumbHTML}
      <span class="pagetitle">
        {$pageTitle}
      </span>
    </div>
    {if $hasHelp}
      <div class="help">
        <a href="help.php"><img src="/common/images/help.png" width="42" height="45" alt="Help" /></a>
      </div>
    {/if}
  </div>
{/block}
