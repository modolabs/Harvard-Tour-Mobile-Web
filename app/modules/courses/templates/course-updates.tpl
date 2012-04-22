{if $updatesLinks}
{include file="findInclude:modules/courses/templates/updatesList.tpl" updates=$updatesLinks}
{else}
{"NO_UPDATES"|getLocalizedString}
{/if}
