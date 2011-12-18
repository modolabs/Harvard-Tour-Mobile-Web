{include file="findInclude:common/templates/header.tpl"}

{if isset($description) && strlen($description)}
  <p class="{block name='headingClass'}nonfocal smallprint{/block}">
    {$description|escape}
  </p>
{/if}

{include file="findInclude:common/templates/search.tpl"}

{if $areas}
{block name="areas"}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$areas}
{/block}
{/if}

{if $courses}
{block name="courses"}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$courses}
{/block}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
