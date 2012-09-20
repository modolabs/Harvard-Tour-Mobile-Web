{include file="findInclude:common/templates/header.tpl"}

<h3 class="nonfocal">Assignment Name</h3>
<p class="focal">{$grade.title}</p>

{if $grade.dueDate}
<h3 class="nonfocal">Due Date</h3>
<p class="focal">{$grade.dueDate}</p>
{/if}

{if $grade.dateModified}
<h3 class="nonfocal">Last Submitted, Edited or Graded</h3>
<p class="focal">{$grade.dateModified}</p>
{/if}

{if $grade.status}
<h3 class="nonfocal">Status</h3>
<p class="focal">{$grade.status}</p>
{/if}

{if $grade.grade !== null}
<h3 class="nonfocal">Grade</h3>
<p class="focal">{$grade.grade}</p>
{/if}

{if $grade.possiblePoints !== null}
<h3 class="nonfocal">Possible Points</h3>
<p class="focal">{$grade.possiblePoints}</p>
{/if}

{if $grade.percent !== null}
<h3 class="nonfocal">Percentage</h3>
<p class="focal">{$grade.percent}</p>
{/if}

{if $grade.studentComment}
<h3 class="nonfocal">Feedback to Student</h3>
<p class="focal">{$grade.studentComment}</p>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
