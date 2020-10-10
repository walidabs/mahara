{if $enablecomments || $LOGGEDIN}
<div id="add-comment" class="add-comment-container">
    {if $enablecomments}
    <h3 id="add_feedback_heading">
        <span class="icon icon-comments left" role="presentation" aria-hidden="true"></span>
        {str tag=addcomment section=artefact.comment}
    </h3>
    {/if}

    <div class="addcommentform" id="comment-form">
        {$addfeedbackform|safe}
    </div>
</div>
{/if}
