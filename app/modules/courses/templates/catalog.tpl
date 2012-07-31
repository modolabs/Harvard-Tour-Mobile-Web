{include file="findInclude:common/templates/header.tpl"}

{block name="catalogHeader"}
{$catalogHeader}
{/block}

{if !$showTermSelector && $termTitle}
	<div class="nonfocal"><h2>{$termTitle}</h2></div>
{/if}

{include file="findInclude:common/templates/search.tpl" extraArgs=$hiddenArgs}

{if $showTermSelector}
{block name="termselector"}
{include file="findInclude:modules/courses/templates/include/termselector.tpl"}
{/block}
{/if}

{if $bookmarksList}
{block name="bookmarksList"}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarksList}
{/block}
{/if}


{block name="areas"}
  {include file="findInclude:common/templates/navlist.tpl" boldLabels=true navlistItems=$areas}
{/block}

{block name="catalogFooter"}
{$catalogFooter}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
