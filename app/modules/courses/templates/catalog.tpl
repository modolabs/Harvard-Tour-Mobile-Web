{include file="findInclude:common/templates/header.tpl"}

{block name="catalogHeader"}
{$catalogHeader}
{/block}
{if $sections}

{include file="findInclude:common/templates/search.tpl" extraArgs=$hiddenArgs}

<div id="category-switcher">
Term: <select id="termID" name="term" onchange="loadSection(this, '{$page}');">
    {foreach $sections as $section}
        {if $section['selected']}
            <option value="{$section['value']}" selected="true">{$section['title']|escape}</option>
        {else}
            <option value="{$section['value']}">{$section['title']|escape}</option>
        {/if}
    {/foreach}
</select>
</div>
{/if}

{if $bookmarksList}
{block name="bookmarksList"}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarksList}
{/block}
{/if}


{block name="areas"}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$areas}
{/block}

{block name="catalogFooter"}
{$catalogFooter}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
