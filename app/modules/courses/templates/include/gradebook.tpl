{block name="gradebookList"}
{foreach $gradesListLinks as $gradesLink}
    {include file="findInclude:modules/courses/templates/include/gradebookList.tpl"  gradeListHeading=$gradesLink['gradeListHeading'] grades=$gradesLink['gradesLinks']}    
{/foreach}
{/block}