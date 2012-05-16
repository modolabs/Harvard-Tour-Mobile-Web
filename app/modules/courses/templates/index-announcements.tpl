{block name="announcementsHeader"}{/block}
{if $announcementsLinks}
{include file="findInclude:modules/courses/templates/announcementsList.tpl" announcements=$announcementsLinks}
{else}
{"NO_ANNOUNCEMENTS"|getLocalizedString}
{/if}
{block name="announcementsFooter"}{/block}