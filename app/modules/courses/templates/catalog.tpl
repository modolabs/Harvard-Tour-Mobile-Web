{include file="findInclude:common/templates/header.tpl"}

{block name="catalogHeader"}
{$catalogHeader}
{/block}

{block name="navList"}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$areas}
{/block}

{block name="catalogFooter"}
{$catalogFooter}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
