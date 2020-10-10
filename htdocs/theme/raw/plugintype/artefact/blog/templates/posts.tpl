{foreach from=$posts item=post}
    <div id="posttitle_{$post->id}" class="{if $post->published} published{else} draft{/if} list-group-item">
        <div class="post-heading">
            <h2 class="list-group-item-heading title text-inline">
                {$post->title}
            </h2>

            <div class="list-group-item-controls">
                <span id="poststatus{$post->id}" class="poststatus text-inline">
                    {if $post->published}
                        {str tag=published section=artefact.blog}
                    {else}
                        {str tag=draft section=artefact.blog}
                    {/if}
                </span>

                {if !$post->locked && $post->canedit}
                <div id="changepoststatus{$post->id}" class="changepoststatus text-inline">
                    {$post->changepoststatus|safe}
                </div>
                {/if}

                {if $post->locked}
                <span class="locked-post text-muted">
                    <span class="icon icon-lock left" role="presentation" aria-hidden="true"></span>
                    {str tag=submittedforassessment section=view}
                </span>
                {elseif $post->canedit}
                <div class="btn-group postcontrols">
                    <form name="edit_{$post->id}" action="{$WWWROOT}artefact/blog/post.php" class="form-as-button float-left">
                        <input type="hidden" name="id" value="{$post->id}">
                        <div class="first">
                            <button type="submit" class="submit btn btn-secondary btn-sm" title="{str(tag=edit)|escape:html|safe}">
                                <span class="icon icon-pencil-alt" role="presentation" aria-hidden="true"></span>
                                <span class="sr-only">{str tag=editspecific arg1=$post->title |escape:html|safe}</span>
                            </button>
                        </div>
                    </form>
                    {$post->delete|safe}
                </div>
                {/if}
            </div>
        </div>
        <div id="postdetails_{$post->id}" class="postdetails postdate text-small text-midtone">
            <span class="icon icon-regular icon-calendar-alt left" role="presentation" aria-hidden="true"></span>
            <strong>
                {str tag=postedon section=artefact.blog}:
            </strong>
            {$post->ctime}
            {if $post->lastupdated}
                <br>
                <span class="icon icon-regular icon-calendar-alt left" role="presentation" aria-hidden="true"></span>
                <strong>
                    {str tag=updatedon section=artefact.blog}:
                </strong>
                {$post->lastupdated}
            {/if}

            {if $post->tags}
            <p id="posttags_{$post->id}" class="tags">
                <span class="icon icon-tags left" role="presentation" aria-hidden="true"></span>
                <strong>{str tag=tags}:</strong>
                {list_tags owner=$post->author tags=$post->tags}
            </p>
            {/if}
        </div>
        <p id="postdescription_{$post->id}" class="postdescription">
            {$post->description|clean_html|safe}
        </p>

        {if $post->files}
        <div class="has-attachment card collapsible" id="postfiles_{$post->id}">
            <div class="card-header">
                <a class="text-left collapsed" data-toggle="collapse" href="#attach_{$post->id}" aria-expanded="false">
                    <span class="icon left icon-paperclip icon-sm" role="presentation" aria-hidden="true"></span>
                    <span class="text-small"> {str tag=attachedfiles section=artefact.blog} </span>
                    <span class="metadata">({$post->files|count})</span>
                    <span class="icon icon-chevron-down collapse-indicator float-right" role="presentation" aria-hidden="true"></span>
                </a>
            </div>
            <div class="collapse" id="attach_{$post->id}">
                <ul class="list-group list-unstyled">
                {foreach from=$post->files item=file}
                    <li class="list-group-item">
                        <a class="file-icon-link" href="{$WWWROOT}artefact/file/download.php?file={$file->attachment}" {if $file->description} title="{$file->description}" data-toggle="tooltip"{/if}>
                            {if $file->icon}
                            <img src="{$file->icon}" alt="" class="file-icon">
                            {else}
                            <span class="icon icon-{$file->artefacttype} icon-lg text-default left file-icon" role="presentation" aria-hidden="true"></span>
                            {/if}
                        </a>
                        <span class="title">
                            <a href="{$WWWROOT}artefact/file/download.php?file={$file->attachment}" {if $file->description} title="{$file->description}" data-toggle="tooltip"{/if}>
                                <span class="text-small">{$file->title|truncate:40}</span>
                            </a>
                        </span>
                        <span class="text-midtone text-small float-right">({$file->size|display_size})</span>
                    </li>
                {/foreach}
                </ul>
            </div>
        </div>
        {/if}
    </div>
{/foreach}
