{extends file="findExtends:modules/courses/templates/index.tpl"}

{block name="showCoursesLoginLink"}
<div class="loginstatus">
    <ul class="nav secondary loginbuttons">
    <li{if $footerLoginClass} class="{$footerLoginClass}"{/if}><a href="{$footerLoginLink}">{$footerLoginText}</a></li>
    </ul>
</div>
{/block}