{include file="findInclude:common/templates/header.tpl"}

{block name="announcementsHeader"}{/block}
{block name="announcementsList"}
{if $announcementsLinks}
{include file="findInclude:modules/courses/templates/include/announcementsList.tpl" announcements=$announcementsLinks}
{else}
{"NO_ANNOUNCEMENTS"|getLocalizedString}
{/if}
{/block}
{block name="announcementsFooter"}{/block}

{include file="findInclude:common/templates/footer.tpl"}
