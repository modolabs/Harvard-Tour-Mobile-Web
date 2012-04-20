{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/termselector.tpl"}

{capture assign=tabBody}
{include file="findInclude:modules/courses/templates/updatesList.tpl" updates=$contents}
{/capture}
{include file="findInclude:modules/courses/templates/courseTabs.tpl" tabBody=$tabBody}
{include file="findInclude:common/templates/footer.tpl"}
