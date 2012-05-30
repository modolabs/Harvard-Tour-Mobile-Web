{block name="announcementsHeader"}{/block}
{if $announcementsLinks}
{include file="findInclude:modules/courses/templates/include/announcementsList.tpl" announcements=$announcementsLinks}
{else}
{"NO_ANNOUNCEMENTS"|getLocalizedString}
{/if}
{block name="announcementsFooter"}{/block}