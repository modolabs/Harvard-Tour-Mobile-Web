{if $courses}
{if $courseListHeading}
<div class="nonfocal">
  <h3>{$courseListHeading}</h3>
</div>
{/if}
<ul class="nav">
{foreach $courses as $course}
<li class="update update_{$course.type}">
  <a href="{$course.url}">
    {$course.title}
    <div class="smallprint">
    {if $course.img}<img src="{$course.img}" width="24" height="24" alt="">{/if}
    {$course.subtitle}
    </div> 
  </a>
</li>
{/foreach}
</ul>
{/if}