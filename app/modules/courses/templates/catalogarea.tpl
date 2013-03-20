{include file="findInclude:common/templates/header.tpl"}

{if $areaTitle}
<div class="nonfocal"><h2>{$areaTitle}{if $termTitle}: {$termTitle}{/if}</h2></div>
{/if}

{include file="findInclude:common/templates/search.tpl" extraArgs=$hiddenArgs}

{block name="areas"}
{if $areas}
  {include file="findInclude:common/templates/navlist.tpl" boldLabels=true navlistItems=$areas}
{/if}
{/block}

{block name="courses"}
{if $courses}
  {include file="findInclude:common/templates/results.tpl" boldLabels=true results=$courses}
{/if}
{/block}

{if !$courses && !$areas}
{block name="no_content"}
<div class="focal">{"NO_CATALOG_DATA"|getLocalizedString}</div>
{/block}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
