{block name="updatesHeader"}{/block}
{block name="updatesList"}{/block}
{if $updatesLinks}
{include file="findInclude:modules/courses/templates/updatesList.tpl" updates=$updatesLinks}
{else}
{"NO_UPDATES"|getLocalizedString}
{/if}
{block name="updatesFooter"}{/block}