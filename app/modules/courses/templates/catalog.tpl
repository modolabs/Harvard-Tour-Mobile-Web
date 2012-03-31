{include file="findInclude:common/templates/header.tpl"}

{block name="catalogHeader"}
{$catalogHeader}
{/block}
Term: <select id="termID" name="term" onchange="loadSection(this, '{$page}');">
    {foreach $sections as $section}
        {if $section['selected']}
            <option value="{$section['value']}" selected="true">{$section['title']|escape}</option>
        {else}
            <option value="{$section['value']}">{$section['title']|escape}</option>
        {/if}
    {/foreach}
</select>

{block name="navList"}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$areas}
{/block}

{block name="catalogFooter"}
{$catalogFooter}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
