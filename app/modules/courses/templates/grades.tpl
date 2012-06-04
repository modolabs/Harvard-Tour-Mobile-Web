{block name="gradesHeader"}{/block}
{block name="gradesList"}
{if $gradesLinks}
{include file="findInclude:modules/courses/templates/include/updatesList.tpl" updates=$gradesLinks}
{else}
{"NO_UPDATES"|getLocalizedString}
{/if}
{/block}
{block name="gradesFooter"}{/block}