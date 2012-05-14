{block name="announcementsHeader"}{/block}
{block name="announcementsList"}
{if $announcementsLinks}
{include file="findInclude:modules/courses/templates/announcementsList.tpl" announcements=$announcementsLinks}
{else}
{"NO_ANNOUNCEMENTS"|getLocalizedString}
{/if}
{/block}
{block name="announcementsFooter"}{/block}