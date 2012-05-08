{if $tasks}
{if $tasksListHeading}
<div class="nonfocal">
  <h2>{$tasksListHeading}</h2>
</div>
{/if}
<ul class="nav">
{foreach $tasks as $task}
<li class="task task_{$task.type}">
  <a href="{$task.url}">
    {$task.courseTitle|default:$task.title}
    <div class="smallprint">
    {if $task.img}<img src="{$task.img}" width="16" height="16" alt="" class="listtype">{/if}
    {$task.updated}
    </div> 
  </a>
</li>
{/foreach}
</ul>
{/if}