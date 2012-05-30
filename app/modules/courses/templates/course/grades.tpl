{block name="gradesHeader"}{/block}
{block name="gradesList"}
{if $contents}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$contents subTitleNewline=true}
{else}
{"NO_UPDATES"|getLocalizedString}
{/if}
{/block}
{block name="gradesFooter"}{/block}