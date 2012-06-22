{include file="findInclude:common/templates/header.tpl"}

<h2 class="nonfocal">{$areaTitle}</h2>

{include file="findInclude:common/templates/search.tpl" extraArgs=$hiddenArgs}

{block name="areas"}
{if $areas}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$areas}
{/if}
{/block}

{block name="courses"}
{if $courses}
  {include file="findInclude:common/templates/results.tpl" boldLabels=true results=$courses}
{/if}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
