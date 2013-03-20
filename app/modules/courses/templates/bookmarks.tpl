{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>{$bookmarkItemTitle}</h2>
</div>
{if $navItems}
  {include file="findInclude:common/templates/navlist.tpl" boldLabels=true navlistItems=$navItems}
{else}
  {block name="noResults"}
    <ul class="nav">
      <li>{"NO_RESULTS"|getLocalizedString}</li>
    </ul>
  {/block}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
