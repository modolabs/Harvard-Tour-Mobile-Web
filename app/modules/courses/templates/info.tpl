{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/info/details.tpl" tabInfoDetails=$infoDetails.info}

{block name="links"}
{if $links}
{include file="findInclude:common/templates/navlist.tpl" navListHeading="Links" navlistItems=$links subTitleNewline=true}
{/if}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
