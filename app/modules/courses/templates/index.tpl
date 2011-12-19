{include file="findInclude:common/templates/header.tpl"}

{if isset($description) && strlen($description)}
  <p class="{block name='headingClass'}nonfocal smallprint{/block}">
    {$description|escape}
  </p>
{/if}

{include file="findInclude:common/templates/search.tpl"}

{block name="navList"}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$course subTitleNewline=true}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
