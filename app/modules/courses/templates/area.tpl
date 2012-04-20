{include file="findInclude:common/templates/header.tpl"}
<h2 class="nonfocal">{$areaTitle}</h2>
{if $areas}
{block name="areas"}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$areas}
{/block}
{/if}

{if $courses}
{block name="courses"}
  {include file="findInclude:common/templates/navlist.tpl" boldLabels=true navlistItems=$courses}
{/block}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
