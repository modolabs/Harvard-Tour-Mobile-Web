{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}

{if $contentTypes}
    <h2 class="nonfocal">{"CONTENT_TYPE_TITLE"|getLocalizedString}</h2>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$contentTypes} 
{/if}

<div id="tabscontainer">
    <ul id="tabs" class="smalltabs">
        <li class="active"> <a href="{$linkToOtherTab}">Updates</a></li>
        <li> <a href="{$linkToResourcesTab}">Resources</a></li>
        <li> <a href="{$linkToInfoTab}"> Info</li>
    </ul>
    <div id="tabbodies">
        <div class="tabbody">
        {include file="findInclude:common/templates/navlist.tpl" navlistItems=$contents subTitleNewline=true}
        </div>
    </div>
</div>
{include file="findInclude:common/templates/footer.tpl"}
