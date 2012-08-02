{include file="findInclude:common/templates/header.tpl"}

    {if $hasGrades}
        {include file="findInclude:modules/courses/templates/include/gradebook.tpl"}
    {else}
        {block name="noGradesText"}
            <p>{$noGradesText}</p>
        {/block}
    {/if}

{include file="findInclude:common/templates/footer.tpl"}
