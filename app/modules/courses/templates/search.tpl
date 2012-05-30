{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:common/templates/search.tpl" emphasized=false extraArgs=$hiddenArgs}

{include file="findInclude:modules/courses/templates/include/termselector.tpl"}

{include file="findInclude:common/templates/results.tpl" results=$results accessKey=false}

{include file="findInclude:common/templates/footer.tpl"}
