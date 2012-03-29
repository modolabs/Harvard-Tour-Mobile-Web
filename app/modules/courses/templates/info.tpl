{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}
<div class="bookmarkicon">
{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
</div>
{capture assign="tabBody"}
{if $location}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$location}
{/if}
{if $description}
{include file="findInclude:common/templates/navlist.tpl" navListHeading="Description" navlistItems=$description accessKey=false subTitleNewline=$contactsSubTitleNewline}
{/if}
{if $instructors}
{include file="findInclude:common/templates/navlist.tpl" navListHeading="Instructor(s)" navlistItems=$instructors accessKey=false subTitleNewline=$contactsSubTitleNewline}
{/if}

{if $links}
{include file="findInclude:common/templates/navlist.tpl" navListHeading="Links" navlistItems=$links subTitleNewline=true}
{/if}

{/capture}
{include file="findInclude:modules/courses/templates/courseTabs.tpl" tabBody=$tabBody}



{include file="findInclude:common/templates/footer.tpl"}
