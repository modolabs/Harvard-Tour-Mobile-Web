{extends file="findExtends:common/templates/header-touch.tpl"}

{block name="breadcrumbs"}
{/block}

{block name="navbar"}
  <div id="navbar"{if $hasHelp} class="helpon"{/if}>
    <div class="breadcrumbs{if $isModuleHome} homepage{/if}">
      <a name="top" href="/" class="homelink">
        <img src="/common/images/homelink.gif" width="40" height="30" alt="Home" />
      </a>
      <span class="pagetitle">
        {$pageTitle}
      </span>
    </div>
  </div>
{/block}
