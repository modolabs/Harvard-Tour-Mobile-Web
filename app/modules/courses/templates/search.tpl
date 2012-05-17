{include file="findInclude:common/templates/header.tpl"}

<h2 class="nonfocal">{$searchHeader}</h2>

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

{if $courses}
{block name="courses"}
  {include file="findInclude:common/templates/navlist.tpl" boldLabels=true navlistItems=$courses}
{/block}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
