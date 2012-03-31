{include file="findInclude:common/templates/header.tpl"}

{block name="catalogHeader"}
{$catalogHeader}
{/block}
Term: <select id="termID" name="term" onchange="loadSection(this, '{$page}');">
    {foreach $terms as $term}
        {if $term['selected']}
            <option value="{$term['value']}" selected="true">{$term['title']|escape}</option>
        {else}
            <option value="{$term['value']}">{$term['title']|escape}</option>
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
