{if $courses}
{if $courseListHeading}
  <h3>{$courseListHeading}</h3>
{/if}
<ul class="nav">
{foreach $courses as $course}
<li class="statusitem update update_{$course.type}">
  <a href="{$course.url}"{block name="courselinkJS"}{/block}>
    {$course.title}
    <div class="smallprint {if $course.img}icon{/if}">
    {if $course.img}<img src="{$course.img}" width="16" height="16" alt="" class="listtype">{/if}
    {$course.subtitle}
    </div> 
  </a>
</li>
{/foreach}
</ul>
{/if}