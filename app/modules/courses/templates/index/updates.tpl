{block name="updatesHeader"}{/block}
{block name="updatesList"}
{if $updatesLinks}
{include file="findInclude:modules/courses/templates/include/updatesList.tpl" updates=$updatesLinks}
{else}
{"NO_UPDATES"|getLocalizedString}
{/if}
{/block}
{block name="updatesFooter"}{/block}