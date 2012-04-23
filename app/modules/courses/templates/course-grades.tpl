{if $contents}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$contents subTitleNewline=true}
{else}
{"NO_UPDATES"|getLocalizedString}
{/if}